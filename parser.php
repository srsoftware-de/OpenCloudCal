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
	return $result;
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

function parse_event($page){
	global $db_time_format;
	$result=array('place'=>null,'text'=>'');
	$links=array();
	$links[]=url::create(null, $page,loc('Event page'));
	$imgs=array();

	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);

	/** Rosenkeller **/
	$divs=$xml->getElementsByTagName('div'); // Suchen aller divs
	foreach ($divs as $div){
		foreach ($div->attributes as $attr){
			if ($attr->name == 'class' && $attr->value=='event-description'){ // Suchen des Beschreibungstextes
				$text=trim($div->childNodes->item(0)->nodeValue); // Das "event-description"-div hat mehrere unterlemenete. Eines davon ist der eigentliche Text
				if (strlen($text)<10){
					$text=trim($div->childNodes->item(1)->nodeValue);
				}
				$result['text']=$text; // wen wir den Text haben: Suche beenden
				break;
			}
		}
		if (isset($result['text'])){ // wen wir den Text haben: Suche beenden
			break;
		}
	}

	$data=$xml->getElementsByTagName('i'); // weitere Informationen abrufen
	foreach ($data as $info){
		if ($info->attributes){
			foreach ($info->attributes as $attr){
				if ($attr->name == 'class'){
					if (strpos($attr->value, 'fa-calendar') !== false){
						$result['start']=parser_parse_date($info->nextSibling->wholeText);
						break;
					}
					if (strpos($attr->value, 'fa-building') !==false){
						$result['location']=$info->nextSibling->wholeText;
						break;
					}
					if (strpos($attr->value, 'fa-music') !==false){
						$result['tags']=parse_tags($info->nextSibling->wholeText);
						break;
					}
					if (strpos($attr->value, 'fa-money') !==false){
						break;
					}
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
						$links[]=url::create(null, $href,$tx);
						break;
					}
				}
			}
		}
	}
	$images=$xml->getElementsByTagName('img');
	foreach ($images as $image){
		if ($image->hasAttribute('pagespeed_high_res_src')){
			$src=$image->getAttribute('pagespeed_high_res_src');
			if (stripos($src, '://')===false){
				$src=dirname($page).'/'.$src;
			}
			$imgs[]=$src;
		}
	}
	$lis=$xml->getElementsByTagName('li');
	foreach ($lis as $li){
		foreach ($li->attributes as $attr){
			if ($attr->name == 'class' && strpos($attr->value,'active')!==false){
				$result['title']=trim($li->nodeValue);
				break;
			}
		}
	}
	/** Rosenkeller **/
	/** Wagner **/
	if (!isset($result['start'])){
		$headings=$xml->getElementsByTagName('h1');
		foreach ($headings as $heading){
			$result['title']=$heading->nodeValue;
			break;
		}


		$paragraphs=$xml->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			$text=trim($paragraph->nodeValue);
			if (preg_match('/\d\d.\d\d.\d\d:\d\d/',$text)){
				$result['start']=parser_parse_date($text);
				continue;
			}
			$pos=strpos($text,'Kategorie');
			if ($pos!==false){
				$result['tags']=parse_tags(substr($text, $pos+8));
				continue;
			}
			if (strpos($text,'comment form')!==false){
				continue;
			}
			$hrefs=$paragraph->getElementsByTagName('a');
			foreach ($hrefs as $link){
				$href=trim($link->getAttribute('href'));
				$mime=guess_mime_type($href);
				if (startsWith($mime, 'image')){
					$imgs[]=$href;
				} else {
					$tx=trim($link->nodeValue);
					$links[]=url::create(null, $href,$tx);
				}
			}
			$result['text'].="\n".$text;
		}
	}
	/** Wagner **/

	/** cosmic dawn **/
	if (!isset($result['start'])){
		$startdate=0;
		$paragraphs=$xml->getElementsByTagName('p');
		foreach ($paragraphs as $paragraph){
			$text=trim($paragraph->nodeValue);
			if (preg_match('/\d\d.\d\d.\d\d\d\d/',$text)){
				$startdate=parser_parse_date($text);
				$result['start']=$startdate;
			}
			if (preg_match('/doors: *(?P<hour>\d\d?)pm/',$text,$hits)){
				if (isset($result['start'])){
					$result['start']=$result['start']+3600*(12+$hits['hour']);
					continue;
				}
			}
			$images=$xml->getElementsByTagName('img');
		}
		foreach ($images as $image){
			$imgs[]=trim($image->baseURI.$image->getAttribute('src'));
		}		
		if($startdate!=0 && $startdate==$result['start']){
			$result['start']=$result['start']+(20*3600); // default start time: 20°°
		}
	}

	/** cosmic dawn **/

	if (!isset($result['start'])){
		return false;
	}


	foreach ($links as $url){
		$url->save();
	}

	$starttime=$result['start'];
	$result['start']=date($db_time_format,$starttime);

	if (!isset($result['end'])){
		$endtime=$starttime+2*3600; // 2h later
		$result['end']=date($db_time_format,$endtime);
	}
	$result['links']=$links;
	if (count($imgs)>0){
		$result['images']=$imgs;
	}

	return $result;
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

function grep_event_title($source){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_title'));
}

function grep_event_description($source){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_description'));
}

function grep_event_start($source){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_start'));
}

function grep_event_end($source){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_end'));
}

function grep_event_location($source,$default=null){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_location'));
}

function grep_event_coords($source,$default=null){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_coords'));
}

function grep_event_tags($source,$additional=null){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_tags'));
}

function grep_event_links($source){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_links'));
}

function grep_event_images($source){
	// TODO
	return loc('%method not implemented, yet',array('%method','grep_event_images'));
}

function already_imported($event_url){
	// TODO
	print loc('%method not implemented, yet',array('%method','already_imported'));
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
		$source      = file_get_contents($event_url);
		$title       = grep_event_title($source);
		$description = grep_event_description($source);
		$start       = grep_event_start($source);
		$end	  	 = grep_event_end($source);
		$location    = grep_event_location($source,$site_data['location']); // fallback
		$coords      = grep_event_coords($source,$site_data['coords']); // fallback
		$tags		 = grep_event_tags($source,$site_data['tags']); // merge
		$links		 = grep_event_links($source);
		$images		 = grep_event_images($source);
		
		$event = appointment::create($title, $description, $start, $end, $location, $coords, $tags, $links, $images); // TODO: add params
		$existing_event = appointment::get_imported($event_url);
		if ($existing_event != null){
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
				$existing_event->add_image($image);
			}			
			$existing_event->save(); 
		} else {
			$event->save(); // TODO: currently does not save tags, links and images
		}
	}
}

?>