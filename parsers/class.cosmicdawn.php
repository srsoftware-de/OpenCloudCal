<?php
class CosmicDawn{
	private static $base_url = 'https://www.kuba-jena.de/';
	private static $event_list_page = 'veranstaltungen';

	public static function read_events(){
	    ini_set("user_agent","Mozilla/5.0 (Windows NT 6.1; rv:8.0) Gecko/20100101 Firefox/8.0");
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$links = $xml->getElementsByTagName('a');
		
		$event_pages = array();
		foreach ($links as $link){
			$page = $link->getAttribute('href');
			if (strpos($page, '/veranstaltung/')>0) {
				$event_pages[$page]=true; // used as keys, so duplicates get removed
			}
		}
		foreach ($event_pages as $page => $dummy) self::read_event($page);
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
		if ($event === null){
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
		$headlines = $xml->getElementsByTagName('h1');
		foreach ($headlines as $headline){
			return trim($headline->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		$container = $xml->getElementById('main');
		$divs = $container->getElementsByTagName('div');
		foreach ($divs as $div){
		    if (!$div->hasAttribute('class')) continue;
		    $class = $div->getAttribute('class');
		    if ($class != 'wpem-single-event-body-content') continue;
		    return trim($div->nodeValue).NL;;
		}
		return "";
	}

	private static function read_start($xml){
		$container = $xml->getElementById('main');
		$spans = $container->getElementsByTagName('span');
		$date = null;
		$time = null;
		$einlass = null;
		foreach ($spans as $span){
		    if ($date === null && $span->hasAttribute('class') && $span->getAttribute('class') == 'wpem-event-date-time-text') {
		        $date = $span->getAttribute('content');
				continue;
			}
			$text = trim($span->nodeValue);
			$pos = strpos($text, 'Beginn:');
			if ($time === null && $pos !== false){
				$keys = array('ca.','Uhr');
				$time = trim(str_replace($keys, '', trim(substr($text,$pos+7))));
				continue;
			}
			$pos = strpos($text, 'Einlass:');
			if ($einlass === null && $pos !== false){
			    $einlass = trim(substr($text,$pos+8));
				continue;
			}

		}
		$pos=strpos($time,'pm');
		if ($pos!==false){
			$time = trim(substr($time,0,$pos));
			$time = (12+(int)$time).':00';
		}
		if ($time === null) $time = $einlass;
		if ($time === null) $time = '21:00';
		if ($date === null) return null;
		return $date.' '.$time;
	}

	private static function read_location($xml){
		return 'Kulturbahnhof, Spitzweidenweg 26, 07743 Jena';
	}

	private static function read_tags($xml){
	    $tags = ['Kulturbahnhof', 'Jena'];
	    $main = $xml->getElementById('main');
	    $spans = $main->getElementsByTagName('span');
	    foreach ($spans as $span){
	        if (!$span->hasAttribute('class')) continue;
	        $class = $span->getAttribute('class');
	        if (strpos($class,'event-type') === false) continue;
	        $tag = $span->nodeValue;
	        $parts = explode('/',$tag);
	        foreach ($parts as $part){
	            $tags[] = trim($part);
	        }
	    }
	    return $tags; 
	}

	private static function read_links($xml,$source_url){
		$container = $xml->getElementById('main');
		$anchors = $container->getElementsByTagName('a');
		$url = url::create($source_url,loc('event page'));
		$links = [$url];

		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$href = $anchor->getAttribute('href');
			if (strpos($href,'google') !== false) continue;
			$text = trim($anchor->nodeValue);
			$links[] = url::create($href,$text);
		}
		return $links;
	}

	private static function read_images($xml){
		$wrapper = $xml->getElementById('main');
		$divs = $wrapper->getElementsByTagName('div');
		$attachments = [];
		foreach ($divs as $div){
		    if (! $div->hasAttribute('class')) continue;
		    $class = $div->getAttribute('class');
		    if ($class !== 'wpem-single-event-header-top') continue;
    		$images = $div->getElementsByTagName('img');
    		
    		foreach ($images as $image){
    			$address = $image->getAttribute('src');
    			if (strpos($address, 'icon-check.png') !== false) continue;
    			$mime = guess_mime_type($address);
    			$attachments[] = url::create($address,$mime);
    
    		}
		}
		foreach ($divs as $div){
		    if (! $div->hasAttribute('class')) continue;
		    $class = $div->getAttribute('class');
		    if ($class !== 'wpem-single-event-body') continue;
		    $images = $div->getElementsByTagName('img');
		    foreach ($images as $image){
		        $address = $image->getAttribute('src');
		        if (strpos($address, 'icon-check.png') !== false) continue;
		        $mime = guess_mime_type($address);
		        $attachments[] = url::create($address,$mime);
		        
		    }
		}
		return $attachments;
	}

	private static function date($input){
	    $datestring=date_parse($input);
	    $secs=parseDateTime($datestring);
	    return date(TIME_FMT,$secs);
	}
}
