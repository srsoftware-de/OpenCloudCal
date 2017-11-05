<?php
class JenaKultur{
	private static $base_url = 'https://www.jenakultur.de';
	private static $event_list_page = '246470';

	public static function read_events(){
		
		$year = date('Y');
		$date = date('d.m.');
		$start_date = $date.$year;
		$end_date = $date.($year+1);
		
		$event_pages = [];
		
		$url = self::$base_url.'/de/'.self::$event_list_page.'?date_from='.$start_date.'&date_to='.$end_date.'&max=200';
		$xml = load_xml($url);
		$pager = $xml->getElementById('pager_load_content');
		$anchors = $pager->getElementsByTagName('a');
		foreach ($anchors as $anchor){
			if ($anchor->textContent == '') continue;
			if (!$anchor->hasAttribute('href')) continue;
			$event_pages[] = $anchor->getAttribute('href');
		}
		$event_pages = array_unique($event_pages);
		foreach ($event_pages as $page)	self::read_event(self::$base_url.'/de/'.$page);
	}
	

	public static function read_event($source_url){
		$xml = load_xml($source_url);
		$title = self::read_title($xml);
		$description = self::read_description($xml);
		
		$start = self::read_start($xml);
		$location = self::read_location($xml);
		
		$coords = self::getCoords($location);
		$tags = self::read_tags($xml,$location);
		
		$links = self::read_links($xml,$source_url);
		$attachments = self::read_images($xml,self::$base_url);
		
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
	
	private static function getCoords($location){
		if (preg_match('/Am.*Anger.*26/', $location))	return "50.931041, 11.592494";
		if (preg_match('/Anna-Siemsen-Straße.*1/', $location))	return "50.900698, 11.576314";
		if (preg_match('/Bachstraße.*39/', $location))	return "50.929640, 11.582501";
		if (preg_match('/Bebel-Straße.*17a/', $location))	return "50.931641, 11.575464";
		if (preg_match('/Bibliotheksplatz.*2/', $location))	return "50.930519, 11.587721";		
		if (preg_match('/Breitscheid-Straße.*2/', $location))	return "50.880461, 11.623390";
		if (preg_match('/Carl-Zeiß-Straße.*3/', $location))	return "50.928644, 11.581153";
		if (preg_match('/Charlottenstraße.*19/', $location))	return "50.932494, 11.599681";
		if (preg_match('/Feldstraße.*8/', $location))	return "50.932142, 11.605704";
		if (preg_match('/Goethestraße.*3/', $location)) 		return "50.927671, 11.582361";
		if (preg_match('/Grietgasse.*17a/', $location)) 		return "50.925650, 11.585949";
		if (preg_match('/Im.*Wehrigt.*10/', $location)) 		return "50.893323, 11.600034";
		if (preg_match('/Jenzigweg.*33/', $location))	return "50.934967, 11.604802";
		if (preg_match('/Lange.*Straße.*1/', $location))	return "50.955593, 11.638032";
		if (preg_match('/Leutragraben.*1/', $location))	return "50.928615, 11.584032";
		if (preg_match('/Markt.*16/', $location)) return "50.927973, 11.588443";
		if (preg_match('/Markt.*7/', $location)) 	return "50.928491, 11.588274";		
		if (preg_match('/Markt.*1/', $location)) 	return "50.927856, 11.587492";
		if (preg_match('/Philosophenweg.*20/', $location)) 	return "50.932663, 11.584434";
		if (preg_match('/Sophienstraße.*18/', $location)) 	return "50.932894, 11.588875";
		if (preg_match('/Teutonengasse.*3/', $location)) 	return "50.926610, 11.588982";
		if (preg_match('/Vor dem Neutor.*5/', $location)) 	return "50.922196, 11.584792";
		if (preg_match('/Wagnergasse.*26/', $location))	return "50.931115, 11.580225";
		if (preg_match('/Westbahnhofstraße.*8/', $location))	return "50.924737, 11.579044";
		if (preg_match('/Ziegenhainer.*Straße.*52/', $location))	return "50.921476, 11.604575";
		
		
		if (strpos($location, 'Abbe-Bücherei')!== false) 		return "50.927099, 11.580427";		
		if (strpos($location, 'EAH Jena')!== false) 		return "50.918503, 11.569581";
		if (strpos($location, 'F-Haus')!== false) 		return "50.929282, 11.582243";
		if (strpos($location, 'Haus auf der Mauer')!== false) 		return "50.929803, 11.583870";
		if (strpos($location, 'Immergrün')!== false) 		return "50.929668, 11.585541";
		if (strpos($location, 'Innenstadt')!== false) 		return "50.928, 11.586";
		if (strpos($location, 'Kassablanca')!== false) 		return "50.922629, 11.577887";
		if (strpos($location, 'Kirchplatz')!== false) 		return "50.929000, 11.587984";
		if (strpos($location, 'KuBuS')!== false) 		return "50.885762, 11.607136";
		if (strpos($location, 'Kulturbahnhof')!== false) 		return "50.936535, 11.592681";
		if (strpos($location, 'LISA')!== false) 		return "50.882214, 11.613220";
		if (strpos($location, 'Lutherhaus')!== false) 		return "50.924322, 11.592498";
		if (strpos($location, 'Markt')!== false) 	return "50.928094, 11.588008";		
		if (strpos($location, 'Melanchthonhaus')!== false) 	return "50.924928, 11.576424";
		if (strpos($location, 'Optisches Museum')!== false) 	return "50.927862, 11.579201";
		if (strpos($location, 'Romantikerhaus')!== false) 		return "50.927325, 11.589671";
		if (strpos($location, 'Rosenthal')!== false) 		return "50.919445, 11.579714";
		if (strpos($location, 'Sparkassen-Arena')!== false) 		return "50.899820, 11.588523";
		if (strpos($location, 'Theaterhaus')!== false) 		return "50.925428, 11.583475";
		if (strpos($location, 'Volksbad')!== false) 		return "50.925147, 11.586522";
		if (strpos($location, 'Volkshaus')!== false) 		return "50.927279, 11.580014";
		if (strpos($location, 'Volkssternwarte')!== false) 		return "50.925257, 11.583004";
		if (strpos($location, 'Zeiss-Planetarium')!== false) 		return "50.931813, 11.587135";
		
		return null;
	}
	
	private static function getTagsFromLocation($location){
		if (preg_match('/Anna-Siemsen-Straße.*1/', $location))	return ['Winzerla'];
		if (preg_match('/Bachstraße.*39/', $location))	return ['IrishPub','FiddlersGreen'];
		if (preg_match('/Bebel-Straße.*17a/', $location))	return ['EvangelischeStudentengemeinde','ESG'];
		if (preg_match('/Bibliotheksplatz.*2/', $location))	return ['Thulb','Bücherei','Bibliothek'];
		if (preg_match('/Breitscheid-Straße.*2/', $location))	return ['Mehrgenerationenhaus','AWO'];
		if (preg_match('/Carl-Zeiß-Straße.*3/', $location))	return ['FSU','Universität','Campus'];
		if (preg_match('/Charlottenstraße.*19/', $location))	return ['Kunsthandlung','Huber','Treff'];
		if (preg_match('/Goethestraße.*3/', $location)) 		return ['GoetheGalerie'];
		if (preg_match('/Grietgasse.*17a/', $location)) 		return ['Volkshochschule','VHS'];
		if (preg_match('/Im.*Wehrigt.*10/', $location)) 		return ['Reitsportzentrum'];
		if (preg_match('/Lange.*Straße.*1/', $location))	return ['alteSchule','Kunitz'];
		if (preg_match('/Leutragraben.*1/', $location))	return ['NeueMitte'];
		if (preg_match('/Markt.*16/', $location)) return ['TouristInfo'];
		if (preg_match('/Markt.*7/', $location)) 	return ['Stadtmuseum', 'Göhre'];
		if (preg_match('/Markt.*1/', $location)) 	return ['Rathaus'];
		if (preg_match('/Philosophenweg.*20/', $location)) 	return ['Mensa','Philosophenweg'];
		if (preg_match('/Sophienstraße.*18/', $location)) 	return ['KünstlerischeAbendschule'];
		if (preg_match('/Teutonengasse.*3/', $location)) 	return ['Kleinskunstbühne'];
		if (preg_match('/Vor dem Neutor.*5/', $location)) 	return ['ParadiesCafe','Paradies'];
		if (preg_match('/Wagnergasse.*26/', $location))	return ['CafeWagner'];
		if (preg_match('/Ziegenhainer.*Straße.*52/', $location))	return ['Musikschule','Kunstschule'];
	
	
		if (strpos($location, 'Abbe-Bücherei')!== false) 		return ['Abbe','Bücherei','Bibliothek'];
		if (strpos($location, 'EAH Jena')!== false) 		return ['Ernst-Abbe','Fachhochschule'];
		if (strpos($location, 'F-Haus')!== false) 		return ['F-Haus','FHaus'];
		if (strpos($location, 'Haus auf der Mauer')!== false) 		return ['Haus','HasuAufDerMauer'];
		if (strpos($location, 'Immergrün')!== false) 		return ['Café','Cafe','Immergrün'];
		if (strpos($location, 'Kassablanca')!== false) 		return ['Kassablanca'];
		if (strpos($location, 'Kirchplatz')!== false) 		return ['Kirchplatz'];
		if (strpos($location, 'KuBuS')!== false) 		return ['KuBuS'];
		if (strpos($location, 'Kulturbahnhof')!== false) 		return ['Kulturbahnhof'];
		if (strpos($location, 'LISA')!== false) 		return ['Stadtteilzentrum','LISA'];
		if (strpos($location, 'Lutherhaus')!== false) 		return ['Lutherhaus'];
		if (strpos($location, 'Markt')!== false) 	return ['Markt'];
		if (strpos($location, 'Melanchthonhaus')!== false) 	return ['Melanchthonhaus'];
		if (strpos($location, 'Optisches Museum')!== false) 	return ['optisches','Museum'];
		if (strpos($location, 'Romantikerhaus')!== false) 		return ['Romantikerhaus'];
		if (strpos($location, 'Rosenthal')!== false) 		return ['Villa','Rosenthal'];
		if (strpos($location, 'Sparkassen-Arena')!== false) 		return ['SparkassenArena'];
		if (strpos($location, 'Theaterhaus')!== false) 		return ['Theaterhaus','Theater'];
		if (strpos($location, 'Volksbad')!== false) 		return ['Volksbad'];
		if (strpos($location, 'Volkshaus')!== false) 		return ['Volkshaus'];
		if (strpos($location, 'Volkssternwarte')!== false) 		return ['Volkssternwarte','Sternwarte','URANIA'];
		if (strpos($location, 'Zeiss-Planetarium')!== false) 		return ['Planetarium'];
	
		return [];
	}

	private static function read_title($xml){
		$headings = $xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			return trim($heading->nodeValue);
		}
		return null;
	}

