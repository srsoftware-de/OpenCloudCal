<?php
class SaaleGaerten{
	private static $base_url = 'http://www.saalgaerten.de/';
	private static $event_list_pages = array('de/programm.html','de/kinoprogramm.html');

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
		$event_pages = array();
		
		foreach (self::$event_list_pages as $event_list_page){
			$xml = load_xml(self::$base_url . $event_list_page);
			$programm = $xml->getElementById('programm');
			if ($programm == null) $programm = $xml->getElementById('kinoprogramm');
			$events = $programm->getElementsByTagName('div');
			foreach ($events as $event){
				if (!$event->hasAttribute('class')) continue;
				$class = $event->getAttribute('class');
				if (strpos($class, 'upcoming')===false) continue;
				$links = $event->getElementsByTagName('a');
				foreach ($links as $link){
					$href = trim($link->getAttribute('href'));
					$event_pages[]=self::$base_url.$href;
					break;			
				}
			}
		}
		$event_pages=array_unique($event_pages);
		foreach ($event_pages as $page){
			self::read_event($page);
		}
	}

	public static function read_event($source_url){
		$xml = load_xml($source_url);

		$title = self::read_title($xml);
		
		$description = self::read_description($xml);
		$start = self::date(self::read_start($xml));
		$location = 'Saalgärten 1b, 07407 Rudolstadt';

		$coords = '50.719359, 11.348609';

		$tags = self::read_tags($xml);
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
		$detail = $xml->getElementById('programm-detail');
		if ($detail == null) $detail = $xml->getElementById('filmdetail');
		$divs = $detail->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'titlebox') continue;
			return trim($div->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		$detail = $xml->getElementById('programm-detail');
		if ($detail == null) $detail = $xml->getElementById('filmdetail');
		$divs = $detail->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'textbox') continue;
			$paras = $div->getElementsByTagName('p');
			$description = '';
			foreach ($paras as $para){
				$description.=$para->nodeValue.NL;
			}
			return trim($description);
		}
		return null;
	}

	private static function read_start($xml){
		$detail = $xml->getElementById('programm-detail');
		if ($detail == null) $detail = $xml->getElementById('filmdetail');
		$divs = $detail->getElementsByTagName('div');
		$datestring = '';
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'datebox') continue;
			$strongs = $div->getElementsByTagName('strong');
			foreach ($strongs as $strong){
				$datestring.=$strong->nodeValue;
			}
			$paras = $div->getElementsByTagName('p');
			foreach ($paras as $para){
				if (!$para->hasAttribute('class')) continue;
				$class = $para->getAttribute('class');
				if ($class == 'month') {
					$datestring.='.'.$para->nodeValue;
				}
				if ($class == 'time'){
					$timestring = $para->nodeValue;
					$pos = strpos($timestring,'Uhr');
					if ($pos !== false){
						$datestring.=' '.trim(str_replace('Beginn:', '', substr($timestring, 0,$pos)));
					}
				}
			}
			foreach (self::$months as $name => $month){
				$datestring = str_replace($name." '", $month.'.20', $datestring);
			}
			return trim($datestring);
		}
		return null;
	}

	private static function read_tags($xml){
		$detail = $xml->getElementById('programm-detail');
		if ($detail == null) $detail = $xml->getElementById('filmdetail');
		$divs = $detail->getElementsByTagName('div');
		$tags = array();
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'datebox') continue;
			$paras = $div->getElementsByTagName('p');
			foreach ($paras as $para){
				if (!$para->hasAttribute('class')) continue;
				$class = $para->getAttribute('class');
				if ($class != 'tag') continue;
				$tags = explode(' ', trim($para->nodeValue));
				break 2;
			}
		}
		$tags[] = 'Saalegärten';
		$tags[] = 'Rudolstadt';
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$url = url::create($source_url,loc('event page'));
		$links = array($url,);
		
		$detail = $xml->getElementById('programm-detail');
		if ($detail == null) $detail = $xml->getElementById('filmdetail');
		$divs = $detail->getElementsByTagName('div');
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;
			$class = $div->getAttribute('class');
			if ($class != 'textbox') continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$href = trim($anchor->getAttribute('href'));
				$links[] = url::create($href,$anchor->nodeValue);
			}
		}
		return $links;
	}

	private static function read_images($xml){
		$images = array();
		
		$detail = $xml->getElementById('programm-detail');
		if ($detail == null) $detail = $xml->getElementById('filmdetail');
		$imgs = $detail->getElementsByTagName('img');
		foreach ($imgs as $img){
			if (!$img->hasAttribute('src')) continue;
			$src = $img->getAttribute('src');
			if (strpos($src, 'http')=== false){
				$src = self::$base_url.$src;
			}
			$mime = guess_mime_type($src);
			$images[] = url::create($src,$mime);
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