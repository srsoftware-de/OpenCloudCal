<?php
class WagnerVerein{
	private static $base_url = 'http://www.wagnerverein-jena.de/';
	private static $event_list_page = '?page_id=52';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$tables = $xml->getElementsByTagName('table');
		$event_pages = array();
		foreach ($tables as $table){
			$class = $table->getAttribute('id');
			if (strpos($class, 'events')!==false){
				$links = $table->getElementsByTagName('a');
				foreach ($links as $link){
					$href = trim($link->getAttribute('href'));
					if (strpos($href,'event=')!==false){
						$event_pages[]=$href;
					}
				}
				break;
			}
		}

		foreach ($event_pages as $page){
			self::read_event($page);
		}
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);
	
		// next block: find content div
		$content = $xml->getElementById('Content');
		$divs = $content->getElementsByTagName('div');
		foreach ($divs as $div){
			if ($div->hasAttribute('class') && (strpos($div->getAttribute('class'), 'content_wrapper') !== false)){
				$content = $div;
				break;
			}
		}
		
		$title = self::read_title($content);
		$description = self::read_description($content);
		$start = self::read_start($content);
		$location = 'Café Wagner, Wagnergasse 26, 07743 Jena';

		$coords = '50.931251, 11.580310';

		$tags = self::read_tags($content);
		$links = self::read_links($content,$source_url);
		$attachments = self::read_images($content);
		//print $title . NL . $description . NL . $start . NL . $location . NL . $coords . NL . 'Tags: '. print_r($tags,true) . NL . 'Links: '.print_r($links,true) . NL .'Attachments: '.print_r($attachments,true).NL;
		$event = Event::get_imported($source_url);
		if ($event === null){
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

	private static function read_title($content){
		$doc = $content->ownerDocument;
		$headings = $content->getElementsByTagName('h1');
		$title = '';
		foreach ($headings as $heading){
			$parts = $heading->childNodes;
			$active = false;
			foreach ($parts as $part){
				if ($active){
					if ($part->nodeType == XML_TEXT_NODE) $title.=$part->nodeValue;
				} else {
					if ($part->nodeType == XML_ELEMENT_NODE) $active = true;
				}
				
			}
		}
		return trim($title);
	}

	private static function read_description(DOMNode $content){
		$description = '';
		$paragraphs = $content->getElementsByTagName('p');
		$doc = $content->ownerDocument;
		foreach ($paragraphs as $paragraph){
			$html = trim($doc->saveHTML($paragraph));
			$html = str_replace(array('<p>','<strong>','</strong>'),'',$html);
			$html = str_replace(array('</p>','<br>'),"<br/>",$html);									
			if (strpos($html, 'Kategorie:') == 1) continue;
			$description .= $html."\n";
		}
		return $description;
	}

	private static function read_start($content){
		global $db_time_format;
		
		$day = null;
		$time = null;
		$headings = $content->getElementsByTagName('h1');		
		foreach ($headings as $heading){
			$parts = $heading->childNodes;
			foreach ($parts as $part){
				$text = trim($part->nodeValue);				
				if ($text != ''){
					if ($day === null){
						$day = $text;
					} elseif ($time === null){
						$time = $text;
						break;						
					}
				}	
			}
			if ($time != null) break;
		}
		$day = substr($day,3); // remove day name abbrevation
		
		$date=extract_date($day.date('Y'));
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);
		
		// if day has passed by this year, it should lie in the next year
		if ($secs < time()){
			$date=extract_date($day.(date('Y')+1));
			$datestring=date_parse($date.' '.$time);
			$secs=parseDateTime($datestring);				
		}
		
		return date($db_time_format,$secs);
	}

	private static function read_tags($content){
		$lists = $content->getElementsByTagName('ul');
		$tags = array('CafeWagner','Jena');
		foreach ($lists as $list){
			if (!$list->hasAttribute('class')) continue;
			if (strpos($list->getAttribute('class'), 'categories') === false) continue;
			$list_elements = $list->getElementsByTagName('li');
			foreach ($list_elements as $list_element){
				$tags[] = trim($list_element->nodeValue);
			}			
		}
		return $tags;
	}

	private static function read_links($content,$source_url){
		$url = url::create($source_url,loc('event page'));	
		$links = array($url,);
		$anchors = $content->getElementsByTagName('a');
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

	private static function read_images($content){
		$images = $content->getElementsByTagName('img');
		$attachments = array();

		foreach ($images as $image){
			$address = $image->getAttribute('src');
			$mime = guess_mime_type($address);
			$attachments[] = url::create($address,$mime);
		}
	
		return $attachments;
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