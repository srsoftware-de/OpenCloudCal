<?php
class TheLondoner{
	private static $base_url = 'http://www.thelondoner.de';
	private static $event_list_page = '/programm';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$program = $xml->getElementById('programm');		
		$headlines = $program->getElementsByTagName('h2');
		foreach ($headlines as $headline){
			$anchors = $headline->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				self::read_event($anchor->getAttribute('href'));
			}
		}
	}
	
	private static function read_title($xml){
		$headlines = $xml->getElementsByTagName('h1');		
		foreach ($headlines as $headline){
			return $headline->nodeValue;
		}
		return null;
	}
	
	private static function read_description($xml){
		$sections = $xml->getElementsByTagName('section');
		foreach ($sections as $section){
			$text = '';
			if (!$section->hasAttribute('id')) continue;
			if ($section->getAttribute('id') != 'cont') continue;
			$paragraphs = $section->getElementsByTagName('p');
			foreach ($paragraphs as $para){
				$text.=$para->nodeValue."<br/>\n";
			}
			return $text;
		}
		return null;
	}
	
	private static function getMonth($m){
		switch (strtolower($m)){
			case 'jan': return 1;
			case 'feb': return 2;
			case 'mar': return 3;
			case 'apr': return 4;
			case 'mai': return 5;
			case 'jun': return 6;
			case 'jul': return 7;
			case 'aug': return 8;
			case 'sep': return 9;
			case 'okt': return 10;
			case 'nov': return 11;
			case 'dez': return 12;
		}
	}
	
	public static function read_event($source_url){
		$xml = load_xml($source_url);		
		$title = self::read_title($xml);
		
		$description = self::read_description($xml);
		
		$start = self::date(self::read_start($xml));
		$location = 'The Londoner - English Pub, Parkstraße 15, 99867 Gotha';
		
		$coords = '50.942422,10.70167';
		
		$tags = self::read_tags($xml);
		
		$links = self::read_links($xml);
		$attachments = self::read_images($xml);
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
			//foreach ($attachments as $attachment) $event->add_attachment($attachment);
			$event->save();
		}
	}


	private static function read_start($xml){
		$rows= $xml->getElementsByTagName('tr');
		
		foreach ($rows as $row){
			$row = $row->nodeValue;
			if (strpos($row, 'Wann')===false) continue;
			$row = trim(substr($row,8,18));
			return $row."<br/>\n";
		}
		return null;		
	}

	private static function read_tags($container){
		$tags = array('TheLondoner','english.Pub','Gotha');
		return $tags;
	}

	private static function read_links($xml){
		$sections = $xml->getElementsByTagName('section');
		$links = array();
		foreach ($sections as $section){
			$text = '';
			if (!$section->hasAttribute('id')) continue;
			if ($section->getAttribute('id') != 'cont') continue;
			$anchors = $section->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$address = $anchor->getAttribute('href');
				$text = $anchor->nodeValue;
				if (strpos($address, '://')===false){
					$address = self::$base_url.'/'.$address;
				}	
				$links[] = url::create($address,$text);
			}
		}
		return $links;
	}
	
	private static function read_images($xml){
		$articles = $xml->getElementsByTagName('article');
		$links = array();
		foreach ($articles as $article){
			$text = '';
			$images = $article->getElementsByTagName('img');
			foreach ($images as $image){
				if (!$image->hasAttribute('src')) continue;
				$address = $image->getAttribute('src');
				
				if (strpos($address, '://')===false){
					$address = self::$base_url.'/'.$address;
				}	
				$links[] = url::create($address,guess_mime_type($address));
			}
		}
		return $links;
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