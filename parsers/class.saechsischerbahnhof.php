<?php
class SaechsischerBahnhof{
	private static $base_url = 'http://club.xn--schsischer-bahnhof-ltb.de';
	private static $event_list_page = '/index.php/programm';
	
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
		
		$agenda = $xml->getElementById('icagenda');
		if ($agenda === null) {
			http_response_code(404);
			die('Website not available: '.self::$base_url);			
		}
		$links = $agenda->getElementsByTagName('a');
		$event_pages = array();
		foreach ($links as $link){
			$href = trim($link->getAttribute('href'));
			if (strpos($href,'programm/')!==false){
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
		$location = 'Sächsischer Bahnhof, Erfurtstraße 19, 07545 Gera';

		$coords = '50.869216, 12.079855';

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
		$description=$xml->getElementById('detail-desc');
		$description = $xml->saveHTML($description);
		$description = implode("\n",array_filter(array_map('trim',explode("\n",$description))));
		$breaks = array('<br>');
		$description = str_replace($breaks,"\n",$description);
		
		return strip_tags($description);
	}

	private static function read_start($xml){
		global $db_time_format;
		$divs = $xml->getElementsByTagName('div');
		$description = '';
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'details') continue;
			$raw = $div->nodeValue;
			$start = str_replace(array('Daten:','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag','Sonntag',','), '', $raw);
			foreach (self::$months as $name => $month){
				$start = str_replace(' '.$name.' ', $month.'.', $start);
			}			
			return trim($start);
		}
		return null;
	}

	private static function read_tags($xml){
		return array('Sächsischer.Bahnhof','Gera');
	}

	private static function read_links($xml,$source_url){
		$description=$xml->getElementById('icagenda');
		$url = url::create($source_url,loc('event page'));	
		$links = array($url,);
		$anchors = $description->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');				
				if (strpos(guess_mime_type($address),'image')===false){
					if (strpos($address, 'tmpl=')!==false) continue;
					if (strpos($address,'http')===false){
						$address = self::$base_url.$address;
					}						
					$links[] = url::create($address,trim($anchor->nodeValue));
				}
			}
		}
		return $links;
	}

	private static function read_images($xml){
		$description=$xml->getElementById('icagenda');
		$attachments = array();
		$images = $description->getElementsByTagName('img');
		foreach ($images as $image){
			$address = $image->getAttribute('src');
			if (strpos($address,'http')===false){
				$address = self::$base_url.$address;
			}
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