<?php


function find_program_page($site){
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($site);
	$links = $xml->getElementsByTagName('a');
	$result='';
	foreach ($links as $link){
		$children=$link->childNodes;
		for ($i=0; $i<$children->length; $i++){
			$child=$children->item($i);
			if ($child instanceof DOMText){
				$text=$child->wholeText;
				if (stripos($text, 'Programm')!==false){
					$result=$link->getAttribute('href');
					break;
				}
				if (stripos($text, 'Termine')!==false){
					$result=$link->getAttribute('href');
					break;
				}
			}
		}
	}
	if (stripos($result, '://')===false){
		$dir=dirname($site);
		if ($dir=='http:'){
			if (substr($site, -1,1)=='/'){
				$result=$site.$result;
			} else {
				$result=$site.'/'.$result;
			}
		} else {
			$result=$dir.'/'.$result;
		}
	}

	return $result;
}

function find_event_pages($page){
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);
	$links = $xml->getElementsByTagName('a');
	$result=array();
	foreach ($links as $link){
		$href=$link->getAttribute('href');
		if (stripos($href,'event')!== false){
			if (stripos($href, '://')===false){
				$href=dirname($page).'/'.$href;
			}
			$result[]=$href;
		}
	}
	return array_unique($result);
}

function extract_date($text){
	preg_match('/\d?\d\.\d?\d\.\d\d\d\d/', $text, $matches);
	if (count($matches)>0){
		$date=$matches[0];
		return $date;
	} else {
		preg_match('/\d?\d\.\d?\d\./', $text, $matches);
		if (count($matches)>0){
			$date=$matches[0].date("Y");
			return $date;
		}
	}
	return '';
}

function extract_time($text){
	preg_match('/\d?\d:\d?\d/', $text, $matches);
	if (count($matches)>0){
		$time=$matches[0];
		return $time;
	}
	return '';
}

function parser_parse_date($text){
	global $db_time_format;	
	$date=extract_date($text);
	
	$time=extract_time($text);
	
	$date=date_parse($date.' '.$time);
	$secs=parseDateTime($date);
	return $secs;
}

function parse_tags($text){
	$dummy=explode(' ', $text);
	$result=array();
	foreach ($dummy as $tag){
		$tag=trim($tag);
		if (strlen($tag)>2){
			$result[]=$tag;
		}
	}
	return $result;
}

function load_xml($url){
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($url);	
	return $xml;
}

function merge_fields(&$target_data,$additional_data,$fields){
	foreach ($fields as $field){
		if (isset($target_data[$field])){
			if ($target_data[$field]==null){
				$target_data[$field]=$additional_data[$field];
			} else if (is_array($target_data[$field])){
				if (is_array($additional_data[$field])){
					$target_data[$field]=array_merge($target_data[$field],$additional_data[$field]);
				} else {
					if (isset($additional_data[$field])){
						$target_data[$field]=$additional_data[$field];
					}
				}
			}
		} else {
			$target_data[$field]=$additional_data[$field];
		}
	}
}

function grep_event_title($xml){
	/* Rosenkeller */
	$list_elements=$xml->getElementsByTagName('li');
	foreach ($list_elements as $list_element){
		foreach ($list_element->attributes as $attribute){
			if ($attribute->name == 'class' && strpos($attribute->value,'active')!==false){
				return trim($list_element->nodeValue);
			}
		}
	}	
	/* Rosenkeller */
	/* Wagner */
	$headings=$xml->getElementsByTagName('h1');
	foreach ($headings as $heading){
		return $heading->nodeValue;
	}
	/* Wagner  */
	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_title')));
	return null;
}

function grep_event_description_raw($xml){
	/* Rosenkeller */
	$divs=$xml->getElementsByTagName('div'); // Suchen aller divs
	foreach ($divs as $div){
		foreach ($div->attributes as $attr){
			if ($attr->name == 'class' && $attr->value=='event-description'){ // Suchen des Beschreibungstextes
				$text=trim($div->childNodes->item(0)->nodeValue); // Das "event-description"-div hat mehrere unterlemenete. Eines davon ist der eigentliche Text
				if (strlen($text)<10){
					return trim($div->childNodes->item(1)->nodeValue);
				}
				return $text; // wen wir den Text haben: Suche beenden
			}
		}
	}	
	/* Rosenkeller */
	/* Wagner */
	$paragraphs=$xml->getElementsByTagName('p');
	$text='';
	foreach ($paragraphs as $paragraph){		
		$text.=trim($paragraph->nodeValue)."\n";
	}
	if (strlen($text) > 0){
		return $text;
	}
	/* Wagner */
	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_description')));
	return null;	
}

