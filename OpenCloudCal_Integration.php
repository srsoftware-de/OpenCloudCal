
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
	
	function replace_open_cloudcal_tags($content){
		$opencloudcal_content = $content;
		$occ_pos=strpos($opencloudcal_content, 'opencloudcal:');
		while (false !== $occ_pos){
			$occ_end=strpos($opencloudcal_content, ' ');
			$occ_key=substr($opencloudcal_content, $occ_pos,$occ_end-$occ_pos);
			$opencloudcal_content=str_replace($occ_key,strtoupper($occ_key) , $opencloudcal_content);			
		}
		
		return $opencloudcal_content;
	} # function replace_open_cloudcal_tags()
	
	add_filter('the_content', 'replace_open_cloudcal_tags');	
}



?>