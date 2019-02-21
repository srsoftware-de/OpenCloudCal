<?php
require_once 'dom_methods.php';

class FestungKoenigstein{
	private static $base_url = 'https://www.festung-koenigstein.de/';
	private static $event_list_page = 'de/veranstaltungskalender.html';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);

		$events = findElements($xml, DIV, CLS,'event');
		foreach ($events as $event) self::read_event($event);
	}

	public static function read_event($ev_div){
		$headline = findElements($ev_div, H2, null, null, SINGLE);
		$title = $headline->nodeValue;
		$source_url = findElements($headline, ANCHOR, LINK, null, VALUE);

		$description = findElements($ev_div, DIV, CLS, 'teaser', CONTENT);
		$start = static::startFrom($source_url);
		$end = static::endFrom($source_url);
		$location = 'Festung Königstein, 01824 Königstein';
		$coords = '50.91844, 14.05813';
		$tags = ['Festung','Königstein','Festung'];
		$images = static::read_images($ev_div);
		$links = findElements($ev_div, ANCHOR,LINK,null,VALUES);
		//debug(['source'=>$source_url,'title'=>$title,'start'=>$start,'end'=>$end,'description'=>$description,'location'=>$location,'coords'=>$coords,'tags'=>$tags,'images'=>$images]); return;
		$event = Event::get_imported($source_url);
		if ($event === null){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $description, $start, $end, $location, $coords,$tags,$links,$images,false);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($description);
			$event->set_start($start);
			$event->set_location($location);
			$event->set_coords($coords);
			foreach ($links as $link) $event->add_link($link);
			foreach ($tags as $tag) $event->add_tag($tag);
			foreach ($images as $image) $event->add_attachment($image);
			$event->save();
		}
	}

	private static function read_images($ev_div){
		$addresses = findElements($ev_div, IMAGE, SOURCE, null, VALUES);
		$images = [];
		foreach ($addresses as $address){
			$mime = guess_mime_type($address);
			if (strpos($mime,'image')!==false) $images[] = url::create(static::$base_url.$address,$mime);
		}
		return $images;
	}

	private static function startFrom($url){
		$times = parseParam($url,'times');
		$parts = explode(',', $times);
		return date(TIME_FMT,$parts[0]);
	}

	private static function endFrom($url){
		$times = parseParam($url,'times');
		$parts = explode(',', $times);
		return date(TIME_FMT,$parts[1]);
	}
}
