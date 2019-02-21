<?php
class Taeubchenthal{
	private static $base_url = 'http://www.taeubchenthal.com/';
	private static $event_list_page = 'programm';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);

		$anchors = $xml->getElementsByTagName('a');
		$event_urls =[];
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$href = $anchor->getAttribute('href');
			if (strpos($href,'/veranstaltungen/')!==false) $event_urls[]=$anchor->getAttribute('href');
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
		$location = 'Täubchenthal, Wachsmuthstrasse 1, 04229 Leipzig';
		$coords = '51.324621, 12.330507';
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
			$headings = $parent->getElementsByTagName('h4');
			foreach ($headings as $heading) {
				$text = trim($heading->nodeValue);
				if ($text == '') continue;
				$title .= ' : '.$text;
			}
		}

		return trim($title);
	}

	private static function read_description(DOMNode $content){
		$description = '';
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;

			$class = $div->getAttribute('class');
			if (strpos($class,'top-ma')===false) continue;
			$paragraphs = $div->getElementsByTagName('p');
			foreach ($paragraphs as $paragraph){
				$description .= '<p>'.$paragraph->ownerDocument->saveHTML($paragraph)."</p>\n";
			}
		}
		return $description;
	}

	private static function read_start($content){
		$day = null;
		$time = null;
		$headings = $content->getElementsByTagName('h1');
		foreach ($headings as $heading){
			$parent = $heading->parentNode;

			$paragraphs = $parent->getElementsByTagName('p');

			foreach ($paragraphs as $paragraph){
				$text = $paragraph->textContent;
				$pos = strpos($text,'Start');
				if ($pos === false) $pos = strpos($text,'Beginn');
				if ($pos === false) continue;
				$parts = explode(': ', $text);

				$time = trim(end($parts));
				if (strpos($time, ':')===false) $time=str_replace('Uhr', ':00', $time); // 22Uhr => 22:00
				$day = trim(reset(explode(':',$text)));
				break;
			}
			if ($day !== null) break;
		}
		$date=extract_date($day.date('Y'));
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);

		return date(TIME_FMT,$secs);
	}

	private static function read_tags($content){
		$headings = $content->getElementsByTagName('h1');
		$parent = null;
		foreach ($headings as $heading){
			$parent = $heading->parentNode;
			break;
		}
		$tags = ['Täubchenthal','Leipzig'];
		if ($parent === null) return $tags;
		$dds = $parent->getElementsByTagName('dd');
		foreach ($dds as $dd){
			$content = str_replace(['/',',',';'], ' ', $dd->textContent);
			if (strpos($content, 'EUR')!==false) continue;
			$parts = explode(' ',$content);
			foreach ($parts as $part){
				$part = trim($part);
				if ($part != '') $tags[] = $part;
			}
		}
		return $tags;
	}

	private static function read_links($content,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = [$url];
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if (strpos($class,'artist_links')===false) continue;
			$anchors = $div->getElementsByTagName('a');
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
			$attachments[] = url::create($address,$mime);
		}

		return $attachments;
	}



	private static function date($text){
		$date=extract_date($text);
		$time=extract_time($text);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		return date(TIME_FMT,$secs);
	}
}