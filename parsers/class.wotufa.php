<?php
class Wotufa{
	private static $base_url = 'http://wotufa.de';
	private static $event_list_page = '/eventkalender/kalender.php';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$tables = $xml->getElementsByTagName('table');
		$event_pages = array();
		foreach ($tables as $table){
			$class = $table->getAttribute('class');
			if (strpos($class, 'kalList')!==false){
				$links = $table->getElementsByTagName('a');
				foreach ($links as $link){
					$href = trim($link->getAttribute('href'));					
					if (strpos($href,'detail')!==false){
						$event_pages[]=$href;
					}
				}
				break;
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
		$description = self::read_description($xml);
		$start = self::date(self::read_start($xml));
		$location = 'Wotufa-Saal, Ziegenrücker Straße 6, 07806 Neustadt an der Orla';

		$coords = '50.732068, 11.745464';

		$tags = array('Wotufa','Neustadt.Orla');
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
		$table_cells = $xml->getElementsByTagName('td');
		$next = false;
		foreach ($table_cells as $cell){
			$content = trim($cell->nodeValue);
			if ($next) return $content;
			if ($content == 'Veranstaltung') $next=true;
		}
		return null;
	}

	private static function read_description($xml){
		$table_cells = $xml->getElementsByTagName('td');
		$next = false;
		foreach ($table_cells as $cell){
			$content = trim($cell->nodeValue);
			if ($next) return $content;
			if ($content == 'Details') $next=true;
		}
		return null;
	}

	private static function read_start($xml){
		$table_cells = $xml->getElementsByTagName('td');
		$day = null;
		$time = null;
		$day_found = false;
		$time_found = false;
		foreach ($table_cells as $cell){
			$content = trim($cell->nodeValue);
			if ($day_found) {
				$day = trim(substr($content,0,10));
				$day_found=false;
			}
			if ($time_found){
				$time = trim(substr($content,0,5));
				$time_found = false;
			}
			if ($content == 'Datum') $day_found=true;
			if ($content == 'Zeit') $time_found=true;
		}
		return $day.' '.$time;
	}

	private static function read_links($xml,$source_url){
		// TODO: nicht implementiert, da zur Zeit der Implementierung keine Links auf der Event-Seite waren
		return null;
	}

	private static function read_images($xml){
		$imgs = $xml->getElementsByTagName('img');
		$images = array();
		foreach ($imgs as $img){
			$url = $img->getAttribute('src');			
			if (strpos($url,'grafik')!==false) continue;
			$mime = guess_mime_type($url);
			$images[]=url::create($url,$mime);
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