function grep_event_description($xml){
	$raw=grep_event_description_raw($xml);
	$raw=str_replace(array("\r\n","\r"), "\n", $raw);	
	$enc=htmlspecialchars_decode($raw);
	return $enc;
}

function grep_event_start($xml){
	global $db_time_format;
	/* Rosenkeller */
	$infos=$xml->getElementsByTagName('i'); // weitere Informationen abrufen
	foreach ($infos as $info){
		if ($info->attributes){
			foreach ($info->attributes as $attr){
				if ($attr->name == 'class'){
					if (strpos($attr->value, 'fa-calendar') !== false){
						$starttime=parser_parse_date($info->nextSibling->wholeText);
						return date($db_time_format,$starttime);
					}
				}
			}
		}
	}
	/* Rosenkeller */
	/* Wagner */
	$paragraphs=$xml->getElementsByTagName('p');
	foreach ($paragraphs as $paragraph){
		$text=trim($paragraph->nodeValue);
		if (preg_match('/\d\d.\d\d.\d\d:\d\d/',$text)){
			$starttime= parser_parse_date($text);
			return date($db_time_format,$starttime);
		}
		if (preg_match('/\d\d.\d\d.\d\d\d\d/',$text)){
			$starttime= parser_parse_date($text);
			return date($db_time_format,$starttime);
		}
	}
	/* Wagner */
	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_start')));
	return null;
}

function grep_event_end($xml){
	return null;
	// TODO
	return loc('%method not implemented, yet',array('%method'=>'grep_event_end'));
}

function grep_event_location($xml,$default=null){
	/* Rosenkeller */
	$infos=$xml->getElementsByTagName('i'); // weitere Informationen abrufen
	foreach ($infos as $info){
		if ($info->attributes){
			foreach ($info->attributes as $attr){
				if ($attr->name == 'class'){
					if (strpos($attr->value, 'fa-building') !==false){
						return $info->nextSibling->wholeText;
					}
				}
			}
		}
	}
	/* Rosenkeller */
	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_location')));
	return null;
}

function grep_event_coords($xml,$default=null){
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_coords')));
	return null;
	// TODO
}

function grep_event_tags_raw($xml){
	/* Rosenkeller */
	$infos=$xml->getElementsByTagName('i'); // weitere Informationen abrufen
	foreach ($infos as $info){
		if ($info->attributes){
			foreach ($info->attributes as $attr){
				if ($attr->name == 'class'){
					if (strpos($attr->value, 'fa-music') !==false){
						return parse_tags($info->nextSibling->wholeText);
					}
				}
			}
		}
	}
	/* Rosenkeller */
	/* Wagner */
	$paragraphs=$xml->getElementsByTagName('p');
	foreach ($paragraphs as $paragraph){
		$text=trim($paragraph->nodeValue);
		$pos=strpos($text,'Kategorie');
		if ($pos!==false){
			return parse_tags(substr($text, $pos+8));
		}
	}
	/* Wagner */
	

	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_tags')));
	return null;
}

function grep_event_tags($xml, $additional=array()){
	if ($additional==null){
		$additional=array();
	}
	$additional[]=loc('imported');
	
	$tags=grep_event_tags_raw($xml);
	if ($tags==null){
		return $additional;
	}
	return array_merge($tags,$additional);
}

function grep_event_links($xml,$url=null){
	$links=array();
	if ($url!=null){
		$url=url::create($url,loc('event page'));
		$links[]=$url;
	}
	$infos=$xml->getElementsByTagName('i'); // weitere Informationen abrufen
	foreach ($infos as $info){
		if ($info->attributes){
			foreach ($info->attributes as $attr){
				if ($attr->name == 'class'){
					if (strpos($attr->value, 'fa-globe') !==false){						
						$link=$info->nextSibling;						
						if (!isset($link->tagName)){ // link separated by text: skip to link
							$link=$link->nextSibling;
						}
						if (!isset($link->tagName) || $link->tagName != 'a'){ // still no link found: give up
							break;
						}
						$href=trim($link->getAttribute('href'));
						$tx=trim($link->nodeValue);
						$links[]=url::create($href,$tx);
					}
				}
			}
		}
	}
	
	
	$paragraphs=$xml->getElementsByTagName('p');
	foreach ($paragraphs as $paragraph){
		$hrefs=$paragraph->getElementsByTagName('a');
		foreach ($hrefs as $link){
			$href=trim($link->getAttribute('href'));
			$mime=guess_mime_type($href);
			if (!startsWith($mime, 'image')){
				$tx=trim($link->nodeValue);
				$links[]=url::create($href,$tx);
			}
		}
	}
	if (!empty($links))	{
		return $links;
	}	
	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_links')));
	return array();
}

