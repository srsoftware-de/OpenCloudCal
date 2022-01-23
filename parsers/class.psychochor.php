<?php
require_once 'dom_methods.php';

class Psychochor{
	private static $base_url = 'http://psycho-chor.de/';
	private static $event_list_page = 'events';
	private static $cities = array('Erfurt','Jena', 'Weimar');

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		
	    $links = findElements($xml, ANCHOR, 'itemprop', 'url');
	    foreach ($links as $link){
	        if ($link->hasAttribute(LINK)) {
	            $url = $link->getAttribute(LINK);
	            if (strpos($url, '/termine/') !== false) self::read_event($url);
	        }
	        
		}
	}

	public static function read_event($source_url){
	    //debug($source_url);
	    $xml = load_xml($source_url);
	    $title = self::read_title($xml);
	    $description = self::read_description($xml);
	    $start = self::read_start($xml);
	    $location = self::read_location($xml);
		$coords = self::read_coords($xml);
		$tags = self::read_tags($xml);
		$links = self::read_links($xml, $source_url);
		$attachments = self::read_images($xml);
		
		//print 'Titel:' . $title . NL . 'Description: '.$description . NL . 'start: '.$start . NL . 'location: '.$location . NL . 'coords: '.print_r($coords,true) . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
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
	    
	    $prefix = '';
	    $spans = findElements($xml, SPAN, CLS, 'canceled');
	    if ($spans) $prefix = 'Abgesagt: ';
	    
	    $spans = findElements($xml, SPAN, CLS, 'evcal_event_title');
	    foreach ($spans as $span){
	        return $prefix . trim($span->nodeValue);
	    }
		return null;
	}

	private static function read_description($xml){
	    $divs = findElements($xml, DIV, 'itemprop', 'description');
	    if ($divs) foreach ($divs as $div){
	        $text = '';
	        $paragraphs = findElements($div, PARAGRAPH);
	        foreach ($paragraphs as $p){
	            $val = trim($p->nodeValue);
	            if ($val) $text .= $val . NL;
	        }
	        
	        return $text;
	    }
		return null;
	}
	
	private static function read_start($xml){
	    $year = null;
	    $ems = findElements($xml, EM, CLS, 'year');
	    foreach ($ems as $em) $year = $em->nodeValue;
	    
	    $month = null;
	    $ems = findElements($xml, EM, CLS, 'month');
	    foreach ($ems as $em) $month = MONTHS_DE_SHORT[strtolower($em->nodeValue)];
	    
	    $day = null;
	    $ems = findElements($xml, EM, CLS, 'date');
	    foreach ($ems as $em) $day = $em->nodeValue;
	    
	    $time = null;
	    $ems = findElements($xml, EM, CLS, 'time');
	    foreach ($ems as $em) $time = $em->nodeValue;
	    if ($time === 'Ganztägig') $time = '9:00';
	    
	    return $year . '-' . $month . '-' . $day . ' ' . $time;
	}
	
	private static function read_location($xml){
        $ems = findElements($xml, EM, CLS, 'evcal_location');
        if ($ems) foreach ($ems as $em) return trim($em->nodeValue);
	    return null;
	}
	
	private static function read_coords($xml){
	    $lat = null;
	    $lon = null;
	    $iframes = findElements($xml, 'iframe');
	    if ($iframes) foreach ($iframes as $iframe) {
	        if ($iframe->hasAttribute('data-src-cmplz')){
	            $map_url = $iframe->getAttribute('data-src-cmplz');
	            $parts = explode('!', $map_url);
	            foreach ($parts as $part){
	                if (startsWith($part, '2d')) $lon = substr($part, 2);
	                if (startsWith($part, '3d')) $lat = substr($part, 2);
	            }	            
	            $coords = ['lat'=>$lat,'lon'=>$lon];
	            //debug($coords,1);
	            return $coords;
	        }
	    }
	    
	    return null;
	}

	private static function read_tags($xml){
	    $tags = ['PsychoChor'];
	    $ems = findElements($xml, EM, 'data-filter', 'event_type');
	    foreach ($ems as $em) $tags[] = $em->nodeValue;
	    
	    $spans = findElements($xml, SPAN, CLS, 'evcal_event_subtitle');
	    if ($spans) foreach ($spans as $span) $tags[] = $span->nodeValue;
	    return $tags;
	}

	private static function read_links($xml,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = [$url];
		
		$div = $xml->getElementById('evcal_list');
		$anchors = findElements($div, ANCHOR);
		foreach ($anchors as $a){
		    if ($a->hasAttribute(LINK)){
		        $url = $a->getAttribute(LINK);
		        if ($url == $source_url) continue;
		        $tx = $a->nodeValue;
		        $links[] = url::create($url,$tx);
		    }
		}
		
		return $links;
	}

	private static function read_images($xml){
        $images = [];	    
	    $div = $xml->getElementById('evcal_list');
	    $imgs = findElements($div, IMAGE);
	    if ($imgs) foreach ($imgs as $img){
	        if ($img->hasAttribute(SOURCE)){
	            $address = $img->getAttribute(SOURCE);
	            $mime = guess_mime_type($address);
	            $images[] = url::create($address,$mime);
	        }
	    }	    
	    return $images;
	}
}