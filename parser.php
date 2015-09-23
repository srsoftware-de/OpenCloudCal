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
				}
			}
		}
	}
	if (stripos($result, '://')===false){
		$result=dirname($site).'/'.$result;
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
	$date=$matches[0];
	return $date;
}

function extract_time($text){
	preg_match('/\d?\d:\d?\d/', $text, $matches);
	$time=$matches[0];
	return $time;
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
	$result=array();
	$links=array();
	$links[]=url::create(null, $page,loc('Event page'));
	$imgs=array();
	
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);	
	
	/** Rosenkeller **/
	$divs=$xml->getElementsByTagName('div');	
	foreach ($divs as $div){
		foreach ($div->attributes as $attr){
			if ($attr->name == 'class' && $attr->value=='event-description'){
				$text=trim($div->childNodes->item(0)->nodeValue);
				if (strlen($text)<10){
					$text=trim($div->childNodes->item(1)->nodeValue);
				}
				$result['text']=$text;
				break;
			}
		}
		if (isset($result['text'])){
			break;
		}
	}
	
	$data=$xml->getElementsByTagName('i');
	foreach ($data as $info){
		if ($info->attributes){
			foreach ($info->attributes as $attr){
				if ($attr->name == 'class'){
					if (strpos($attr->value, 'fa-calendar') !== false){
						$result['start']=parser_parse_date($info->nextSibling->wholeText);
						break;
					}
					if (strpos($attr->value, 'fa-building') !==false){
						$result['place']=$info->nextSibling->wholeText;
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
		$die=false;
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
			if (strlen($text)>200){				
				$result['text']=$text;
				continue;
			}
			print_r($result);
			print "\n";
			print_r($text);
			print "\n";
			die();
		}		
	}
	/** Wagner **/

	foreach ($links as $url){
		$url->save();
	}
	
	$starttime=$result['start'];
	$result['start']=date($db_time_format,$starttime-getTimezoneOffset($starttime));
	
	if (!isset($result['end'])){
		$endtime=$starttime+2*3600; // 2h later
		$result['end']=date($db_time_format,$endtime-getTimezoneOffset($endtime));
	}
	$result['links']=$links;
	if (count($imgs)>0){
		$result['images']=$imgs;
	}
	
	return $result;
}

function parserImport($site,$tags=null,$coords=null){
	if (!isset($site) || empty($site)){
		warn('You must supply an adress to import from!');
		return;
	}
	$program_page=find_program_page($site);
	$event_pages=find_event_pages($program_page);
	$events = array();
	foreach ($event_pages as $event_page){
		$event_data=parse_event($event_page);		
		if (!isset($event_data['coords'])){
			$event_data['coords']=$coords;
		}
		if (isset($tags) && $tags!=null){
			$event_data['tags']=array_merge($event_data['tags'],$tags);
		}		
		$appointment=appointment::create($event_data['title'], $event_data['text'], $event_data['start'], $event_data['end'], $event_data['place'], $event_data['coords'],false);
		$appointment->safeIfNotAlreadyImported($event_data['tags'],$event_data['links']);
	}
}

?>