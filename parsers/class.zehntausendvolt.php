<?php
class ZehnTausendVolt{
	private static $base_url = 'http://www.10000volt.de';
	private static $event_list_page = '/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);

		$anchors = $xml->getElementsByTagName('a');
		$event_urls =[];
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$text = trim($anchor->textContent);
			if (strpos($text, 'MOAR')===false) continue;
			$href = $anchor->getAttribute('href');
			$event_urls[]=$anchor->getAttribute('href');
		}
		
		foreach ($event_urls as $url) self::read_event($url);
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);
		
		// next block: find content div
		$content = $xml->getElementById('primaryContainer');
		$title = self::read_title($content);
		$description = self::read_description($xml->getElementById('text_auszug'));
		$start = self::read_start($xml);
		$location = 'Kulturzentrum "Trafo", Nollendorfer Str. 30, 07743 Jena';
		$coords = '50.936165, 11.587579';
		$tags = self::read_tags($content);
		$links = self::read_links($xml->getElementById('text_auszug'),$source_url);
		
		$images = self::read_images($xml->getElementById('cover'));
		
		$event = Event::get_imported($source_url);
		if ($event == null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, null, $location, $coords,$tags,$links,$images,false);
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
		$headings = $content->getElementsByTagName('h5');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;		
	}

	private static function read_description(DOMNode $content){
		$description = '';
		$paragraphs = $content->getElementsByTagName('p');
		$description = '';
		foreach ($paragraphs as $para){
			$text = trim(str_replace("\xc2\xa0",' ',$para->nodeValue));
			if ($text == '') continue;
			$description .= "<p>$text</p>";
		}
		return $description;
	}

	private static function read_start($xml){
		global $db_time_format;
		
		$day = null;
		$time = null;
		
		$tag_div = $xml->getElementById('tag');
		$month_div = $xml->getElementById('monat');
		$day = $tag_div->textContent.'.'.getMonth($month_div->textContent).'.';
		
		$time_div = $xml->getElementById('abendkasse');
		$time_text = $time_div->textContent;
		$parts = explode('/',$time_text);
		foreach ($parts as $part){
			if (strpos($part,'Begin')!==false || strpos($part,'Start')!==false){
				$time = end(explode(': ',$part));
				if (strpos($time,':')===false) $time = str_replace([' Uhr','Uhr'],':00',$time);
			}
		} 
		
		$date=extract_date($day.date('Y'));
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		
		// if day has passed by this year, it should lie in the next year
		if ($secs < time()){
			$date=extract_date($day.(date('Y')+1));
			$datestring=date_parse($date.' '.$time);
			$secs=parseDateTime($datestring);
		}
		
		return date($db_time_format,$secs);
	}

	private static function read_tags($content){
		return ['Kulturzentrum','Trafo','Jena'];
	}

	private static function read_links($content,$source_url){
		$url = url::create($source_url,loc('event page'));	
		$links = [$url];
		$anchors = $content->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$address = $anchor->getAttribute('href');
			$links[] = url::create($address,trim($anchor->nodeValue));
		}		
		return $links;
	}

	private static function read_images($content){
		$images = $content->getElementsByTagName('img');
		$attachments = array();

		foreach ($images as $image){
			$address = $image->getAttribute('src');
			$mime = guess_mime_type($address);
			$attachments[] = url::create($address,$mime);
		}
	
		return $attachments;
	}
}