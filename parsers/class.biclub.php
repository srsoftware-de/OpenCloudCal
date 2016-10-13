<?php
class BiClub{
	private static $base_url = 'http://www.bi-club.de';
	private static $event_list_page = '/programm';

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
		$event_pages = array();
		
		$xml = load_xml(self::$base_url . self::$event_list_page);
		
		$tables = $xml->getElementsByTagName('table');
		foreach ($tables as $table){ 
			$anchors = $table->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$href = trim($anchor->getAttribute('href'));
				if (strpos($href,'events') === false) continue;
				$event_pages[]=self::$base_url.$href;
			}
		}
		
		$lists = $xml->getElementsByTagName('li');
		$next_page = null;
		foreach ($lists as $li){
			if (!$li->hasAttribute('class')) continue;
			if ($li->getAttribute('class') != 'date-next') continue;
			$anchors = $li->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$next_page = $anchor->getAttribute('href');
				break 2;
			}
		}
		
		$xml = load_xml($next_page);
		
		$tables = $xml->getElementsByTagName('table');
		foreach ($tables as $table){ 
			$anchors = $table->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$href = trim($anchor->getAttribute('href'));
				if (strpos($href,'events') === false) continue;
				$event_pages[]=self::$base_url.$href;
			}
		}
		
		$event_pages=array_unique($event_pages);
		foreach ($event_pages as $page){
			self::read_event($page);
		}
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);

		$title = self::read_title($xml);
		
		$description = self::read_description($xml);
		$start = self::date(self::read_start($xml));
		$location = 'BiClub, Max-Planck-Ring 4, 98693 Ilmenau';

		$coords = '50.682690, 10.931450';

		$tags = self::read_tags($xml);
		
		$links = self::read_links($xml,$source_url);
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
		$content = $xml->getElementById('content');
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if (strpos($class,'body')===false) continue;
			$paras = $div->getElementsByTagName('p');
			$description = '';
			foreach ($paras as $para){
				$description.=$para->nodeValue.NL;
			}
			return trim($description);
		}
		return null;
	}

	private static function read_start($xml){
		$content = $xml->getElementById('content');
		$spans = $content->getElementsByTagName('span');
		foreach ($spans as $span){
			if (!$span->hasAttribute('class')) continue;
			if (!$span->hasAttribute('content')) continue;
			$class = $span->getAttribute('class');
			if (strpos($class,'date')===false) continue;
			$date = $span->nodeValue;
			$pos = strpos($date, '-');
			$date = trim(substr($date, 1+$pos));
			break;
		}
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if (strpos($div->getAttribute('class'),'einlass')===false) continue;
			$divs = $div->getElementsByTagName('div');
			foreach ($divs as $div){
				if (!$div->hasAttribute('class')) continue;
				if (strpos($div->getAttribute('class'),'item')===false) continue;
				return $date.' '.trim($div->nodeValue);
			}
		}
		return null;
	}

	private static function read_tags($xml){
		$content = $xml->getElementById('content');
		$divs = $content->getElementsByTagName('div');
		$tags = array('BiClub','Ilmenau');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if (strpos($class,'style')===false) continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				$tag=trim($anchor->nodeValue);
				$tags[]=$tag;
				if (strpos(strtolower($tag),'goth') !== false){
					$tags[]='schwarzesjena';
					$tags[]='gothic';
				}
			}
			break;
		}
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = array($url,);
		

		$content = $xml->getElementById('content');
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if (strpos($class,'body')===false) continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$href = $anchor->getAttribute('href');
				$links[] = url::create($href,$anchor->nodeValue);
			}
		}
	
		return $links;
	}

	private static function read_images($xml){
		$images = array();
		
		$content = $xml->getElementById('content');
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if (strpos($class,'thumbnail')===false) continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$href = trim($anchor->getAttribute('href'));
				$pos = strpos($href, '?');
				$mime = guess_mime_type($href);
				if ($pos !== false) $mime = guess_mime_type(substr($href,0, $pos));
				$images[] = url::create($href,$mime);
			}
		}
		return $images;
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