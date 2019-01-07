<?php
class Gewerkschaftshaus{
	private static $base_url = 'http://www.hsd-erfurt.de';
	private static $event_list_page = '/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$event_div = $xml->getElementById('Inhalt');

		$parts = $event_div->childNodes;
		$nodes = [];
		for ($i=0; $i<$parts->length; $i++) $nodes[]=$parts->item($i);
		while (!empty($nodes)){
			$node = array_shift($nodes);

			if ($node->nodeName=='p' && static::getClass($node) == 'Daten'){
				array_unshift($nodes,$node);
				self::read_event($nodes);
			}
		}
	}

	private static function getClass($node){
		if (!empty($node->attributes)){
			foreach ($node->attributes as $attr){
				if ($attr->name =='class') return $attr->value;
			}
		}
		return null;
	}

	private static function getStyle($node){
		if (!empty($node->attributes)){
			foreach ($node->attributes as $attr){
				if ($attr->name =='style') return $attr->value;
			}
		}
		return null;
	}



	public static function read_event(array &$nodes){
		$start = null;
		$title = null;
		$location = null;
		$attachments = [];
		$links = [];
		$text = '';
		$source_url = static::$base_url.'?eventhash=';
		while (!empty($nodes)){
			$node = array_shift($nodes);
			$class = static::getClass($node);
			$type = $node->nodeName;
			switch ($type){
				case 'p':
					if ($class == 'Daten') {
						$source_url.=md5($node->textContent);
						$start = static::read_start($node);
						$location = trim(static::read_location($node));
					}
					$image = static::read_image($node);
					$links = array_merge($links,static::read_links($node));
					if (!empty($image)) $attachments[] = $image;
					break;
				case 'h1':
					$title = $node->textContent;

					break;
				case 'h2':
					foreach ($node->childNodes as $child){
						if ($child->nodeName =='#text') {
							$title.=' - '.$child->nodeValue;
							break;
						}
					}
					break;
				case 'div':
					if (static::getStyle($node)=="display: none"){
						foreach ($node->getElementsByTagName('p') as $paragraph) $text .= $paragraph->nodeValue."\n";
					}
					break;
				case 'hr':
					break 2;
			}
		}

		if (in_array($location,['HsD','Museumskeller'])){
			$coords = '50.981758,11.035228';
			$location .= ', Juri-Gagarin-Ring 140A, Erfurt';
		} else $coords = null;

		$event = Event::get_imported($source_url);
		if (empty($event)){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $text, $start, null, $location, $coords, ['Erfurt','HsD'], $links, $attachments);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($text);
			$event->set_start($start);
			$event->set_location($location);
			foreach ($links as $link) $event->add_link($link);
			$event->save();
		}
	}

	private static function read_title($xml){
		$headings = $xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			$parts = explode(':',$heading->nodeValue,2);
			return trim($parts[1]);
		}
		return null;
	}

	private static function read_description($xml){
		$articles = $xml->getElementsByTagName('article');
		$description = '';
		foreach ($articles as $article){
			$paragraphs = $article->getElementsByTagName('p');
			$first=true;
			foreach ($paragraphs as $paragraph){
				if ($first){
					$first = false;
					continue;
				}
				$text = trim($paragraph->textContent);
				if (!empty($text)) {
					if ($text == 'Sorry, the comment form is closed at this time.') continue;
					$description .= str_replace('€Kategorie', "€\nKategorie", $text) . NL;
				}
			}
		}
		return $description;
	}

	private static function read_start($node){
		global $db_time_format;
		$text = $node->nodeValue;
		$parts = explode(' / ',$text);
		$date = extract_date($parts[1]);
		$time = str_replace('.',':',$parts[2]);
		$secs=parseDateTime(date_parse($date.' '.$time));

		return date($db_time_format,$secs);
	}

	private static function read_location($node){
		global $db_time_format;
		$text = $node->nodeValue;
		$parts = explode(' / ',$text);
		return $parts[3];
	}

	private static function read_tags($xml){
		global $db_time_format;
		$articles = $xml->getElementsByTagName('article');
		$description = '';
		foreach ($articles as $article){
			$paragraphs = $article->getElementsByTagName('p');
			foreach ($paragraphs as $paragraph){
				$text = trim($paragraph->textContent);
				$pos = strpos($text, 'Kategorie:');
				if ($pos!==false) {
					$tags = explode(' ',substr($text, $pos+11));
					$tags[] = 'CafeWagner';
					$tags[] = 'Jena';
					return $tags;
				}
			}
		}
		return array('CafeWagner','Jena');
	}

	private static function read_links($node){
		$links = [];
		$anchors = $node->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');
				if (strpos(guess_mime_type($address),'image')===false) $links[] = url::create($address,trim($anchor->nodeValue));
			}
		}
		return $links;
	}

	private static function read_image($node){
		$images = $node->getElementsByTagName('img');
		foreach ($images as $image){
			$address = $image->getAttribute('src');
			$mime = guess_mime_type($address);
			return url::create($address,$mime);
		}
		return null;
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