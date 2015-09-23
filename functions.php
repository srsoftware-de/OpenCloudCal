<?php

/***** very basic functions ******/
function loc($text){
	global $locale;
	if (isset($locale) && array_key_exists($text,$locale)){
		return $locale[$text];
	}
	return $text;
}

function notify($message){
	global $notifications;
	$notifications.='<p>'.loc($message).'</p>'.PHP_EOL;
}

function warn($message){
	global $warnings;
	$warnings.='<p>'.loc($message).'</p>'.PHP_EOL;
}

function startsWith($haystack, $needle){
	return $needle === "" || strpos($haystack, $needle) === 0;
}
function endsWith($haystack, $needle){
	return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}
/***** end of: very basic functions ******/

/***** time calculations ******/
function getTimezoneOffset($timestamp){
	if (!isset($_SESSION)){
		return 0;
	}
	if (!isset($_SESSION['country'])){
		return 0;
	}
	if ($_SESSION['country']=='UTC'){
		return 0;
	}
	$summertimeOffset=3600*date('I',$timestamp); // date('I',$timestamp) returns 0 or 1 for winter or summer time
	$code=$_SESSION['country'];
	if ($code=='DE'){
		return $summertimeOffset+3600; // one additional hour to UTC
	}
	warn(str_replace('%tz',$_SESSION['country'],loc('Unknown timezone: %tz')));
	return null;
}

function clientTime($timestamp){
	global $db_time_format;
	$secs=strtotime($timestamp);
	return date($db_time_format,$secs+getTimezoneOffset($secs));
}
/***** end of: time calculations ******/


/* this converts a datetime array to a timestamp (seconds since 1960 or somethin like that).
 * if a timezone (country code) is given, it is checked whether the referenced date is in summertime and
* the timestamp is adjusted apropriately to reflect the date in UTC.
* format of array:
* timstamp = arrray(year => YYYY, month => MM, day => dd, hour => hh, minute => mm, timezone => 'DE') // or UTC
*/
function parseDateTime($array){
	if (!isset($array['year'])){
		return false;
	}
	if (!isset($array['month'])){
		return false;
	}
	if (!isset($array['day'])){
		return false;
	}

	$d_string=$array['year'].'-'.$array['month'].'-'.$array['day'];
	$secs=strtotime($d_string);

	if (isset($array['hour'])){
		$hour=(int) $array['hour'];
		$secs+=3600 * $hour;
	}
	if (isset($array['minute'])){
		$min=(int) $array['minute'];
		$secs+=60 * $min;
	}
	if (isset($array['addtime'])){
		$secs+=(int)$array['addtime'];
	}
	$secs-=getTimezoneOffset($secs);
	return $secs;
}

function isSpam($data){
	if (!empty($data['email'])){
		return true; // no warning: Spam!
	}
	if (!empty($data['title'])){
		if (strpos($data['title'],'http') !== false) {
			return true;
		}
	}
	if (!empty($data['description'])){
		if (strpos($data['description'],'http') !== false) {
			return true;
		}
	}
	
	return false;
}

function parseAppointmentData($data){
	global $db_time_format,$countries;
    if (isSpam($data)){
    	return false;
    }
	if (isset($data['timezone']) && array_key_exists($data['timezone'], $countries)){
		$_SESSION['country']=$data['timezone'];
	}
	if (empty($data['title'])){
		warn('no title given');
		return false;
	}
	if (strpos($data['title'],'http') !== false) {
		return false; // no warning: Spam!
	}
	$start=parseDateTime($data['start']);
	if (!$start){
		warn('invalid start date');
		return false;
	}
	$end=parseDateTime($data['end']);
	if (!$end){
		warn('invalid end date');
		return false;
	}
	if ($end<$start){
		$end=$start;
	}
	$start=date($db_time_format,$start);
	$end=date($db_time_format,$end);
	$app=appointment::create($data['title'],$data['description'],$start,$end,$data['location'],$data['coordinates'],false);
	if (isset($data['id'])){
		$app->id=$data['id'];
	}
	return $app;
}

function parseSessionData($data){
	global $db_time_format;
	if (empty($data['aid'])){
		warn('no appointment given');
		return false;
	}
	if (empty($data['description'])){
		warn('no description given');
		return false;
	}
	$start=parseDate($data['start']);
	if (!$start){
		warn('invalid start date');
		return false;
	}
	$end=parseDate($data['end']);
	if (!$end){
		warn('invalid end date');
		return false;
	}
	$start+=parseTime($data['start']);
	$end+=parseTime($data['end']);
	if ($end<$start){
		$end=$start;
	}
	$start=date($db_time_format,$start);
	$end=date($db_time_format,$end);
	$session=session::create($data['aid'],$data['description'],$start,$end,false); // create, but do not save, yet
	return $session;
}

function parseLinkData($data){
	global $db_time_format;
	if (isSpam($data)){
		return false;
	}
	if (empty($data['aid'])){
		warn('no appointment given');
		return false;
	}
	if (empty($data['description'])){
		warn('no description given');
		return false;
	}
	if (empty($data['url'])){
		warn('no url given');
		return false;
	}
	$url=$data['url'];
	if (!strpos($url,':')){
		$url='http://'.$url;
	}
	$url=url::create($data['aid'],$url,$data['description']);
	return $url;
}

