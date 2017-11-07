<?php
class Werk2{
	private static $base_url = 'http://www.werk-2.de';
	private static $event_list_page = '/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);

		$vorschau = $xml->getElementById('vorschaukasten');
		$anchors = $vorschau->getElementsByTagName('a');
		$event_urls = [];
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$event_urls[] = self::$base_url.$anchor->getAttribute('href');
		}		
		foreach ($event_urls as $url) self::read_event($url);
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);
		
		// next block: find content div
		$content = $xml->getElementById('main');
		$title = self::read_title($content);
		$description = self::read_description($content);
		$start = self::read_start($source_url,$content);
		
		$location = 'WERK 2 - Kulturfabrik Leipzig e.V., Kochstr. 132, 04277 Leipzig';
		$coords = '51.310107, 12.371393';
		$tags = self::read_tags($content);
		$links = self::read_links($content,$source_url);
		
		$attachments = self::read_images($content);
		
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

	private static function read_title($content){
		$parent = null;
		$headings = $content->getElementsByTagName('h1');
		$title = '';
		foreach ($headings as $heading){
			$title =$heading->nodeValue;
			$parent = $heading->parentNode;
			break;
		}
		
		if ($parent !== null){
			$headings = $parent->getElementsByTagName('h2');
			foreach ($headings as $heading) {
				$text = trim($heading->nodeValue);
				if ($text == '') continue;
				$title .= ' | '.$text;
			}
		}
		
		return trim($title);		
	}

	private static function read_description(DOMNode $content){
		$description = '';
		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			if (!$paragraph->hasAttribute('class')) continue;
			
			$class = $paragraph->getAttribute('class');
			if (strpos($class,'beschreibung')===false) continue;
			$description .= '<p>'.$paragraph->ownerDocument->saveHTML($paragraph)."</p>\n";				
		}
		return $description;
	}

	private static function read_start($url,$content){
		global $db_time_format;
		
		$day = substr(end(explode('/',$url)),0,10);
		$time = null;

		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			if (!$paragraph->hasAttribute('class')) continue;
				
			$class = $paragraph->getAttribute('class');
			if (strpos($class,'infos')===false) continue;
			$description = $paragraph->textContent;
			$parts = explode(', ', $description);
			foreach ($parts as $part){
				$pos = strpos($part, 'Start');
				if ($pos === false) $pos = strpos($part, 'Begin');
				if ($pos === false) continue;
				$time = trim(end(explode(': ', $part)));
				if (strpos($time, ':')===false) $time = str_replace([' Uhr','Uhr'], ':00', $time);
				break;
			}
			if ($time !== null) break;
		}
		
		$datestring=date_parse($day.' '.$time);
		$secs=parseDateTime($datestring);
		
		return date($db_time_format,$secs);
	}

	private static function read_tags($content){
		$tags = ['Werk2','Leipzig'];
		return $tags;
	}

	private static function read_links($content,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = [$url];
		
		
		$description = '';
		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			if (!$paragraph->hasAttribute('class')) continue;
			$class = $paragraph->getAttribute('class');
			if ($class!='links') continue;

			$anchors = $paragraph->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$address = $anchor->getAttribute('href');
				$links[] = url::create($address,trim($anchor->nodeValue));
			}
		}
		
		return $links;
	}

	private static function read_images($content){
		$images = $content->getElementsByTagName('img');
		$attachments = array();

		foreach ($images as $image){
			$address = $image->getAttribute('src');
			$mime = guess_mime_type($address);
			$attachments[] = url::create(self::$base_url.$address,$mime);
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