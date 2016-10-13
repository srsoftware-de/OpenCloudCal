<?php
class MpireJena{
	private static $base_url = 'http://mpire-jena.de/';
	private static $event_list_page = 'calendar';
	
	private static $months = array(
			'Januar'=>'01',
			'Februar'=>'02',
			'März'=>'03',
			'April'=>'04',
			'Mai'=>'05',
			'Juni'=>'06',
			'Juli'=>'07',
			'August'=>'08',
			'September'=>'09',
			'Oktober'=>'10',
			'November'=>'11',
			'Dezember'=>'12');
	

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$anchors = $xml->getElementsByTagName('a');
		$event_pages = array();
		foreach ($anchors as $anchor){
			$href = $anchor->getAttribute('href');
			if (strpos($href, 'tc-events')!==false){
				$event_pages[]=$href;				
			}
		}
		
		$event_pages = array_unique($event_pages);
		foreach ($event_pages as $page){
			self::read_event($page);
		}
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);

		$title = self::read_title($xml);
		$description = self::read_description($xml,$title);
		$start = self::date(self::read_start($xml));
		$end = self::date(self::read_end($xml));
		$location = 'M-Pire Music Club Jena - Prüssingstr. 18, 07747 Jena';

		$coords = '50.883224, 11.597377';

		$tags = self::read_tags($description);
		$links = self::read_links($xml,$source_url);
		$attachments = self::read_images($xml);
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$event = Event::get_imported($source_url);
		if ($event == null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, $end, $location, $coords,$tags,$links,$attachments,false);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($description);
			$event->set_start($start);
			$event->set_end($end);
			$event->set_location($location);
			$event->set_coords($coords);
			foreach ($tags as $tag) $event->add_tag($tag);
			foreach ($links as $link) $event->add_link($link);
			foreach ($attachments as $attachment) $event->add_attachment($attachment);
			$event->save();
		}
	}

	private static function read_title($xml){
		$headings = $xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml,$title){
		$articles = $xml->getElementsByTagName('article');
		$description = '';
		foreach ($articles as $article){
			$paragraphs = $article->getElementsByTagName('p');
			$first=true;
			foreach ($paragraphs as $paragraph){
				$text = trim($paragraph->nodeValue);
				if ($text == $title) {
					$description = '';
				} else {
					$description.=$text.'<br/>';
				}
			}
		}
		return $description;
	}

	private static function read_start($xml){
		global $db_time_format;
		$times = $xml->getElementsByTagName('time');
		foreach ($times as $time){
			$text = trim($time->nodeValue);
			foreach (self::$months as $name => $month){
				$text = str_replace(' '.$name.' ', $month.'.', $text);
			}
			$dash = strpos($text, '-');
			if ($dash !== false) $text=trim(substr($text, 0,$dash));
			return $text;
		}
		return null;
	}
	
	private static function read_end($xml){
		global $db_time_format;
		$times = $xml->getElementsByTagName('time');
		foreach ($times as $time){
			$text = trim($time->nodeValue);
			foreach (self::$months as $name => $month){
				$text = str_replace(' '.$name.' ', $month.'.', $text);
			}
			$dash = strpos($text, '-');
			if ($dash === false) continue;
			$text=trim(substr($text, $dash+1));
			return $text;
		}
		return null;
	}

	private static function read_tags($description){
		return array('Mpire.Jena','Jena');
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
		if ($text == null) return null;
		$date=extract_date($text);
		$time=extract_time($text);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		return date($db_time_format,$secs);
	}
}