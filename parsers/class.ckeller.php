<?php
class CKeller{
	private static $base_url = 'http://www.c-keller.de';
	private static $event_list_page = '/';
	
	private static $months = array(
			'Jan'=>'01',
			'Feb'=>'02',
			'Mär'=>'03',
			'Apr'=>'04',
			'Mai'=>'05',
			'Jun'=>'06',
			'Jul'=>'07',
			'Aug'=>'08',
			'Sep'=>'09',
			'Okt'=>'10',
			'Nov'=>'11',
			'Dez'=>'12');
	
	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$events_div = $xml->getElementById('col2_content');
		$children = $events_div->childNodes;
		$cal = null;
		foreach ($children as $child){
			if ($child->nodeType == XML_TEXT_NODE) continue;
			if ($child->nodeType == XML_COMMENT_NODE) continue;
			if (!$child->hasAttribute('id')) continue;
			$id = $child->getAttribute('id');
			if ($id == 'cal') $cal = $child;
			if ($id == 'textumschluss') {
				self::read_event($cal,$child);
			}
		}
	}
	
	public static function read_event($cal_div,$content){
		
		$title = self::read_title($content);
		$description = self::read_description($content);
		$start=parseDate(self::read_start($cal_div));
		$location = 'C-Keller, Markt 21, 99425 Weimar';
		$source_url = self::$base_url . self::$event_list_page.'#'.replace_spaces($start);
		$coords = '50.979138, 11.329847';
		$tags = array('C-Keller','Weimar');
		if (stripos($title,'Live:') !== false) $tags[]='Konzert';
		$links = self::read_links($content);
		$links[] = url::create($source_url,'Event-Seite');
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
	
	public static function read_source($content){
		if ($content->hasAttribute('itemtype')){
			return $content->getAttribute('itemtype');
		}
		return null;
	}
	
	public static function read_title($content){
		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $p){
			if (!$p->hasAttribute('class')) continue;
			if ($p->getAttribute('class') == 'fett') return $p->nodeValue;
		}
		return null;
	}
	
	public static function read_description($content){
		$paragraphs = $content->getElementsByTagName('p');
		foreach ($paragraphs as $p){
			if (!$p->hasAttribute('class')) continue;
			if ($p->getAttribute('class') == 'kalenderi') return $p->nodeValue;
		}
		return null;
	}
	
	public static function read_start($content){
		$spans = $content->getElementsByTagName('span');
		$day = null;
		$month = null;
		$year = date('Y');
		$time = '19:00';
		foreach ($spans as $span){
			if (!$span->hasAttribute('class')) continue;
			$class = $span->getAttribute('class');
			if ($class == 'calday') $day = trim($span->nodeValue);
			if ($class == 'calmonth') $month = trim($span->nodeValue);
		}
		if ($day != null && $month != null){
			foreach (self::$months as $name => $m){
				$month = str_replace($name,$m,$month);
			}
			$start = $year.'-'.$month.'-'.$day.' '.$time;
			$stamp = strtotime($start);
			if ($stamp < time()){
				return $day.'.'.$month.'.'.($year+1).' '.$time;
			}
			return $day.'.'.$month.'.'.$year.' '.$time;
		}
		return null;
	}
	
	public static function read_images($content){
		$imgs = $content->getElementsByTagName('img');
		$images = array();
		foreach ($imgs as $img){
			$url = str_replace('200a','750a',$img->getattribute('src'));
			if (strpos($url,'http') === false) $url = self::$base_url.'/'.$url;
			$images[] = url::create($url,guess_mime_type($url));
		}
		return $images;
	}

	public static function read_links($content){
		$links = array();
		$anchors = $content->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if (!$anchor->hasAttribute('class')) continue;
			if ($anchor->getAttribute('class')!='link')continue;
			$links[] = url::create($anchor->getAttribute('href'),$anchor->nodeValue);
		}
		return $links;
	}	
}
