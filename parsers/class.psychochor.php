<?php
class Psychochor{
	private static $base_url = 'http://psycho-chor.de/';
	private static $event_list_page = 'de/konzerte/aktuell.html';
	private static $cities = array('Erfurt','Jena', 'Weimar');

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$event_div = $xml->getElementById('c1074');
		$divs = $event_div->getElementsByTagName('div');

		foreach ($divs as $event){
			$class = $event->getAttribute('class');
			if ($class!='csc-default') continue;
			//if (strpos($class, 'wrapper')!==false) continue;
			$tables = $event->getElementsByTagName('table');
			if ($tables->length <1) continue;
			self::read_event(self::$base_url . self::$event_list_page,$event);
		}
	}

	public static function read_event($source_url,$xml){
		$title = self::read_title($xml);
		$table_rows = $xml->getElementsByTagName('tr');
		$date = null;
		$time = null;
		$description = '';
		$location = null;
		foreach ($table_rows as $row){
			$cols = $row->getElementsByTagName('td');
			$first = null;
			$second = null;
			foreach ($cols as $col){
				if ($first === null){
					$first = trim(str_replace("\xC2\XA0", ' ', $col->nodeValue));
				} elseif ($second === null){
					$second = trim(str_replace("\xC2\XA0", ' ', $col->nodeValue));
					break;
				}
			}
			if ($first=='Wochentag'){

			} elseif ($first == 'Datum'){
				$date = $second;
			} elseif ($first == 'Beginn'){
				$time = trim(str_replace('Uhr','',$second));
			} elseif ($first == 'Einlass'){
				$description.=$first.': '.$second."\n";
			} elseif ($first == 'Ort'){
				if ($location === null){
					$location=$second;
				} else {
					$location .= ", ".$second;
				}
			} elseif ($first == 'Adresse'){
				if ($location === null){
					$location=$second;
				} else {
					$location .= ", ".$second;
				}
			} elseif ($first == ''){
				if ($location === null){
					$location=$second;
				} else {
					$location .= ", ".$second;
				}
			} elseif ($first == 'Eintritt'){
				$description.=$first.': '.$second."\n";
			} elseif (preg_match('/^[0-9]{5} /', $first)) {
				if ($location === null){
					$location=$first;
				} else {
					$location .= ", ".$first;
				}
			} else {
				error_log('Psychochor-Parser found unknown field "'.$first.'": "'.$second.'"');
			}
		}

		if ($date === null || $time === null) return;
		$start = self::date($date.' '.$time);
		$source_url=$source_url.'?date='.$date.'&time='.$time;
		$links = array(url::create($source_url,'Homepage'));
		$tags = array('Chor','Psychochor','Konzert');
		foreach (self::$cities as $city){
			if (strpos($location, $city)!==false) $tags[]=$city;
		}
		$event = Event::get_imported($source_url);
		if ($event === null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, null, $location, null,$tags,$links,null,false);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($description);
			$event->set_start($start);
			$event->set_location($location);
			foreach ($tags as $tag) $event->add_tag($tag);
			foreach ($links as $link) $event->add_link($link);
			$event->save();
		}
	}

	private static function read_title($xml){
		$headings = $xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			$parts = explode(':',$heading->nodeValue,2);
			return trim($parts[1]);
		}
		return null;
	}

	private static function read_description($xml){
		$articles = $xml->getElementsByTagName('article');
		$description = '';
		foreach ($articles as $article){
			$paragraphs = $article->getElementsByTagName('p');
			$first=true;
			foreach ($paragraphs as $paragraph){
				if ($first){
					$first = false;
					continue;
				}
				$text = trim($paragraph->textContent);
				if (!empty($text)) {
					if ($text == 'Sorry, the comment form is closed at this time.') continue;
					$description .= str_replace('€Kategorie', "€\nKategorie", $text) . NL;
				}
			}
		}
		return $description;
	}

	private static function read_start($xml){
		global $db_time_format;
		$articles = $xml->getElementsByTagName('article');
		$description = '';
		foreach ($articles as $article){
			$paragraphs = $article->getElementsByTagName('p');
			foreach ($paragraphs as $paragraph){
				$text = trim($paragraph->textContent);
				if (preg_match('/\d\d.\d\d.\d\d:\d\d/',$text)){
					return $text;
				}
				if (preg_match('/\d\d.\d\d.\d\d\d\d/',$text)){
					return $text;
				}
			}
		}
		return null;
	}

	private static function read_tags($xml){
		global $db_time_format;
		$articles = $xml->getElementsByTagName('article');
		$description = '';
		foreach ($articles as $article){
			$paragraphs = $article->getElementsByTagName('p');
			foreach ($paragraphs as $paragraph){
				$text = trim($paragraph->textContent);
				$pos = strpos($text, 'Kategorie:');
				if ($pos!==false) {
					$tags = explode(' ',substr($text, $pos+11));
					$tags[] = 'CafeWagner';
					$tags[] = 'Jena';
					return $tags;
				}
			}
		}
		return array('CafeWagner','Jena');
	}

	private static function read_links($xml,$source_url){
		$articles = $xml->getElementsByTagName('article');
		$url = url::create($source_url,loc('event page'));
		$links = array($url,);
		foreach ($articles as $article){
			$anchors = $article->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if ($anchor->hasAttribute('href')){
					$address = $anchor->getAttribute('href');
					if (strpos(guess_mime_type($address),'image')===false){
						$links[] = url::create($address,trim($anchor->nodeValue));
					}
				}
			}
		}
		return $links;
	}

	private static function read_images($xml){
		$articles = $xml->getElementsByTagName('article');
		$attachments = array();
		foreach ($articles as $article){
			$images = $article->getElementsByTagName('img');
			foreach ($images as $image){
				$address = $image->getAttribute('src');
				$mime = guess_mime_type($address);
				$attachments[] = url::create($address,$mime);
			}
		}
		return $attachments;
	}



	private static function date($text){
		global $db_time_format;
		$date=extract_date($text);
		$time=extract_time($text);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		return date($db_time_format,$secs);
	}
}