<?php
class Moritzbastei{
	private static $base_url = 'http://www.moritzbastei.de';
	private static $event_list_page = '/de/programm';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$anchors = $xml->getElementsByTagName('a');
		$event_pages = array();
		foreach ($anchors as $anchor){
			$href = trim($anchor->getAttribute('href'));
			if (strpos($href,'event')!==false){
				$event_pages[]=$href;
			}
		}
		$event_pages = array_unique($event_pages);
		//print '<pre>'; print_r($event_pages); print '</pre>';
		foreach ($event_pages as $page){
			self::read_event(self::$base_url.$page);
		}
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);

		$title = self::read_title($xml);
		$description = self::read_description($xml);
		$start = self::date(self::read_start($xml));
		$location = 'Moritzbastei, Universitätsstraße 9, 04109 Leipzig';

		$coords = '51.337268, 12.379194';

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
		$headings = $xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		$divs = $xml->getElementsByTagName('div');
		$description = '';
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'ed_more_info') continue;
			return $div->nodeValue;
		}
		return $description;
	}

	private static function read_start($xml){
		global $db_time_format;
		$spans = $xml->getElementsByTagName('span');
		$text = '';
		foreach ($spans as $span){
			if (!$span->hasAttribute('class')) continue;
			$class = $span->getAttribute('class');
			if ($class != 'date-display-single') continue;
			$text.= ' '.$span->nodeValue;
			if (strpos($text,':') !== false) return trim($text);				
		}
		return null;
	}

	private static function read_tags($xml){
		global $db_time_format;
		$metas = $xml->getElementsByTagName('meta');
		$tags = array('Moritzbastei','Leipzig');
		foreach ($metas as $meta){
			if (!$meta->hasAttribute('name')) continue;
			$name = $meta->getAttribute('name');
			if ($name != 'keywords') continue;
			$keywords = $meta->getAttribute('content');
			$keywords = explode(', ', $keywords);
			foreach ($keywords as $keyword){
				$tag = trim($keyword);
				if (empty($tag)) continue;
				$tags[]=$tag;
			}
		}
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$divs = $xml->getElementsByTagName('div');
		$links = array();
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'ed_more_info') continue;

			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if ($anchor->hasAttribute('href')){
					$address = $anchor->getAttribute('href');
					if (strpos(guess_mime_type($address),'image')===false){
						$links[] = url::create($address,trim($anchor->nodeValue));
					}
				}
			}
		}
		return $links;
	}

	private static function read_images($xml){
		$images = $xml->getElementsByTagName('img');
		foreach ($images as $image){
			$address = $image->getAttribute('src');
			if (strpos($address,'events')===false) continue;
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