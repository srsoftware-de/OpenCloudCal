<?php
class Kassablanca{
	private static $base_url = 'http://www.kassablanca.de/';
	private static $event_list_page = 'programm/aktuell';
	
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
		$tables = $xml->getElementsByTagName('table');
		$event_pages = array();		
		foreach ($tables as $table){
			$links = $table->getElementsByTagName('a');
			foreach ($links as $link){
				$page = trim($link->getAttribute('href'));
				if (strpos($page, 'event')!==false) {
					$event_pages[$page]=true; // used as keys, so duplicates get removed
				}
			}
		}
		foreach ($event_pages as $page => $dummy){
			self::read_event(self::$base_url . $page);
		}
	}
	
	public static function read_event($source_url){
		$xml = load_xml($source_url);
		$title = self::read_title($xml);
		$description = self::read_description($xml);
		$start=self::date(self::read_start($xml));
		print $start.NL; die();
		return;
		$location = self::read_location($xml);
		$coords = null;		
		if (stripos($location, 'Kulturbahnhof') !== false){
			$coords = '50.93658, 11.59266';
		}
		
		$tags = self::read_tags($xml);

		$links = self::read_links($xml);
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
		$contentleft = $xml->getElementById('contentleft');
		$divs = $contentleft->getElementsByTagName('div');
		foreach ($divs as $div){
			if ($div->hasAttribute('class') && $div->getAttribute('class')=='headline'){
				return trim($div->nodeValue);
			}
		}
		return null;		
	}
	
	private static function read_description($xml){
		$contentleft = $xml->getElementById('contentleft');
		$divs = $contentleft->getElementsByTagName('div');
		$description = '';
		foreach ($divs as $div){
			if ($div->hasAttribute('class')){
				$class = trim($div->getAttribute('class'));				
				if (stripos($class, 'description')!==false){
					$description .= trim($div->nodeValue);	
				}				
			}
		}
		return $description;
	}
	
	private static function read_start($xml){
		$tables = $xml->getElementsByTagName('table');
		$day = null;
		$month = null;
		$time = null;
		foreach ($tables as $table){
			$divs = $table->getElementsByTagName('div');
			foreach ($divs as $div){
				if ($div->hasAttribute('class')){
					$text = $div->nodeValue;
					$class = $div->getAttribute('class');
					if ($class == 'date1'){
						$day = trim(substr($text,-2,2));
						continue;
					}
					if ($class == 'date2'){
						$month = self::$months[$text];
						continue;
					}
					if ($class == 'time2'){
						$time = trim(substr($text,-5,5));
						continue;
					}
				}
			}
		}
		return $day.'.'.$month.'. '.$time;
	}
	
	private static function read_location($xml){
		$container = $xml->getElementById('container');
		$paragraphs = $container->getElementsByTagName('p');
		$location = 'Kulturbahnhof, Spitzweidenweg 26, 07743 Jena';
		foreach ($paragraphs as $paragraph){
			$text = trim($paragraph->nodeValue);
			$pos = stripos($text, 'Location:');
			if ($pos !== false){
				$location = trim(substr($text, $pos+9));
			}
		}
		return $location;
	}
	
	private static function read_tags($xml){
		$tags = array('Kulturbahnhof', 'CosmicDawn', 'Jena');
		return $tags;
	}
	
	private static function read_links($xml){
		$container = $xml->getElementById('container');
		$anchors = $container->getElementsByTagName('a');
		$links = array();
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$text = trim($anchor->nodeValue);
				if ($text == 'Zurück') continue;
				$links[] = url::create($anchor->getAttribute('href'),$text);
			}
		}		
		return $links;
	}
	
	private static function read_images($xml){
		$wrapper = $xml->getElementById('container');
		$images = $wrapper->getElementsByTagName('img');
		$attachments = array();
		foreach ($images as $image){
			$address = self::$base_url.$image->getAttribute('src');
			$mime = guess_mime_type($address);
			$attachments[] = url::create($address,$mime);
			
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