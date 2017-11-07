<?php
class FourRooms{
	private static $base_url = 'http://fourooms.net';
	private static $event_list_page = '/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$headlines = $xml->getElementsByTagName('h2');
		foreach ($headlines as $headline){
			if (!$headline->hasAttribute('class')) continue;
			if (!$headline->getAttribute('class')=='ev2page-title') continue;
			$anchors = $headline->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				self::read_event($anchor->getAttribute('href'));
			}
		}
	}
	
	public static function read_span($container,$className){
		$spans = $container->getElementsByTagName('span');
		foreach ($spans as $span){			
			if ($span->hasAttribute('id') && $span->getAttribute('id') == $className){
				return $span;
			}
		}
		foreach ($spans as $span){			
			if ($span->hasAttribute('class')){
				$class = $span->getAttribute('class');
				if (strpos($class, $className)!==false){
					return $span;
				}
			}
		}
		return null;
	}
	
	private static function read_title($xml){
		$headlines = $xml->getElementsByTagName('h2');		
		foreach ($headlines as $headline){
			if (!$headline->hasAttribute('class')) continue;
			if (strpos($headline->getAttribute('class'),'title') === false) continue;
			return $headline->nodeValue;
		}
		return null;
	}
	
	private static function read_description($xml){
		$divs = $xml->getElementsByTagName('div');
		foreach ($divs as $div){
			$text = '';
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class') != 'event-text') continue;
			$paragraphs = $div->getElementsByTagName('p');
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
		$location = '4rooms, Täubchenweg 26 04317 Leipzig';

		$coords = '51.336121, 12.399969';
		
		$tags = self::read_tags($xml);
		
		$links = self::read_links($xml);
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
			//foreach ($attachments as $attachment) $event->add_attachment($attachment);
			$event->save();
		}
	}


	private static function read_start($xml){
		$divs = $xml->getElementsByTagName('div');
		
		$day = '';
		$month = '';
		$year = '';
		$time = '';
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
				
			$class = $div->getAttribute('class');
			if ($class == 'event-single-data') {
				$inner = $div->getElementsByTagName('div');
				foreach ($inner as $div){
					if (!$div->hasAttribute('class')) continue;
					$class = $div->getAttribute('class');
					switch ($class){
						case 'event-single-day': $day = trim($div->nodeValue); break;
						case 'event-single-month': $month = trim(str_replace('/', '', $div->nodeValue)); break;
						case 'event-single-year': $year = trim($div->nodeValue); break;
					}
				}
				if ($day == '') continue;
				if ($month == '') continue;
				if ($year == '') continue;
				$month = self::getMonth($month);
			} elseif ($class == 'evsng-cell-info'){
				$dummy = explode(':', $div->nodeValue);				
				$hour = $dummy[0];
				$minute = trim(substr($dummy[1],0,2));
				if (strpos($dummy[1],'pm')!==false) $hour+=12;
				$time = $hour.':'.$minute;
			}
		}
		if ($day == '') return null;
		if ($month == '') return null;
		if ($year == '') return null;
		if ($time == '') return null;
		return $day.'.'.$month.'.'.$year.' '.$time;		
	}

	private static function read_tags($container){
		$tags = array('4rooms','Leipzig');
		return $tags;
	}

	private static function read_links($xml){
		$links = array();
		$divs = $xml->getElementsByTagName('div');
		foreach($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class')!='event-text') continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if ($anchor->hasAttribute('href')){
					$address = $anchor->getAttribute('href');
					if (strpos($address, 'mailto')!==false) continue;
					if (strpos($address, '://')===false){
						$address = self::$base_url.'/'.$address;
					}
					$text = trim($anchor->nodeValue);
					$link = url::create($address,$text);
					$links[] = $link;
				}
			}			
		}		
		return $links;
		
	}
	
	private static function read_images($xml){
		$links = array();
		$anchors = $xml->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('class')) continue;
			if (strpos($anchor->getAttribute('class'),'evsng-zoom')===false) continue;
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');
				if (strpos($address, 'mailto')!==false) continue;
				if (strpos($address, '://')===false){
					$address = self::$base_url.'/'.$address;
				}					
				$link = url::create($address,guess_mime_type($address));
				$links[] = $link;
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