<?php
class FromHell{
	private static $base_url = 'http://www.clubfromhell.de';
	private static $event_list_page = '/events/uebersicht-alle_liste.html';

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
		$content = $xml->getElementById('cfh_content_inner');
		if ($content == null) return;
		$links = $content->getElementsByTagName('a');
		foreach ($links as $link){
			$href = trim($link->getAttribute('href'));
			// do not follow links to overview
			if (strpos($href,'events/uebersicht')===false){
				$event_pages[]=$href;
			}
		}
		foreach ($event_pages as $page){
			self::read_event(self::$base_url.$page);
		}
	}
	
	public static function find_canonical($xml,$url){
		$bodies = $xml->getElementsByTagName('body');
		foreach ($bodies as $body){
			if ($body != null && $body->hasAttribute('class')){
				$class = trim($body->getAttribute('class'));
				if ($class == '') continue;
				preg_match('/node-\d+/', $class, $nodes);
				if (!empty($nodes)){
					return self::$base_url.'/'.str_replace('-', '/', reset($nodes));
				}
			}
		}
		return $url;
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);
		$source_url = self::find_canonical($xml,$source_url);

		$title = self::read_title($xml);
		$description = self::read_description($xml);
		$start = self::date(self::read_start($xml));
		$location = 'From Hell, Flughafenstraße 41, 99092 Erfurt / Bindersleben';
		$coords = '50.973578, 10.954197';
		$tags = self::read_tags($xml);
		$links = self::read_links($xml,$source_url);
		$attachments = self::read_images($xml);
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$event = Event::get_imported($source_url);
		if ($event == null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, null, $location, $coords,$tags,$links,$attachments,false);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($description);
			$event->set_start($start);
			$event->set_location($location);
			$event->set_coords($coords);
			foreach ($tags as $tag) $event->add_tag($tag);
			foreach ($links as $link) $event->add_link($link);
			foreach ($attachments as $attachment) $event->add_attachment($attachment);
			$event->save();
		}
	}

	private static function read_title($xml){
		$main = $xml->getElementById('block-system-main');		
		$headings = $main->getElementsByTagName('h2');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		$main = $xml->getElementById('block-system-main');		
		$divs = $xml->getElementsByTagName('div');
		$description = '';
		foreach ($divs as $div){
			if ($div->hasAttribute('class')){
				$class = $div->getAttribute('class');
				if (strpos($class,'views-field-body')!==false || strpos($class,'views-field-field-shuttle-service')!==false){
					$description .= trim(str_replace('Sonstige Informationen:','',$div->nodeValue)).NL;
				}
			}
		}
		return $description;
	}

	private static function read_start($xml){
		$main = $xml->getElementById('block-system-main');
		$spans = $main->getElementsByTagName('span');
		foreach ($spans as $span){
			if ($span->hasAttribute('class')){
				$class = $span->getAttribute('class');
				if (strpos($class,'date-display-s')!==false){ // date-display-start or date-display-single
					$datestring = $span->nodeValue;
					$keys = array('Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag','Uhr',' um');
					$datestring = str_replace($keys, '', $datestring);
					foreach (self::$months as $name => $month){
						$datestring = str_replace(' '.$name.' ', '.'.$month.'.', $datestring);
					}
					return $datestring;
				}
			}
		}
		return null;
	}

	private static function read_tags($xml){
		$tags = array('FromHell','Erfurt');
		$main = $xml->getElementById('block-system-main');		
		$divs = $main->getElementsByTagName('div');
		foreach ($divs as $div){
			if ($div->hasAttribute('class')){
				$class = $div->getAttribute('class');
				if (strpos($class,'views-field-field-genre')!==false){
					$genres = $div->getElementsByTagName('div');
					foreach ($genres as $genre){
						$new_tags = explode(',',trim(str_replace(' ', '', $genre->nodeValue)));
						$tags = array_merge($tags,$new_tags);
					}
				}
			}
		}
		foreach ($tags as $tag){
			$lc=strtolower($tag);
			if (strpos($lc, 'goth')!== false){
				$tags[]='schwarzesjena';
			}
			if ($lc=='ebm'){
				$tags[]='schwarzesjena';
			}
				
		}		
		return array_unique($tags);
	}

	private static function read_links($xml,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = array($url,);	
		$main = $xml->getElementById('block-system-main');		
		$divs = $main->getElementsByTagName('div');
		foreach ($divs as $div){
			if ($div->hasAttribute('class')){
				$class = $div->getAttribute('class');
				if (strpos($class,'views-field-field-social-networks')!==false){
					$anchors = $div->getElementsByTagName('a');
					foreach ($anchors as $anchor){
						if ($anchor->hasAttribute('href')){
							$address = $anchor->getAttribute('href');
							if (strpos($address,'facebook.com')!==false){
								$links[]=url::create($address,'Facebook');
							}							
						}
					}
				}
			}
		}		
		return $links;
	}

	private static function read_images($xml){
		$images = array();
		$main = $xml->getElementById('block-system-main');		
		$divs = $main->getElementsByTagName('div');
		foreach ($divs as $div){
			if ($div->hasAttribute('class')){
				$class = $div->getAttribute('class');
				if (strpos($class,'views-field-field-flyer')!==false){
					$imgs = $div->getElementsByTagName('img');
					foreach ($imgs as $img){
						$address = $img->getAttribute('src');
						$pos = strpos($address, '?');
						if ($pos !== false){
							$address = substr($address, 0,$pos);
						}
						$mime = guess_mime_type($address);
						if (strpos($mime,'image')!==false){
							$images[] = url::create($address,$mime);
						}
					}
				}
			}
		}		
		return $images;
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
