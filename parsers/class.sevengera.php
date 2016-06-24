<?php
class SevenGera{
	private static $base_url = 'http://www.darksidenight.de/';
	private static $event_list_page = 'veranstaltungen';

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
		$inhalte = $xml->getElementById('INHALTE');
		$divs = $inhalte->getElementsByTagName('div');
		$event_pages = array();
		foreach ($divs as $div){
			$class = $div->getAttribute('class');
			if ($class=='event'){
				self::read_event($div);
			}
		}
	}

	public static function read_event($div){

		$title = self::read_title($div);
		$description = self::read_description($div);
		$start = self::date(self::read_start($div));
		$location = 'SevenClub, Bahnhofsplatz 6, 07545 Gera';

		$coords = '50.884116, 12.078617';

		$tags = self::read_tags($title.$description);
		$links = self::read_links($div);
		$attachments = null;
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$source_url=self::read_facebook($div);
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

	private static function read_title($xml){
		$headings = $xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		return "";
	}

	private static function read_start($xml){
		global $db_time_format;
		$paragraphs = $xml->getElementsByTagName('p');
		$description = '';
		foreach ($paragraphs as $p){
			$class=$p->getAttribute('class');
			if ($class=='h1'){
				$start = substr($p->nodeValue,3);
				$keys = array('//','Uhr');
				$start = str_replace($keys, '', $start);
				foreach (self::$months as $name => $month){
					$start = str_replace(' '.$name, $month.'.', $start);
				} 				
				return $start;
			}
		}
		return null;
	}

	private static function read_tags($text){
		$tags =array('SevenClub','Gera');
		if (stripos($text,'80er') !== false) $tags[]='80er';
		if (stripos($text,'90er') !== false) $tags[]='90er';
		if (stripos($text,'Bad Taste') !== false) $tags[]='BadTaste';
		if (stripos($text,'Dark Side') !== false) $tags[]='schwarzesjena';
		if (stripos($text,'Party') !== false) $tags[]='Party';
		return $tags; 		
	}

	private static function read_links($div){
		$anchors = $div->getElementsByTagName('a');
		$links = array();
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');
				$text = trim($anchor->nodeValue);
				if (strpos($text, 'facebook.com')!==false){
					$text = 'Facebook';
				}
				$links[] = url::create($address,$text);
			}
		}
		return $links;
	}

	private static function read_facebook($div){
		$anchors = $div->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = trim($anchor->getAttribute('href'));
				if (strpos($address, 'facebook.com')!==false){
					return $address;
				}
			}
		}
		return "no id";
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