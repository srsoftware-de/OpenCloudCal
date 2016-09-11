<?php
class VolkshausJena{
	private static $base_url = 'http://www.volkshaus-jena.de';
	private static $event_list_page = null;

	public static function read_events(){
		$xml = load_xml(self::$base_url);
		$anchors = $xml->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			$text = $anchor->nodeValue;
			if ($text == 'Programm'){
				self::$event_list_page = $anchor->getAttribute('href');
				break;
			}
		}
		if (self::$event_list_page == null) return;
		$xml = load_xml(self::$event_list_page.'?max=100');
		$events = $xml->getElementsByTagName('div');
		$event_pages = array();
		foreach ($events as $event){
			if (!$event->hasAttribute('class')) continue;
			$class = $event->getAttribute('class');
			if (strpos($class, 'event_box')===false) continue;
			self::read_event($event);
		}
	}
	
	private static function get_event_page($xml){
		$anchors = $xml->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('href')) continue;
			$href = $anchor->getAttribute('href');
			if (strpos($href,'veranstaltungskalender')===false) continue;
			return self::$base_url.$href;
		}
		return null;
	}

	public static function read_event($xml){
		$source_url = self::get_event_page($xml);
		if ($source_url == null) return;
		$event_xml = load_xml($source_url);
		$title = self::read_title($xml);
		$description = self::read_description($event_xml);
		$start = self::date(self::read_start($event_xml));
		$location = 'Volkshaus, Carl-Zeiß-Platz 15, 07743 Jena';

		$coords = '50.927331, 11.579837';

		$tags = self::read_tags($xml);
		$links = self::read_links($event_xml,$source_url);
		$attachments = self::read_images($event_xml,self::$base_url);
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
		$headings = $xml->getElementsByTagName('h3');
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
			if ($div->getAttribute('class') != 'absatz') continue;
			$paragraphs = $div->getElementsByTagName('p');
			foreach ($paragraphs as $paragraph){
				$text=trim($paragraph->nodeValue);
				if (empty($description) && empty($text)) continue;				
				$description.=$text.'<br/>';
			}
		}
		return $description;
	}

	private static function read_start($xml){
		global $db_time_format;
		$bolds = $xml->getElementsByTagName('b');
		$description = '';
		foreach ($bolds as $bold){
			$text = trim($bold->textContent);
			if (preg_match('/\d\d.\d\d.\d\d\d\d | \d\ð:\d\d/',$text)){
				return $text;
			}
		}
		return null;
	}

	private static function read_tags($xml){
		$tags = array('Volkshaus.Jena','Jena');
		$paragraphs = $xml->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			if (!$paragraph->hasAttribute('class')) continue;
			$class = $paragraph->getAttribute('class');
			if (strpos($class, 'kategorie')===false) continue;
			$text = trim($paragraph->textContent);
			$words = explode('/',$text);
			foreach ($words as $word){
				$word = trim($word);
				if (!empty($word)) $tags[]=$word;
			}
		}
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$articles = $xml->getElementsByTagName('article');
		$url = url::create($source_url,loc('event page'));	
		$links = array($url,);
		return $links;
	}

	private static function read_images($xml,$base){
		$divs = $xml->getElementsByTagName('div');			
		$attachments = array();
		foreach ($divs as $div){
			if (!$div->hasAttribute('id')) continue;			
			if ($div->getAttribute('id') != 'header_pict_parade') continue;
			$images = $div->getElementsByTagName('img');
			foreach ($images as $image){
				$address = $image->getAttribute('src');
				if (strpos($address, '://')===false) $address=$base.$address;
				$mime = guess_mime_type($address);
				$attachments[] = url::create($address,$mime);
			}
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