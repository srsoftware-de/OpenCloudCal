<?php
class KasseTurm{
	private static $base_url = 'http://www.kasseturm.de';
	private static $event_list_page = '/events';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);

		$events = array();
		$divs = $xml->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if (strpos($class,'eventHeader')!==false){
				$header = $div;
			} elseif (strpos($class,'eventBody')!==false){
				$body = $div;
				$events[] = array('header'=>$header,'body'=>$body);
			}
		}

		foreach ($events as $event){
			self::read_event($event['header'],$event['body']);
		}
	}

	public static function get_element($container,$type,$className){
		$spans = $container->getElementsByTagName($type);
		foreach ($spans as $span){
			if ($span->hasAttribute('id') && $span->getAttribute('id') == $className) return $span;
		}
		foreach ($spans as $span){
			if ($span->hasAttribute('class')){
				$class = $span->getAttribute('class');
				if (strpos($class, $className)!==false) return $span;
			}
		}
		return null;
	}

	public static function read_event($header,$body){
		$title = self::get_element($header,'span','eventTitle')->nodeValue;

		$subtitle = self::get_element($header,'span','eventSubtitle');
		if ($subtitle !== null) $title.=' - '.$subtitle->nodeValue;
		print_r(['title'=>$title]);
		$description = self::get_element($body,'div','eventDescription');
		$description = $description===null?'':self::get_element($description, 'span', 'eventRight')->nodeValue;
		print_r(['descr'=>$description]);
		$start = self::date(self::read_start($header));
		$location = 'Kasseturm, Goetheplatz 10, 99423 Weimar';

		$coords = '50.981880, 11.326006';

		$tags = self::read_tags($header);
		$links = self::read_links($body);
		$attachments = self::read_images($body);
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$id=self::$base_url.'#'.str_replace(' ','+',$start);
		$event = Event::get_imported($id);
		if ($event === null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, null, $location, $coords,$tags,$links,$attachments,false);
			$event->mark_imported($id);
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


	private static function read_start($container){
		$date = self::get_element($container,'span','eventDatee')->nodeValue;
		$time = self::get_element($container,'span','eventTime')->nodeValue;
		return $date.' '.$time;

	}

	private static function read_tags($container){
		$cats = trim(self::get_element($container,'span','eventCity')->nodeValue);
		if ($cats == 'Sonstiges') {
			$tags = array();
		} else $tags = explode(' ', $cats);
		$tags[] = 'Kasseturm';
		$tags[] = 'Weimar';
		return $tags;
	}

	private static function read_links($container){
		$links = array();
		$organizer = self::get_element($container,'div', 'eventOrganizer');
		if ($organizer === null) return $links;
		$anchors = $organizer->getElementsByTagName('a');
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

		return $links;
	}

	private static function read_images($container){
		$imgs = $container->getElementsByTagName('img');
		$images = array();
		foreach ($imgs as $img){
			if ($img->hasAttribute('src')){
				$address = $img->getAttribute('src');
				if (strpos($address, 'placeholder')!==false) continue;
				$mime = guess_mime_type($address);
				$image = url::create(self::$base_url.'/'.$address,$mime);
				$images[] = $image;
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