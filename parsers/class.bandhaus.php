<?php
class Bandhaus{
	private static $base_url = 'https://bandcommunity-leipzig.org';
	private static $event_list_page = '/';

	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$event_div = $xml->getElementById('events');
		$anchors = $event_div->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			static::read_event($anchor->getAttribute('href'));
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



	public static function read_event($event_url){
		$source_url = static::$base_url.'/'.$event_url;
		$xml = load_xml($source_url);
		$main = $xml->getElementById('events');

		$start = static::read_start($main);
		$title = static::read_title($main);
		$location = 'Bandhaus, Saarländer Str. 17, Leipzig';
		$coords = '51.32490,12.31530';
		$attachments = static::read_images($main);
		$links = static::read_links($main,$source_url);
		$text = static::read_description($main);

		$event = Event::get_imported($source_url);
		if (empty($event)){
			//print 'creating new event for '.$source_url.NL;
			$event = Event::create($title, $text, $start, null, $location, $coords, ['Leipzig','Bandhaus'], $links, $attachments);
			$event->mark_imported($source_url);
		} else {
			//print 'updating event for '.$source_url.NL;
			$event->set_title($title);
			$event->set_description($text);
			$event->set_start($start);
			$event->set_location($location);
			$event->add_tag('Leipzig');
			$event->add_tag('Bandhaus');
			foreach ($links as $link) $event->add_link($link);
			$event->save();
		}
	}
	private static function read_title($main){
		$headings = $main->getElementsByTagName('h1');
		foreach ($headings as $heading){
			return $heading->textContent;
		}
		return null;
	}

	private static function read_start($main){
		global $db_time_format;

		$text = $main->textContent;
		preg_match('/Beginn: ?\d\d:\d\d/', $text, $matches);
		if (empty($matches)) preg_match('/Einlass: ?\d\d:\d\d/', $text, $matches);
		$match = reset($matches);
		$parts = explode(':',$match,2);
		$time = trim($parts[1]);

		$spans = $main->getElementsByTagName('span');
		$tag = null;
		$monat = null;
		foreach ($spans as $span){
			$class = $span->hasAttribute('class') ? $span->getAttribute('class') : null;
			if ($class=='tag') $tag = $span->textContent;
			if ($class=='monat') $monat = $span->textContent;
			if (!empty($tag) && !empty($monat)) break;
		}
		switch($monat){
			case 'Jan': $monat = '01'; break;
			case 'Feb': $monat = '02'; break;
			case 'Mär': $monat = '03'; break;
			case 'Apr': $monat = '04'; break;
			case 'Mai': $monat = '05'; break;
			case 'Jun': $monat = '06'; break;
			case 'Jul': $monat = '07'; break;
			case 'Aug': $monat = '08'; break;
			case 'Sep': $monat = '09'; break;
			case 'Okt': $monat = '10'; break;
			case 'Nov': $monat = '11'; break;
			case 'Dez': $monat = '12'; break;
		}

		$date=$tag.'.'.$monat.'.'.date('Y');
		$datestring=date_parse($date.' '.$time);
		$secs=parseDateTime($datestring);

		// if day has passed by this year, it should lie in the next year
		if ($secs < time()){
			$date=$tag.'.'.$monat.'.'.(date('Y')+1);
			$datestring=date_parse($date.' '.$time);
			$secs=parseDateTime($datestring);
		}

		return date($db_time_format,$secs);
	}

	private static function read_description($main){
		$divs = $main->getElementsByTagName('div');
		foreach ($divs as $div){
			$class = $div->hasAttribute('class')?$div->getAttribute('class'):null;
			if ($class=='teaser') return $div->textContent;
		}
		return null;
	}

	private static function read_links($node,$source_url){
		$links = [url::create($source_url,'Event-Seite')];
		$anchors = $node->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->hasAttribute('href')){
				$address = $anchor->getAttribute('href');
				if (strpos(guess_mime_type($address),'image')===false) $links[] = url::create($address,trim($anchor->nodeValue));
			}
		}
		return $links;
	}

	private static function read_images($main){
		$images = $main->getElementsByTagName('img');
		foreach ($images as $image){
			$address = $image->getAttribute('src');
			if (strpos($address,'://')===false) $address = static::$base_url.'/'.$address;
			$mime = guess_mime_type($address);
			return [url::create($address,$mime)];
		}
		return null;
	}
}