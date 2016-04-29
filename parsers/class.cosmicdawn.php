<?php
class CosmicDawn{
	private static $base_url = 'http://www.cosmic-dawn.de/';
	private static $event_list_page = 'termine.html';
	
	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$links = $xml->getElementsByTagName('a');
		$event_pages = array();		
		foreach ($links as $link){
			$page = $link->getAttribute('href');
			if (strpos($page, 'eventleser')===0) {
				$event_pages[$page]=true; // used as keys, so duplicates get removed
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
		$location = self::read_location($xml);
		$coords = null;		
		if (stripos($location, 'Kulturbahnhof') !== false){
			$coords = '50.93658, 11.59266';
		}
		
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
		$title_container = $xml->getElementById('container');
		$headlines = $title_container->getElementsByTagName('h1');		
		foreach ($headlines as $headline){
			return trim($headline->nodeValue);
		}
		return null;		
	}
	
	private static function read_description($xml){
		$container = $xml->getElementById('container');		
		$paragraphs = $container->getElementsByTagName('p');
		$description = '';				
		foreach ($paragraphs as $paragraph){
			if ($paragraph->hasAttribute('class')){
				$class = $paragraph->getAttribute('class');
				if ($class == 'info') continue;
				if ($class == 'back') continue;
			}
			$description .= trim($paragraph->nodeValue).NL;
		}
		return $description;
	}
	
	private static function read_start($xml){
		$container = $xml->getElementById('container');		
		$paragraphs = $container->getElementsByTagName('p');
		$date = null;	
		$time = null;			
		foreach ($paragraphs as $paragraph){
			$text = trim($paragraph->nodeValue);
			if ($paragraph->hasAttribute('class') && $paragraph->getAttribute('class') == 'info') {
				$date = substr($text,0,10);
				continue;
			}
			$pos = strpos($text, 'show:');
			if ($time == null && $pos!==false){
				$time = trim(substr($text,$pos+5));
				continue;				
			}
			$pos = strpos($text, 'Start:');
			if ($time == null && $pos!==false){
				$keys = array('ca.','Uhr');
				$time = trim(str_replace($keys, '', trim(substr($text,$pos+6))));
				continue;
			}
			$pos = strpos($text, 'doors:');
			if ($time == null && $pos!==false){
				$time = trim(substr($text,$pos+6));
				continue;
			}
				
		}
		$pos=strpos($time,'pm');
		if ($pos!==false){
			$time = trim(substr($time,0,$pos));
			$time = (12+(int)$time).':00';
		}
		if ($time == null) $time = '21:00';
		if ($date == null) return null;		
		return $date.' '.$time;
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
	
	private static function read_links($xml,$source_url){
		$container = $xml->getElementById('container');
		$anchors = $container->getElementsByTagName('a');
		$url = url::create($source_url,loc('event page'));	
		$links = array($url,);	
		
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