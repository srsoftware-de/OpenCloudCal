<?php
require_once 'dom_methods.php';

class EBurg{
	private static $base_url = 'https://engelsburg.club/';
	private static $event_list_page = 'programm';



	public static function read_events(){
	    
	    $url = self::$base_url . self::$event_list_page;
	    
	    while ($url !== null){
	        // load page
	        $xml = load_xml($url);		
		
    		// find links to event page
	        $event_links = (findElements($xml, ANCHOR, CLS, 'read-more'));
    		foreach ($event_links as $link) { 
    		    // parse event page
    		    if ($link->hasAttribute(LINK)){
    		        self::read_event($link->getAttribute(LINK));
    		    }
    		}
    		
    		// find links to next event list page
    		$next_pages = findElements($xml, DIV, CLS, 'next');
    		$url = null;
    		foreach ($next_pages as $next_page){
    		    $links = findElements($next_page, ANCHOR);
    		    foreach ($links as $link){
    		        if ($link->hasAttribute(LINK)) $url = $link->getAttribute(LINK);
    		    }
    		}
	    }
	}

	public static function coords($location){
		if ($location == 'Eburg Club') return '50.978339, 11.026929';
		if (strpos($location, 'DuckDich')||strpos($location, 'Vortragsraum')) return '50.978137, 11.027100';
		if (strpos($location, 'Biergarten')) return '50.978137, 11.027100';

	}

	public static function expand($location){
		if ($location == 'Eburg Club') return 'Club "Eburg", Allerheiligenstraße 20/21, Erfurt';
		if (strpos($location, 'DuckDich')||strpos($location, 'Vortragsraum')) return 'Café "DuckDich", Allerheiligenstraße 20/21, Erfurt';
		if (strpos($location, 'Biergarten')) return 'Biergarten, Allerheiligenstraße 20/21, Erfurt';
		return $location.', Erfurt';
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);
		$containers = findElements($xml, DIV, CLS, 'post-type-program');
		foreach ($containers as $container){
		    $title = self::read_title($container);
		    $description = self::read_description($container);
		    $start = self::date(self::read_start($container));
    		$location = 'Eburg Club';
    		$coords = self::coords($location);
    		$location = self::expand($location);
    
    		$tags = self::read_tags($container);
    		$links = self::read_links($container,$source_url);
    		$attachments = self::read_images($container);
    		
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
	}

	private static function read_title($xml){
		$headings = $xml->getElementsByTagName(H1);
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
	    $divs = findElements($xml, DIV, CLS, 'col-sm-9');
	    foreach ($divs as $div){
	        return trim($div->nodeValue) . NL;
	    }
	    return NL;
	}

	private static function read_start($xml){
	    $h4s = findElements($xml, H4);
        foreach ($h4s as $h4){
            $string = $h4->nodeValue;
            $parts = explode(' ', $string);
            $day = trim($parts[0],'.');
            $month = MONTHS_DE[$parts[1]];
            $year = date("Y");
            $now = time();
            $date = strtotime($year.'-'.$month.'-'.$day);
            if ($now > $date) {
                $year++;
                strtotime($year.'-'.$month.'-'.$day);
            }
            return date('d.m.Y ',$date) . str_replace('.', ':', $parts[3]);
		}
		return null;
	}

	private static function read_tags($xml){
	    $tags = [];
		// Fallback:
		$tags[] = 'Eburg';
		$tags[] = 'Erfurt';
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = [$url];
		
		$as = findElements($xml, ANCHOR);
	    foreach ($as as $anchor){
	        $text = $anchor->nodeValue;
	        if (strpos($text, 'zurück') !== false) continue;
	        if (strpos($text, 'nächste') !== false) continue;
	        if ($anchor->hasAttribute(LINK)) $links[] = url::create($anchor->getAttribute(LINK),$text);
		}

		return $links;
	}

	private static function read_images($xml){		
		$images = [];
		$imgs = findElements($xml, IMAGE);
		foreach ($imgs as $img){
		    if ($img->hasAttribute(SOURCE)){
		        $address = $img->getAttribute(SOURCE);
		        $mime = guess_mime_type($address);
		        $images[] = url::create($address,$mime);
		    }
		}
		return $images;
	}



	private static function date($text){
		$date=extract_date($text);
		$time=extract_time($text);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		return date(TIME_FMT,$secs);
	}
}