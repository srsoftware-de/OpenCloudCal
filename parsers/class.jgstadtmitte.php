<?php
require_once 'dom_methods.php';

class JGStadtMitte{
	private static $base_url = 'http://jg-stadtmitte.de/';
	private static $event_list_page = 'calendar/action~agenda/page_offset~';

	public static function read_events(){
		$offset = 0;
		while (true) {
			$xml = load_xml(self::$base_url . self::$event_list_page.$offset);
			$parts = explode(' ', findElements($xml, SPAN, CLS, 'ai1ec-calendar-title',CONTENT)); // get calendar title, something like „Februar - März 2019“
			$year = array_pop($parts); // extract year
			$events = findElements($xml, DIV, CLS,'ai1ec-date');

			if (empty($events)) break;
			foreach ($events as $event) self::read_event($event,$year);
			$offset++;
		}
	}

	public static function read_event($ev_div,$year){

		$title = findElements($ev_div, SPAN, CLS, 'ai1ec-event-title',CONTENT);

		$description = findElements($ev_div, DIV, CLS, 'ai1ec-event-description', CONTENT);

		$start = static::startFrom($ev_div,$year);
		$end = endsWith($start, '00:00') ? date(TIME_FMT,parseDateTime(date_parse(str_replace('00:00', '23:59', $start)))) : null;
		$start = date(TIME_FMT,parseDateTime(date_parse($start)));

		$location = 'JG Stadtmitte, Johannisstraße 14, 07743 Jena';
		$coords = '50.92946, 11.58493';
		$tags = ['JG','JungeGemeinde','Stadtmitte','Jena'];
		$images = static::read_images($ev_div); //*/
		$source_url = static::$base_url.'calendar?start='.str_replace(' ','_',$start);
		$links = [$source_url];
		//debug(['source'=>$source_url,'title'=>$title,'start'=>$start,'end'=>$end,'description'=>$description,'location'=>$location,'coords'=>$coords,'tags'=>$tags,'images'=>$images,'links'=>$links]); return;
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

	private static function startFrom($ev_div,$year){
		$time_string = findElements($ev_div, DIV,CLS,'ai1ec-event-time',CONTENT);
		$parts = explode(' ', $time_string);

		$month = self::replaceMonth($parts[0]);
		$day = $parts[1];
		if ($day<10) $day = '0'.$day;

		if (strpos($time_string, 'ganztägig') !== false){
			$time = '00:00';
		} else $time = $parts[3];

		return "$year-$month-$day $time";
	}

	private static function replaceMonth($name){
		switch (strtoupper($name)){
			case 'JAN': return '01';
			case 'FEB': return '02';
			case 'MRZ': return '03';
			case 'APR': return '04';
			case 'MAI': return '05';
			case 'JUN': return '06';
			case 'JUL': return '07';
			case 'AUG': return '08';
			case 'SEP': return '09';
			case 'OKT': return '10';
			case 'NOV': return '11';
			case 'DEZ': return '12';
		}
		return $name;
	}
}
