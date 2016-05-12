<?php
class EBurg{
	private static $base_url = 'http://www.eburg.de/';
	private static $event_list_page = 'was/events';

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
		$content = $xml->getElementById('content');
		$posts = $content->childNodes;
		$event_pages = array();
		foreach ($posts as $post){
			if ($post->nodeType != XML_ELEMENT_NODE) continue;
			$class = $post->getAttribute('class');
			if (strpos($class, 'post')===false) continue;
			$links = $post->getElementsByTagName('a');
			foreach ($links as $link){
				$href = str_replace('#content','',trim($link->getAttribute('href')));
				if (strpos($href,'/events/')!==false){
					$event_pages[]=$href;
				}
			}
		}
		$event_pages = array_unique($event_pages);
		foreach ($event_pages as $page){
			self::read_event($page);
		}
	}
	
	public static function coords($location){
		if ($location == 'Eburg Club') return '50.978339, 11.026929';
		if (strpos($location, 'DuckDich')||strpos($location, 'Vortragsraum')) return '50.978137, 11.027100';
		if (strpos($location, 'Biergarten')) return '50.978137, 11.027100';
		
	}
	
	public static function expand($location){
		if ($location == 'Eburg Club') return 'Club "Eburg", Allerheiligenstraße 20/21, Erfurt';
		if (strpos($location, 'DuckDich')||strpos($location, 'Vortragsraum')) return 'Café "DuckDich", Allerheiligenstraße 20/21, Erfurt';
		if (strpos($location, 'Biergarten')) return 'Biergarten, Allerheiligenstraße 20/21, Erfurt';
		return $location.', Erfurt';
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);

		$title = self::read_title($xml);
		$description = self::read_description($xml);
		$start = self::date(self::read_start($xml));
		$location = self::read_category($xml,'Wo?');
		$coords = self::coords($location);
		$location = self::expand($location);
		

		$tags = self::read_tags($xml);
		$links = self::read_links($xml,$source_url);		
		$attachments = self::read_images($xml);
		die(print_r($attachments,true));
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
		$headings = $xml->getElementsByTagName('h2');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		$divs = $xml->getElementsByTagName('div');
		$description = '';
		foreach ($divs as $div){
			if ($div->hasAttribute('class') && $div->getAttribute('class')=='pf-content'){
				$children = $div->childNodes;
				foreach ($children as $child){
					if ($child->nodeType!=1) continue;
					$text = trim($child->nodeValue);					
					if ($text == '') continue;					
					$type = $child->nodeName;
					if ($type == 'h3' || $type == 'strong') continue; // skip headings				
					
					if ($child->hasAttribute('class')) {
						$class = $child->getAttribute('class');
						if ($class == 'lf_wrapper') continue;
						if (strpos($class, 'gallery')!==false) continue; // skip gallery
						if (strpos($class, 'printfriendly')!==false) continue; // skip gallery
						
						//print '(class: '.$child->getAttribute('class').') ';
					}
					if (strpos($text,'function(')!==false) continue; // skip script
					//print $type;
					//print ':'.NL;
					//print $text.NL.NL;
					$description .= $text.NL; 
				}
			}
		}
		return $description;
	}

	private static function read_start($xml){
		global $db_time_format;
		$content = $xml->getElementById('content');
		$spans = $content->getElementsByTagName('span');
		foreach ($spans as $span){
			if ($span->getAttribute('class') == 'date'){
				$string = $span->nodeValue;
				$pos = strpos($string,',');
				if ($pos !== false) $string = substr($string,$pos+1);
				$string = str_replace('.',':',$string); // 20.00 Uhr => 20:00 Uhr
				foreach (self::$months as $name => $month){ // 21: Mai 2016 => 21.05.2016
					$string = str_replace(': '.$name.' ', '.'.$month.'.', $string); 
				}
				$string = str_replace(array('ab ',' Uhr'),'',$string);		
				return trim($string);
			}
		}
		return null;
	}
	
	private static function read_category($xml,$key){
		$content = $xml->getElementById('content');
		$spans = $content->getElementsByTagName('span');
		foreach ($spans as $span){			
			if ($span->getAttribute('class') == 'category'){				
				$text = $span->nodeValue;
				if (strpos($text,$key)===false) continue;
				return trim(substr($text,strlen($key))).NL;
			}
		}
		error_log('category not found');		
	}

	private static function read_tags($xml){
		$content = $xml->getElementById('content');
		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			if (!$paragraph->hasAttribute('class') || $paragraph->getAttribute('class')!='tags') continue;
			$tags = trim($paragraph->nodeValue);
			$pos = strpos($tags, ':');
			if ($pos !== false) $tags=trim(substr($tags, $pos+1));
			$tags=str_replace(' ', '', $tags);
			return $explode(' ',$tags);			
		}
		
		// Fallback:
		$tags = explode(',',self::read_category($xml, 'Was?'));
		$tags[] = 'Eburg';
		$tags[] = 'Erfurt';
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$content = $xml->getElementById('content');
		$url = url::create($source_url,loc('event page'));	
		$links = array($url,);
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class') || $div->getAttribute('class')!= 'lf_wrapper') continue;
			$list_items = $div->getElementsByTagName('li');
			foreach ($list_items as $list_item){
				$anchors = $list_item->getElementsByTagName('a');
				foreach ($anchors as $anchor){
					if ($anchor->hasAttribute('href')){
						$address = $anchor->getAttribute('href');
						if (strpos(guess_mime_type($address),'image')===false){
							$links[] = url::create($address,trim($anchor->nodeValue));
						}
					}
				}
			}
		}			
		
		return $links;
	}

	private static function read_images($xml){
		$content = $xml->getElementById('content');
		$images = array();
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class') || $div->getAttribute('class')!= 'gallery-row') continue;
			$imgs = $div->getElementsByTagName('img');
			foreach ($imgs as $img){
				if ($img->hasAttribute('data-large-file')) print 'large: '.$img->getAttribute('data-large-file');
				if ($img->hasAttribute('src')) print 'large: '.$img->getAttribute('src');
				print NL;
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