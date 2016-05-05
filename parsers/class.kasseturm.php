<?php
class KasseTurm{
	private static $base_url = 'http://www.kasseturm.de';
	private static $event_list_page = '/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$events = $xml->getElementById('mycarousel');
		$events = $events->getElementsByTagName('li');
		foreach ($events as $event){
			self::read_event($event);			
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

	public static function read_event($container){

		$title = self::read_span($container,'title')->nodeValue;
		$description = self::read_span($container,'description')->nodeValue;
		
		$start = self::date(self::read_start($container));
		$location = 'Kasseturm, Goetheplatz 10, 99423 Weimar';

		$coords = '50.981880, 11.326006';

		$tags = self::read_tags($container);
		$links = self::read_links($container);
		$attachments = self::read_images($container);
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$id=self::$base_url.'#'.str_replace(' ','+',$start);
		$event = Event::get_imported($id);
		if ($event == null){
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
		$date = self::read_span($container, 'date')->nodeValue;
		$time = str_replace('.', ':', self::read_span($container, 'time')->nodeValue);		
		return $date.' '.$time; 
		
	}

	private static function read_tags($container){
		$cats = trim(self::read_span($container, 'category')->nodeValue);
		if ($cats == 'Sonst.') {
			$tags = array();
		} else $tags = explode(' ', $cats);
		$tags[] = 'Kasseturm';
		$tags[] = 'Weimar';
		return $tags;
	}

	private static function read_links($container){
		$links = array();
		$description = self::read_span($container, 'description');
		$anchors = $description->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');
				if (strpos($address, 'mailto')!==false) continue;
				$text = trim($anchor->nodeValue);
				$link = url::create($address,$text);
				$links[] = $link;
			}
		}
		
		
		$facebook = trim(self::read_span($container, 'facebook')->nodeValue);		
		if ($facebook != '') $links[] = url::create($facebook,'Facebook');
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