function grep_event_images_raw($referer,$xml){
	$images=$xml->getElementsByTagName('img');
	$imgs=array();
	/* Rosenkeller */
	foreach ($images as $image){
		if ($image->hasAttribute('pagespeed_high_res_src')){
			$src=$image->getAttribute('pagespeed_high_res_src');
			if (stripos($src, '://')===false){
				$src=dirname($referer).'/'.$src;
			}
			$imgs[]=$src;
		}
	}
	if (!empty($imgs)){
		return $imgs;
	}
	/* Rosenkeller */
	/* Wagner */
	$paragraphs=$xml->getElementsByTagName('p');
	foreach ($paragraphs as $paragraph){
		$hrefs=$paragraph->getElementsByTagName('a');
		foreach ($hrefs as $link){
			$href=trim($link->getAttribute('href'));
			$mime=guess_mime_type($href);
			if (startsWith($mime, 'image')){
				$imgs[]=$href;
			}
		}
	}
	if (!empty($imgs)){
		return $imgs;
	}
	/* Wagner */
	
	$images=$xml->getElementsByTagName('img');
	foreach ($images as $image){
		$imgs[]=trim($image->baseURI.$image->getAttribute('src'));
	}
	if (!empty($imgs)){
		return $imgs;
	}
	
	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'grep_event_images')));
	return null;
}

function grep_event_images($referer,$xml){
	$images=grep_event_images_raw($referer, $xml);
	if ($images == null){
		return array();
	}	
	$result=array();
	foreach ($images as $src){		
		$mime=guess_mime_type($src);
		$image=url::create($src,$mime);
		$result[]=$image;
	}
	return $result;
}

function already_imported($event_url){

	// TODO
	error_log(loc('%method not implemented, yet',array('%method'=>'already_imported')));
	return false;
}



function parserImport($site_data){
	if (!is_array($site_data)){
		$site_data=array('url'=>$site_data);
	}
	if (!isset($site_data['url']) || empty($site_data['url'])){
		warn('You must supply an url to import from!');
		return;
	}
	$program_page=find_program_page($site_data['url']); // $url usually specifies the root url of a website
	$event_pages=find_event_pages($program_page); // the program page usually links to the event pages
	foreach ($event_pages as $event_url){
		error_log('parsing '.$event_url);
		$xml         = load_xml($event_url);
		$title       = grep_event_title($xml);
		print $event_url.NL;
		$description = grep_event_description($xml);
		if (empty($description)){
			continue;
		}
		$start       = grep_event_start($xml);
		$end	  	 = grep_event_end($xml);
		$location    = grep_event_location($xml,$site_data['location']); // fallback		
		$coords      = grep_event_coords($xml,$site_data['coords']); // fallback
		$tags		 = grep_event_tags($xml,$site_data['tags']); // merge		
		$links		 = grep_event_links($xml,$event_url);
		$images		 = grep_event_images($event_url,$xml);		
		$event = appointment::create($title, $description, $start, $end, $location, $coords, $tags, $links, $images,false); // TODO: add params
		$existing_event = appointment::get_imported($event_url);
		if ($existing_event != null){			
			error_log("appointment has been imported before");
			// TODO: add all methods below
			$existing_event->set_title($title);
			$existing_event->set_description($description);
			$existing_event->set_start($start);
			$existing_event->set_end($end);
			$existing_event->set_location($location);
			$existing_event->set_coords($coords);
			foreach ($tags as $tag){
				$existing_event->add_tag($tag);
			}			
			foreach ($links as $link){
				$existing_event->add_link($link);
			}
			foreach ($images as $image){
				$existing_event->add_attachment($image);
			}			
			$existing_event->save(); 
		} else {
			error_log("saving appointment");
			$event->save(); // TODO: currently does not save tags, links and images
			$event->mark_imported($event_url);
		}
	}
}

?>