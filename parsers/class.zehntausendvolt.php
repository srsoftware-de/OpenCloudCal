<?php
class ZehnTausendVolt{
	private static $base_url = 'http://www.10000volt.de';
	private static $event_list_page = '/category/events/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);

		$event_list = $xml->getElementById('content');

		$anchors = $event_list->getElementsByTagName('a');
		$event_urls =[];
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')) $event_urls[]=$anchor->getAttribute('href');
		}

		foreach ($event_urls as $url) self::read_event($url);
	}

	public static function read_event($source_url){

		$xml = load_xml($source_url);

		// next block: find content div
		$content = $xml->getElementById('content');
		$title = self::read_title($content);
		$description = self::read_description($content);
		$start = self::read_start($content);
		$location = 'Kulturzentrum "Trafo", Nollendorfer Str. 30, 07743 Jena';
		$coords = '50.936165, 11.587579';
		$tags = self::read_tags($content);
		$links = self::read_links($content,$source_url);

		$images = self::read_images($content);

		$event = Event::get_imported($source_url);
		if ($event === null){
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
			foreach ($images as $attachment) $event->add_attachment($attachment);
			$event->save();
		}
	}

	private static function read_title($content){
		$headings = $content->getElementsByTagName('h1');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($content){
		$description = '';
		$divs = $content->getElementsByTagName('div');
		$description = '';

		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class') != 'post-content') continue;
			$paragraphs = $div->getElementsByTagName('p');
			foreach ($paragraphs as $para){
				$text = trim(str_replace("\xc2\xa0",' ',$para->nodeValue));
				if ($text == '') continue;
				$description .= "<p>$text</p>";
			}
		}
		return $description;
	}

	private static function read_start($content){
		$spans = $content->getElementsByTagName('span');
		$day = '';
		foreach ($spans as $span){
			if (!$span->hasAttribute('class')) continue;

			switch ($span->getAttribute('class')){
				case 'day-month':
					$day = $span->textContent.'.'.$day;
					break;
				case 'year':
					$day = $day.$span->textContent;
					break;
			}
		}

		$time = null;
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class')!='date-box') continue;
			$paragraphs = $div->getElementsByTagName('p');
			foreach ($paragraphs as $p){
				$text = $p->textContent;
				if (strpos($text,'Begin')===0) $time=substr($text,8);
			}
		}

		$date=extract_date($day);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);

		return date(TIME_FMT,$secs);
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