	private static function read_description($xml){
		$divs = $xml->getElementsByTagName('div');			
		$description = '';
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;			
			if ($div->getAttribute('class') != 'absatzbox') continue;
			$description .= $div->ownerDocument->saveHTML($div);
		}
		return $description;
	}

	private static function read_start($xml){
		$divs = $xml->getElementsByTagName('div');			
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;			
			if ($div->getAttribute('class') != 'event_detail_information') continue;
			$text=$div->textContent;
			return parseDate($text);
		}
		return null;
	}
	
	private static function read_location($xml){
		$divs = $xml->getElementsByTagName('div');
		$location = '';			
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;			
			if ($div->getAttribute('class') != 'event_detail_information') continue;
			$text=$div->textContent;
			$parts = explode("   ",$text);
			foreach ($parts as $part){
				$part = trim($part);
				if ($part == '') continue;
				if (strpos($part, 'Uhr')!==false) continue;
				$location .= ', '.$part;
			}
		}
		return trim($location,", \t\n\r\0\x0B");
	}

	private static function read_tags($xml,$location=''){
		$tags = self::getTagsFromLocation($location);
		$tags[] = 'Jena';
		return $tags;
	}

	private static function read_links($xml,$source_url){
		$divs = $xml->getElementsByTagName('div');			
		$links = [];
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;			
			if ($div->getAttribute('class') != 'absatzbox') continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				if (!$anchor->hasAttribute('href')) continue;
				$url = $anchor->getAttribute('href');
				$links[] = url::create($url,trim($anchor->nodeValue));
			}
		}
		return $links;
	}

	private static function read_images($xml,$base){
		$divs = $xml->getElementsByTagName('div');			
		$attachments = array();
		foreach ($divs as $div){
			if (!$div->hasAttribute('class')) continue;			
			if ($div->getAttribute('class') != 'event_detail_image_border') continue;
			$anchors = $div->getElementsByTagName('a');
			foreach ($anchors as $anchor){
				$address = trim($anchor->getAttribute('href'));
				if (strpos($address, '://')===false) $address=$base.$address;
				$mime = guess_mime_type($address);
				$attachments[] = url::create($address,$mime);
			}
		}
		return $attachments;
	}
}