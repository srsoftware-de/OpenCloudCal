<?php
class HellRaiser{
	private static $base_url = 'https://hellraiser-leipzig.de/';
	private static $event_list_page = 'alle-events';
	
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

		$section = $xml->getElementById('primary');
		$anchors = $section->getElementsByTagName('a');
		$event_urls =[];
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$href = $anchor->getAttribute('href');
			if (strpos($href,'#more')===false) continue;
			$parts = explode('#', $href);
			$event_urls[]=reset($parts);			
		}
		foreach ($event_urls as $url) self::read_event($url);
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);
		
		// next block: find content div
		$content = $xml->getElementById('primary');
		$title = self::read_title($content);
		$description = self::read_description($content);
		$start = self::read_start($content);
		$location = 'Hellraiser, Werkstättenstraße 4 04319 Leipzig/Engelsdorf';
		$coords = '51.339877, 12.460968';
		$tags = self::read_tags($content);
		$links = self::read_links($content,$source_url);
		
		$attachments = self::read_images($content);
		
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

	private static function read_title($content){
		$headings = $content->getElementsByTagName('h2');
		foreach ($headings as $heading) return trim($heading->nodeValue);		
		return null;		
	}

	private static function read_description(DOMNode $content){
		$description = '';
		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			if (!$paragraph->hasAttribute('style')) continue;
			$description .= '<p>'.$paragraph->ownerDocument->saveHTML($paragraph)."</p>\n";
		}
		return $description;
	}

	private static function read_start($content){
		global $db_time_format;
		
		$day = null;
		$time = null;
		
		$times = $content->getElementsByTagName('time');
		foreach ($times as $time_tag){
			$day = $time_tag->textContent;
		}
		foreach (self::$months as $name => $month) $day = str_replace(' '.$name.' ', $month.'.', $day);
		
		$paragraphs = $content->getElementsByTagName('p');
		
		foreach ($paragraphs as $paragraph){
			$text = $paragraph->textContent;
			$pos = strpos($text,'Uhr');
			if ($pos === false) continue;
			$text = substr($text, 0, $pos);
			$parts = explode(': ', $text);
			$time = trim(end($parts));
			if (strpos($time, ':')===false) $time.=':00';
			break;
		}
		$date=extract_date($day.date('Y'));
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		
		return date($db_time_format,$secs);
	}

	private static function read_tags($content){
		$tags = ['Hellraiser','Leipzig'];		
		return $tags;
	}

	private static function read_links($content,$source_url){
		$url = url::create($source_url,loc('event page'));	
		$links = [$url];
		$anchors = $content->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$text = $anchor->nodeValue;
			if ($text == 'Event') continue;
			if (strpos($text,'plusone')!==false) continue;
			
			$address = $anchor->getAttribute('href');
			if (strpos($address,'plusone')!==false) continue;
			if (strpos($address,'share')!==false) continue;
				
			if (strpos(guess_mime_type($address),'image')===false) $links[] = url::create($address,trim($text));
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



	private static function date($text){
		global $db_time_format;
		$date=extract_date($text);
		$time=extract_time($text);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		return date($db_time_format,$secs);
	}
}