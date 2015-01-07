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


	function get_open_cloudcal_replacement($occ_key){
		$occ_key=str_replace('opencloudcal:', '', trim($occ_key));
		$occ_url='http://cal.srsoftware.de/?tag='.$occ_key.'&format=ical';
		
		$occ_dates=occ_icsToArray($occ_url);
		
		$occ_output='<table class="cloudcal"><thead><tr><th class="appointment_date">Datum</th><th class="appointment_title">Ereignis</th><th class="appointment_description">Beschreibung</th></tr></thead><tbody>';
		
		foreach ($occ_dates as $occ_date){
			if (trim($occ_date['BEGIN'])=="VCALENDAR") continue;
			$occ_start=$occ_date['DTSTART'];
			$occ_output.='<tr>';
			$occ_output.='<td class="appointment_date"><nobr>'.substr($occ_start,0,4).'-'.substr($occ_start,4,2).'-'.substr($occ_start,6,2);
			if ((strpos($occ_start, 'T') !== false) && (strpos($occ_start, 'Z') !== false)){
				$occ_output.=' '.substr($occ_start,9,2).':'.substr($occ_start, 11,2);
			}
			$occ_output.='</nobr></td>';
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