<?php
class Rosenkeller{
	private static $event_list_url = 'https://rosenkeller.org/programm.html';
	public static function read_events(){
		$xmlDoc = load_xml($event_list_url);
		print_r($xmlDoc);		
	}
}