function parseAttachmentData($data){
	global $db_time_format;
	if (isSpam($data)){
		return false;
	}
	if (empty($data['aid'])){
		warn('no appointment given');
		return false;
	}
	
	if (empty($data['url'])){
		warn('no url given');
		return false;
	}
	$url=$data['url'];
	if (!strpos($url,':')){
		$url='http://'.$url;
	}
	if (empty($data['mime'])){
		$data['mime'] = guess_mime_type($url);
	}	
	$url=url::create($data['aid'],$url,$data['mime']);
	return $url;
}

function guess_mime_type($url){
	$URL=strtoupper($url);
	if (endsWith($URL, 'JPG') || endsWith($URL, 'JPEG')){
		return 'image/jpeg';
	}
	if (endsWith($URL, 'PNG')){
		return 'image/png';
	}
	if (endsWith($URL, 'GIF')){
		return 'image/gif';
	}
	if (endsWith($URL, 'SVG')){
		return 'image/svg+xml';
	}
	return 'unknown';
}

function readTimezoneMode(&$stack){
	$mode=array();
	while (!empty($stack)){
		$line=trim(array_pop($stack));
		if (startsWith($line,'DTSTART:')){
			$mode['start']=substr($line, 8);
		} else if (startsWith($line,'TZNAME:')){
			$mode['name']=substr($line, 7);
		} else if (startsWith($line,'RRULE:')){
			$mode['r_rule']=substr($line, 6);
		} elseif (startsWith($line,'RDATE:')){
			$timezone['rdate']=substr($line,6);
		} elseif (startsWith($line,'TZNAME:')){
			$timezone['name']=substr($line,7);
		} else if (startsWith($line,'TZOFFSETTO:')){
			$mode['offset_to']=substr($line, 11);
		} else if (startsWith($line,'TZOFFSETFROM:')){
			$mode['offset_from']=substr($line, 13);
		} else if ($line=='END:DAYLIGHT'){
			return $mode;
		} else if ($line=='END:STANDARD'){
			return $mode;
		} else {
			warn('tag unknown to readTimezoneMode: '.$line);
			return false;
		}
	}
}

function readTimezone(&$stack){
	$timezone=array();
	while (!empty($stack)){
		$line=trim(array_pop($stack));

		if (startsWith($line,'TZID:')){
			$timezone['id']=substr($line, 5);
		} else if (startsWith($line,'X-LIC-LOCATION:')){
			$timezone['x-lic-location']=substr($line,15);
		} elseif ($line=='BEGIN:DAYLIGHT'){
			if (!isset($timezone['modes'])){
				$timezone['modes']=array();
			}
			$timezone['modes']['daylight']=readTimezoneMode($stack);
		} elseif ($line=='BEGIN:STANDARD'){
			if (!isset($timezone['modes'])){
				$timezone['modes']=array();
			}
			$timezone['modes']['standard']=readTimezoneMode($stack);
		} elseif ($line=='END:VTIMEZONE') {
			return $timezone;
		} else {
			warn('tag unknown to readTimezone: '.$line);
			return false;
		}
	}
}

function readMultilineFromIcal(&$stack){
	$text='';
	while (!empty($stack)){
		$line=rtrim(array_pop($stack));

		if (substr($line, 0,1)!=' '){
			array_push($stack, $line);
			return $text;
		}
		$text.=substr($line,1);
	}
}

function importIcal($url,$tags=null){
	if (!isset($url) || empty($url)){
		warn('You must supply an adress to import from!');
		return;
	}
	print "importing events from "+$url."<br/>\n";
	flush();
	$data=file($url);
	$len=count($data);
	if ($len<1){
		warn('This file contains no data!');
		return false;
	}
	if (trim($data[0]) != 'BEGIN:VCALENDAR'){
		warn('This file does not look like an iCal file!');
		return false;
	}
	$stack=array_reverse($data);
	$timezone=null; // here the problems start:
	// we don't know the client's timezone
	// probably we should save all appointments in UTC,
	// and handle the web interface always in CET/CEST
	// TODO: this needs to be implemented soon
	while (!empty($stack)){
		$line=trim(array_pop($stack));
		if ($line=='BEGIN:VCALENDAR') {
		} else if (startsWith($line,'VERSION:')) {
			$version=substr($line, 8);
		} else if (startsWith($line,'PRODID:')) {
			readMultilineFromIcal($stack);
		} else if (startsWith($line,'CALSCALE:')){
		} else if (startsWith($line,'METHOD:')){
		} else if (startsWith($line,'X-')){
		} else if ($line=='BEGIN:VTIMEZONE') {
			$timezone=readTimezone($stack);
		} else if ($line=='BEGIN:VEVENT') {
			$app=appointment::readFromIcal($stack,$tags,$timezone);
			//die();
		} else if ($line=='END:VCALENDAR') {
		} else {
			warn('unknown tag: '. $line);
			return false;
		}
	}
	// TODO: code here?
}
