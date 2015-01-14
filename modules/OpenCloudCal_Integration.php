<?php
/*
 Plugin Name: OpenCloudCal Integration
Plugin URI: http://cal.srsoftware.de/
Description: The OpenCloudCal Integration plugin allows to integrate appointments listed in the Open Cloud Calendar by a tag. Just use the text <strong>opencloudcal:keyword</strong> wherever you want.
Version: 1.0
Author: Stephan Richter
Author URI: http://srsoftware.de/
Update Server: https://github.com/SRSoftware/OpenCloudCal
Min WP Version: 1.5
Max WP Version: 2.0.4
*/

if (! function_exists('replace_open_cloudcal_tags')){

	function occ_icsToArray($occ_paramUrl) {
		$occ_icsFile = str_replace(';VALUE=DATE','',file_get_contents($occ_paramUrl));
		$occ_icsData = explode('BEGIN:', $occ_icsFile);
		foreach($occ_icsData as $occ_key => $occ_value) {
			$occ_icsDatesMeta[$occ_key] = explode("\n", $occ_value);
		}
		$occ_icsDates=array();
		foreach($occ_icsDatesMeta as $occ_key => $occ_value) {
			foreach($occ_value as $occ_subKey => $occ_subValue) {
				if ($occ_subValue != '') {
					if ($occ_key != 0 && $occ_subKey == 0) {
						$occ_icsDates[$occ_key]['BEGIN'] = $occ_subValue;
					} else {
						$occ_subValueArr = explode(':', $occ_subValue, 2);
						$occ_icsDates[$occ_key][$occ_subValueArr[0]] = $occ_subValueArr[1];
					}
				}
			}
		}

		return $occ_icsDates;
	}
	
	function localized_time($date,$localtime_settings){
		$summertimeOffset=3600*date('I',$date); // date('I',$timestamp) returns 0 or 1 for winter or summer time
		$sec_offset=3600*$localtime_settings['offset'];
		return date($localtime_settings['format'],$date+$sec_offset+$summertimeOffset);
	}

	function getUTCtimestamp($datetime,$timezone=null){
		return substr($datetime, 0,4).'-'.substr($datetime, 4,2).'-'.substr($datetime, 6,2).' '.	substr($datetime, 9,2).':'.substr($datetime, 11,2).':'.substr($datetime, 13,2);		
	}

	function get_open_cloudcal_replacement($occ_key){
		$localtime_settings=array('offset'=>0,'format'=>'Y-m-d H:i:s');
		
		$key_parts=explode(':', $occ_key,4);
		$len=count($key_parts);		
		$occ_key=trim($key_parts[1]);
		$occ_url='http://cal.srsoftware.de/?tag='.$occ_key.'&format=ical';
		if ($len>2) {
			$localtime_settings['offset']=(int)$key_parts[2];
		}
		if ($len>3) {
			$localtime_settings['format']=str_replace('[space]', ' ', $key_parts[3]);
		}
				
		$occ_dates=occ_icsToArray($occ_url);
		$occ_output='<table class="cloudcal"><thead><tr><th class="appointment_date">Datum</th><th class="appointment_title">Ereignis</th><th class="appointment_description">Beschreibung</th></tr></thead><tbody>';
		
		foreach ($occ_dates as $occ_date){
			if (trim($occ_date['BEGIN'])=="VCALENDAR") continue;
			$occ_start=strtotime(getUTCtimestamp($occ_date['DTSTART']));
			$occ_start=localized_time($occ_start,$localtime_settings); // Germany has 1 hr offset to UTC
			$occ_output.='<tr>';
			$occ_output.='<td class="appointment_date"><nobr>'.$occ_start.'</nobr></td>';
			$occ_output.='<td class="appointment_title"><a href="'.$occ_date['URL'].'">'.$occ_date['SUMMARY'].'</a></td>';
			$occ_output.='<td class="appointment_description">'.str_replace('\n', "<br/>\n", $occ_date['DESCRIPTION']).'</td>';
			$occ_output.='</tr>';
		}
		
		$occ_output.='</tbody></table><div class="addappointment"><a href="http://cal.srsoftware.de">Neues Ereignis eintragen.</a> Benutze bei OpenCloudCal das Schlüsselwort <strong>'.$occ_key.'</strong>, damit der Termin hier erscheint.</div>';
		return $occ_output;
	}

	function replace_open_cloudcal_tags($content){
		$opencloudcal_content = $content;
		$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		while (false !== $occ_pos){
			$occ_end_space=strpos($opencloudcal_content, ' ',$occ_pos);
			$occ_end_tag=strpos($opencloudcal_content, '<',$occ_pos);
			$occ_end_newline=strpos($opencloudcal_content, "\n",$occ_pos);
			if (false === $occ_end_space){
				$occ_end_space = PHP_INT_MAX;
			}
			if (false === $occ_end_tag){
				$occ_end_tag = PHP_INT_MAX;
			}
			if (false === $occ_end_newline){
				$occ_end_newline = PHP_INT_MAX;
			}
			$occ_end = min($occ_end_newline, $occ_end_tag, $occ_end_space);
			if ($occ_end == PHP_INT_MAX){
				break;
			}				
			$occ_key=substr($opencloudcal_content, $occ_pos,$occ_end-$occ_pos);
			$opencloudcal_content=str_replace($occ_key, get_open_cloudcal_replacement($occ_key), $opencloudcal_content);
				
			$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		}

		return $opencloudcal_content;
	} # function replace_open_cloudcal_tags()

	add_filter('the_content', 'replace_open_cloudcal_tags');
}?>