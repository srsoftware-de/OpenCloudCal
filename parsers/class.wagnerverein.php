<?php
class WagnerVerein{
	private static $base_url = 'http://www.wagnerverein-jena.de/';
	private static $event_list_page = '?page_id=52';
	
	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$tables = $xml->getElementsByTagName('table');
		$event_pages = array();
		foreach ($tables as $table){
			$class = $table->getAttribute('class');
			if (strpos($class, 'events')!==false){
				$links = $table->getElementsByTagName('a');
				foreach ($links as $link){
					$href = trim($link->getAttribute('href'));
					if (strpos($href,'event')!==false){
						$event_pages[]=$href;
					}
				}
				break;
			}
		}
		
		
		print_r($event_pages); die();
		foreach ($event_pages as $page){
			self::read_event(self::$base_url . $page);
		}
	}
	
	public static function read_event($source_url){
		$xml = load_xml($source_url);
		
		$title = self::read_title($xml);
		$description = self::read_description($xml);
		$start = self::date(self::read_info($xml,'fa-calendar'));
		$location = self::read_info($xml,'fa-building');
		
		$coords = null;		
		if (strtoupper($location) == 'ROSENKELLER'){
			$coords = '50.929463, 11.584644';
		}
		
		$tags = self::read_tags($xml);

		$links = self::read_links($xml);
		$attachments = self::read_attachments($xml);
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
		$title_container = $xml->getElementById('page-title');
		$list_elements = $title_container->getElementsByTagName('li');		
		foreach ($list_elements as $list_element){
			if ($list_element->getAttribute('class') == 'active'){
				return trim($list_element->nodeValue);
			}
		}
		return null;		
	}
	
	private static function read_description($xml){
		$wrapper = $xml->getElementById('wrapper');
		$paragraphs = $wrapper->getElementsByTagName('p');				
		foreach ($paragraphs as $paragraph){
			return trim($paragraph->nodeValue);
		}
		return null;
	}
	
	private static function read_info($xml,$key){
		$wrapper = $xml->getElementById('wrapper');
		$paragraphs = $wrapper->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			$infos = $paragraph->getElementsByTagName('i');
			if ($infos->length > 0){
				$return = false;				
				foreach ($paragraph->childNodes as $child){
					if ($child instanceof DOMElement){
						$class = $child->getAttribute('class');
						if (strpos($class,$key)!==false){
							return trim($child->nextSibling->wholeText);
						}						
					}
				}
			}
		}
		return null;
	}
	
	private static function read_tags($xml){
		$wrapper = $xml->getElementById('wrapper');
		$headlines = $wrapper->getElementsByTagName('h3');
		foreach ($headlines as $headline){
			$text = $headline->nodeValue;
			$text = str_replace('HIP HOP', 'HIP-HOP', $text);
			$tags = explode(' ', $text);
			break;						
		}
		$tags[] = self::read_info($xml, 'fa-music');
		$tags[] = 'Rosenkeller';
		$final_tags = array();
		foreach ($tags as $tag){
			if (strlen($tag)>2) $final_tags[]=$tag;
		}
		return array_unique($final_tags);
	}
	
	private static function read_attachments($xml){
		$wrapper = $xml->getElementById('wrapper');
		$images = $wrapper->getElementsByTagName('img');
		$attachments = array();
		foreach ($images as $image){
			$address = self::$base_url.$image->getAttribute('pagespeed_high_res_src');
			$mime = guess_mime_type($address);
			$attachments[] = url::create($address,$mime);
			
		}
		return $attachments;
	}
	
	private static function read_links($xml){
		$wrapper = $xml->getElementById('wrapper');
		$anchors = $wrapper->getElementsByTagName('a');
		$links = array();
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$links[] = url::create($anchor->getAttribute('href'),trim($anchor->nodeValue)); 
			}
		}
		return $links;
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