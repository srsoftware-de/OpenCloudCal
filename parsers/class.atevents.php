<?php
class AtEvents{
	private static $base_url = 'http://www.at-party.de';
	private static $event_list_page = '/veranstaltungen-partydates';
	
	private static $months = array(
			'Jan'=>'01',
			'Feb'=>'02',
			'Mar'=>'03',
			'Apr'=>'04',
			'May'=>'05',
			'Jun'=>'06',
			'Jul'=>'07',
			'Aug'=>'08',
			'Sep'=>'09',
			'Oct'=>'10',
			'Nov'=>'11',
			'Dec'=>'12');
	
	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$events_div = $xml->getElementById('evcal_list');
		$events = $events_div->childNodes;
		foreach ($events as $event){
			if ($event->nodeType == XML_TEXT_NODE) continue;
			if (!$event->hasAttribute('class')) continue;
			if (strpos($event->getAttribute('class'),'event') === false) continue;
			self::read_event($event);
		}
	}
	
	public static function read_event($content){
		
		$title = self::read_title($content);
		$description = self::read_description($content);
		$start=parseDate(self::read_start($content));
		$location = self::read_location($content);
		$source_url = self::read_source($content);
		$coords = null;
		$tags = array();
		switch ($location){
			case 'Club Seven':
			case 'Seven Gera':
				$location = 'Seven Club, Bahnhofsplatz 6, 07545 Gera';
				$coords = '50.884116, 12.078617';
				$tags = array('Gera','SevenClub');
				break;
			case 'Havanna Lounge Gera':
				$location = 'Havanna Lounge, De-Smit-Straße 2, 07545 Gera';
				$coords = '50.879306, 12.078275';
				$tags = array('Gera','HavannaLounge');
				break;
			case 'Club Qui':
				$location = 'Club Qui, De-Smit-Straße 2, 07545 Gera';
				$coords = '50.879306, 12.078275';
				$tags = array('Gera','ClubQui');
				break;
			case 'Trash Gera':
				$location = 'Trash Bar, Bahnhofsplatz 8, 07545 Gera';
				$coords = '50.883696, 12.077655';
				$tags = array('Gera','ThrashBar');
				break;
			case 'Music Hall Gera':
				$location = 'Music Hall, Heinrichstraße 49, 07545 Gera';
				$coords = '50.872052, 12.075023';
				$tags = array('Gera','MusicHall');
				break;
			default:
				$tags = array('unbekannte.location');	
		}
	
		$links = array();
		$links[] = url::create(self::$base_url . self::$event_list_page,'Event-Seite');
		$links[] = url::create($source_url,'Facebook-Event');
		$attachments = self::read_images($content);
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
	
	public static function read_source($content){
		if ($content->hasAttribute('itemtype')){
			return $content->getAttribute('itemtype');
		}
		return null;
	}
	
	public static function read_title($content){
		$spans = $content->getElementsByTagName('span');
		foreach ($spans as $span){
			if (!$span->hasAttribute('class')) continue;
			if (strpos($span->getAttribute('class'),'title') === false) continue;
			return $span->nodeValue;
		}
		return null;
	}
	
	public static function read_description($content){
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if (strpos($div->getAttribute('class'),'eventon_desc_in') === false) continue;
			return $div->nodeValue;
		}
		return null;
	}
	
	public static function read_start($content){
		$ems = $content->getElementsByTagName('em');
		foreach ($ems as $em){
			if (!$em->hasAttribute('class')) continue;
			if (strpos($em->getAttribute('class'),'evcal_time') === false) continue;
			$date = $em->nodeValue;
			foreach (self::$months as $month => $num){
				$date=str_replace(' '.$month.' ', '.'.$num.'.', $date);
			} 
			return trim(substr(str_replace(' - ',' ',$date),0,16));
		}
		return null;
	}
	
	public static function read_location($content){
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if (strpos($div->getAttribute('class'),'evo_location') === false) continue;
			$paragraphs = $div->getElementsByTagName('p');
			foreach ($paragraphs as $p){
				return trim($p->nodeValue);
			}
		}
		return null;
	}
	
	public static function read_images($content){
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			if (strpos($div->getAttribute('class'),'evcal_evdata_img') === false) continue;
			if (!$div->hasAttribute('style')) continue;
			$style = $div->getAttribute('style');
			$pos = strpos($style, 'http');
			if ($pos < 0) continue;
			$end = strpos($style,')',$pos);
			if ($end<0) continue;
			$address = substr($style,$pos,$end-$pos);
			$pos = strpos($address, '?');
			$url = ($pos>0)?substr($address,0,$pos):$address;
			return array(url::create($address,guess_mime_type($url)));
		}
		return null;
	}
	
}