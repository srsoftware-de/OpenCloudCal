
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
	
	function get_open_cloudcal_replacement($occ_key){
		$occ_key=str_replace('opencloudcal:', '', trim($occ_key));
		return '<script type="text/javascript">alert('.$occ_key.');</script>';
	}
	
	function replace_open_cloudcal_tags($content){
		$opencloudcal_content = $content;
		$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		while (false !== $occ_pos){
			$occ_end=strpos($opencloudcal_content, ' ');
			if ( false === $occ_end){
				break;
			}
			$occ_key=substr($opencloudcal_content, $occ_pos,$occ_end-$occ_pos-1);
			$opencloudcal_content=str_replace($occ_key, get_open_cloudcal_replacement($occ_key), $opencloudcal_content);
			$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		}
		
		return $opencloudcal_content;
	} # function replace_open_cloudcal_tags()
	
	add_filter('the_content', 'replace_open_cloudcal_tags');	
}



?>