<?php if ($format=='html') { ?>
<h2>
	<?php echo $appointment->title ?>
</h2>
<form class="detailactions" action="." method="GET">
  <button type="submit"><?php echo loc('Back to overview'); ?></button>
  <button type="submit" name="edit" value="<?php echo $appointment->id; ?>"><?php echo loc('edit'); ?></button>
  <button type="submit" name="clone" value="<?php echo $appointment->id; ?>"><?php echo loc('clone'); ?></button>
  <button type="submit" name="delete" value="<?php echo $appointment->id; ?>"><?php echo loc('delete'); ?></button>
</form>
<div id="detail_time">
	<?php echo $appointment->start.' - '.$appointment->end; ?>
</div>
<div id="description">
	<?php echo str_replace("\n", "<br/>\n", $appointment->description); ?>
</div>
<div id="location">
	<?php echo loc('Location').': '.$appointment->location; ?>
</div>
<div id="tags">
	<?php echo loc('tags').': '.$appointment->tagLinks(); ?>
</div>

	<?php
	if (isset($appointment->sessions) && count($appointment->sessions)>0){
		echo '<div id="sessions">'.PHP_EOL;
		echo '<h3>'.loc('Sessions').'</h3>'.PHP_EOL;
		echo '<table class="sessions">'.PHP_EOL;
		echo '<tr class="session">'.PHP_EOL;
		echo '  <th class="sessionstart">'.loc('Start').'</th>'.PHP_EOL;
		echo '  <th class="sessionend">'.loc('End').'</th>'.PHP_EOL;
		echo '  <th class="description">'.loc('Description').'</th>'.PHP_EOL;
		echo '  <th class="actions">'.loc('Actions').'</th>'.PHP_EOL;
		echo '</tr>'.PHP_EOL;
		foreach ($appointment->sessions as $session){
			print '<tr class="session">'.PHP_EOL;
			print '  <td class="sessionstart">'.$session->start.'</td>'.PHP_EOL;
			print '  <td class="sessionend">'.$session->end.'</td>'.PHP_EOL;
			print '  <td class="description">'.$session->description.'</td>'.PHP_EOL;
			print '  <td class="delsession"><a class="button" href="?show='.$appointment->id.'&deletesession='.$session->id.'">'.loc('delete').'</a></td>'.PHP_EOL;
			print '</tr>'.PHP_EOL;
		}
		echo '</table>';
		echo '</div>'.PHP_EOL;
	}

	if (isset($appointment->urls) && count($appointment->urls)>0){
		echo '<div id="links">'.PHP_EOL;
		echo '<h3>'.loc('Links').'</h3>'.PHP_EOL;
		echo '<table class="links">'.PHP_EOL;
		echo '<tr class="link">'.PHP_EOL;
		echo '  <th class="description">'.loc('Description').'</th>'.PHP_EOL;
		echo '  <th class="address">'.loc('Address').'</th>'.PHP_EOL;
		echo '  <th class="actions">'.loc('Actions').'</th>'.PHP_EOL;
		echo '</tr>'.PHP_EOL;
		foreach ($appointment->urls as $url){
			print '<tr class="link">'.PHP_EOL;
			print '  <td class="description"><a href="'.$url->address.'">'.$url->description.'</a></td>'.PHP_EOL;
			print '  <td class="address"><a href="'.$url->address.'">'.$url->address.'</a></td>'.PHP_EOL;
			print '  <td class="dellink"><a class="button" href="?show='.$appointment->id.'&deletelink='.$url->id.'">'.loc('delete').'</a></td>'.PHP_EOL;
			print '</tr>'.PHP_EOL;
		}
		echo '</table>';
		echo '</div>'.PHP_EOL;
	}
	
	?>
<div id="coordinates">
	<h3><?php echo loc('Map'); ?></h3>
	<?php
	if ($appointment->coords){ ?>
	<div id="mapdiv"></div>
	<noscript>
		<?php echo loc("You decided to not use JavaScript. That is totally ok, but you will not be able to use the interactive map. Don't worry, you can still enter coordinates manually!"); ?>
	</noscript>
	<script src="scripts/OpenLayers.js"></script>
	<script>
    	map = new OpenLayers.Map("mapdiv");
    	map.addLayer(new OpenLayers.Layer.OSM()); 
    	var lonLat = new OpenLayers.LonLat( <?php echo $appointment->coords['lon'].','.$appointment->coords['lat'];?>  );
    	lonlat=lonLat.transform(new OpenLayers.Projection("EPSG:4326"),map.getProjectionObject()); 
    	    												// transform from WGS 1984 to Spherical Mercator Projection 
    	var zoom=14;
 	    var markers = new OpenLayers.Layer.Markers( "Markers" );
  	  map.addLayer(markers); 
    	markers.addMarker(new OpenLayers.Marker(lonLat)); 
    	map.setCenter (lonLat, zoom);
  	</script>
	<?php }
	?>
</div>
<div class="bottomline right">
<a class="button" href="?show=<?php echo $appointment->id; ?>&format=ical">iCal</a>
</div>
<?php 





} else if ($format=='ical') { ?>
BEGIN:VEVENT
UID:<?php echo $appointment->id.'@'.$_SERVER['HTTP_HOST'].PHP_EOL; ?>
DTSTART:<?php echo str_replace(array('-',' ',':'),array('','T',''),$appointment->start).'Z'.PHP_EOL; ?>
CATEGORIES:<?php echo $appointment->tags(',').PHP_EOL; ?>
CLASS:PUBLIC
DESCRIPTION:<?php echo str_replace("\r\n","\\n",$appointment->description).PHP_EOL; ?>
DTSTAMP:<?php echo str_replace(array('-',' ',':'),array('','T',''),$appointment->start).'Z'.PHP_EOL; ?>
GEO:<?php echo $appointment->coords['lat'].'\;'.$appointment->coords['lon'].PHP_EOL;?>
LOCATION:<?php echo $appointment->location.PHP_EOL; ?>
SUMMARY:<?php echo $appointment->title.PHP_EOL; ?>
<?php
foreach ($appointment->urls as $url){
	print 'URL:'.$url->address.PHP_EOL;
} 
?>
DTEND:<?php echo str_replace(array('-',' ',':'),array('','T',''),$appointment->end).'Z'.PHP_EOL; ?>
END:VEVENT
<?php } ?>
