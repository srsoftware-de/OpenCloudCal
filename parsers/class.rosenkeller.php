<?php
class Rosenkeller{
	private static $base_url = 'https://rosenkeller.org/';
	private static $event_list_page = 'programm.html';
	
	public static function read_events(){
		$xml = load_xml(self::$base_url . self::$event_list_page);
		$links = $xml->getElementsByTagName('a');
		$event_pages = array();		
		foreach ($links as $link){
			$page = $link->getAttribute('href');
			if (strpos($page, 'event_')===0) {
				$event_pages[$page]=true; // used as keys, so duplicates get removed
			}
		}
		foreach ($event_pages as $page => $dummy){
			self::read_event(self::$base_url . $page);
		}
	}
	
	public static function read_event($source_url){
		print $source_url.NL;
	}
}