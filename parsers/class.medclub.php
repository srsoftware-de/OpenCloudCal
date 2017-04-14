<?php
require 'functions.php';

class MedClub{
	private static $base_url = 'http://www.med-club.de';
	private static $event_list_page = '/joomla/index.php/partyguide';
	
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
		$anchors = $xml->getElementsByTagName('a');
		$event_pages = array();
		foreach ($anchors as $anchor){
			$href = $anchor->getAttribute('href');
			if (strpos($href, 'guide/event')!==false){
				$event_pages[]=$href;				
			}
		}
		
		$event_pages = array_unique($event_pages);
		foreach ($event_pages as $page){
			self::read_event(self::$base_url.$page);
		}
	}
	
	public static function read_event($source_url){
		$xml = load_xml($source_url);
		$title = self::read_title($xml);
		$description = self::read_description($xml,$title);
		$start = self::date(self::read_start($xml));
		$end = self::date(self::read_end($xml));
		$location = self::read_location($xml);
		$coords = ($location == 'Med-Club@KuBa')?'50.93658, 11.59266':null;
		$tags = self::read_tags($xml);
		$links = self::read_links($xml,$source_url);
		$attachments = self::read_images($xml);
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$event = Event::get_imported($source_url);
		if ($event == null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, $end, $location, $coords,$tags,$links,$attachments,false);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($description);
			$event->set_start($start);
			$event->set_end($end);
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

	private static function read_description($xml,$title){
		$info_div = $xml->getElementById('submenu_eventinfo');
		$description = '';		
		$paragraphs = ($info_div === null)?array():$info_div->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			$text = trim($paragraph->nodeValue);
			if ($text == $title) {
				$description = '';
			} else {
				$description.=$text.'<br/>';
			}
		}
		return $description;
	}

	private static function read_start($xml){
		$title_div = $xml->getElementById('mat_title');
		if ($title_div === null) return null;
		$divs = $title_div->getElementsByTagName('div');
		$datestring= null;
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class') !== 'mat_event_date') continue;
			$datestring=trim($div->nodeValue);
		} 
		if ($datestring === null) return null;
		if (strpos($datestring,',')!==false) $datestring = explode(',',$datestring)[1];
		
		foreach (self::$months as $name => $month) $datestring = str_replace(' '.$name.' ', $month.'.', $datestring);

		return $datestring;
	}
	
	private static function read_end($xml){		
		return null;
	}

	private static function read_location($xml){
		$title_div = $xml->getElementById('mat_title');
		if ($title_div === null) return null;
		$divs = $title_div->getElementsByTagName('div');
		$location = null;
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class') !== 'mat_event_location') continue;
			$location=trim($div->nodeValue);
		}
		
		return $location;
	}
	
	private static function read_tags($xml){
		$tags = array('MedClub','Jena');

		$title_div = $xml->getElementById('mat_title');
		if ($title_div === null) return $tags;
		$divs = $title_div->getElementsByTagName('div');
		$cat_div = null;
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if ($div->getAttribute('class') !== 'mat_event_category') continue;
			$cat_div = $div;
		}
		if ($cat_div === null) return $tags;
		
		$anchors = $cat_div->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			$tag = trim($anchor->nodeValue);
			if ($tag == 'Partys') $tag='Party';
			$tags[]=$tag;
		}
		
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$info_div = $xml->getElementById('submenu_eventinfo');
		$anchors = $info_div->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');
				if (strpos(guess_mime_type($address),'image')===false){
					$links[] = url::create($address,trim($anchor->nodeValue));
				}
			}
		}
		return $links;
	}

	private static function read_images($xml){
		

		$info_div = $xml->getElementById('mat_event_details');
		$images = $info_div->getElementsByTagName('img');
		foreach ($images as $image){
			$address = $image->getAttribute('src');
			if (strpos($address,'://')===false) $address = self::$base_url.$address;
			$mime = guess_mime_type($address);
			$attachments[] = url::create($address,$mime);
		}
		return $attachments;
	}



	private static function date($text){
		global $db_time_format;
		if ($text == null) return null;
		$date=extract_date($text);
		$time=extract_time($text);
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		return date($db_time_format,$secs);
	}
}