
<?php
/*
 Plugin Name: OpenCloudCal Integration
Plugin URI: http://cal.srsoftware.de/
Description: The OpenCloudCal Integration plugin allows to integrate appointments listed in the Open Cloud Calendar by a tag.
Version: 1.0
Author: Stephan Richter
Author URI: http://srsoftware.de/
Update Server: http://srsoftware.de/
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
		
		$occ_output='<table class="cloudcal"><thead><tr><th>Datum</th><th>Ereignis</th><th>Beschreibung</th></tr></thead><tbody>';
		
		foreach ($occ_dates as $occ_date){
			if (trim($occ_date['BEGIN'])=="VCALENDAR") continue;
			$occ_start=$occ_date['DTSTART'];
			$occ_output.='<tr>';
			$occ_output.='<td>'.substr($occ_start,0,4).'-'.substr($occ_start,4,2).'-'.substr($occ_start,6,2).'</td>';
			$occ_output.='<td style="font-weight: bold;"><a href="'.$occ_date['URL'].'">'.$occ_date['SUMMARY'].'</a></td>';
			$occ_output.='<td>'.str_replace('\n', "<br/>\n", $occ_date['DESCRIPTION']).'</td>';
			$occ_output.='</tr>';
		}
		
		$occ_output.='</tbody></table><br/><a href="http://cal.srsoftware.de">Neues Ereignis eintragen</a><br/>';
		return $occ_output;
	}

	function replace_open_cloudcal_tags($content){
		$opencloudcal_content = $content;
		$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		while (false !== $occ_pos){
			$occ_end=strpos($opencloudcal_content, ' ',$occ_pos);
			if ( false === $occ_end){
				break;
			}
			$occ_key=substr($opencloudcal_content, $occ_pos,$occ_end-$occ_pos);
			$opencloudcal_content=str_replace($occ_key, get_open_cloudcal_replacement($occ_key), $opencloudcal_content);
			$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		}

		return $opencloudcal_content;
	} # function replace_open_cloudcal_tags()

	add_filter('the_content', 'replace_open_cloudcal_tags');
}



?>