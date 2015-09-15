<?php

$sites = array("https://rosenkeller.org/index.html","http://www.wagnerverein-jena.de/");

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

function parse_date($text){
	return $text;
}

function parse_event($page){
	print $page."\n";
	$xml = new DOMDocument();
	@$xml->loadHTMLFile($page);
	$data=$xml->getElementsByTagName('i');
	$result=array();	
	if ($data->length>0){
		$info=$data->item(0);		
		while (true){
			if ($info->attributes){
				foreach ($info->attributes as $attr){
					if ($attr->name == 'class' && strpos($attr->value, 'fa-calendar') !== false){
						$result['date']=parse_date($info->nextSibling->wholeText);
						break;
					} else {
						print_r($attr);
					}
				}
			}
			if ($info->nextSibling){
				$info=$info->nextSibling;
			} else {
				break;
			}
		}
	}
	die();
	return $result;
}

function store_event($event){

}

foreach ($sites as $site){
	$program_page=find_program_page($site);
	$event_pages=find_event_pages($program_page);
	$events = array();
	foreach ($event_pages as $event_page){
		$event=parse_event($event_page);
		//store_event($event);
	}
}


?>