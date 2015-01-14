<?php if ($format=='html') { ?>
<h2>
	<?php echo $appointment->title ?>
</h2>
<div id="detail_time_start">
	<?php echo loc('Start').': '.clientTime($appointment->start); ?>
</div>
<div id="detail_time_end">
  <?php echo loc('End').': '.clientTime($appointment->end); ?>
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
	if (isset($appointment->sessions) && count($appointment->sessions)>0){ ?>
		<div id="sessions">
			<h3><?php echo loc('Sessions'); ?></h3>
				<table class="sessions">
					<tr class="session">
						<th class="sessionstart"><?php echo loc('Start'); ?></th>
						<th class="sessionend"><?php echo loc('End'); ?></th>
		        <th class="description"><?php echo loc('Description'); ?></th>
		        <th class="actions"><?php echo loc('Actions'); ?></th>
		      </tr>
<?php foreach ($appointment->sessions as $session){ ?>
					<tr class="session">
					  <td class="sessionstart"><?php echo clientTime($session->start); ?></td>
					  <td class="sessionend"><?php echo clientTime($session->end); ?></td>
					  <td class="description"><?php echo $session->description; ?></td>
					  <td class="delsession">
					    <form class="deletesession" action="?show=<?php echo $appointment->id; ?>" method="POST">
					      <button type="submit" name="deletesession" value="<?php echo $session->id; ?>"><?php echo loc('delete');?></button>
					    </form>
					  </td>
					</tr>
<?php } ?>
				</table>
			</div>
<?php 	}





	if (isset($appointment->urls) && count($appointment->urls)>0){ ?>
		<div id="links">
		<h3><?php echo loc('Links'); ?></h3>
		<table class="links">
		<tr class="link">
		  <th class="description"><?php echo loc('Description'); ?></th>
		  <th class="address"><?php echo loc('Address'); ?></th>
		  <th class="actions"><?php echo loc('Actions'); ?></th>
		</tr>
<?php foreach ($appointment->urls as $url){ ?>
			<tr class="link">
			  <td class="description"><a href="<?php echo $url->address; ?>"><?php echo $url->description; ?></a></td>
			  <td class="address"><a href="<?php echo $url->address; ?>"><?php echo $url->address; ?></a></td>
			  <td class="dellink">
					<form class="dellink" action="?show=<?php echo $appointment->id; ?>" method="POST">
						<button type="submit" name="deletelink" value="<?php echo $url->id; ?>"><?php echo loc('delete');?></button>
					</form>
			   </td>
			</tr>
<?php } // foreach ?>
		</table>
		</div>
<?php } // if	





	if (isset($appointment->attachments) && count($appointment->attachments)>0){ ?>
		<div id="attachments">
		<h3><?php echo loc('attachments'); ?></h3>
<?php foreach ($appointment->attachments as $attachment){
			if (startsWith($attachment->description, 'image')){
				$image=$attachment->address;
			} else {
			  $image='http://upload.wikimedia.org/wikipedia/commons/0/04/Gnome-mime-text-x-install.png';
			}	?>      
			<div class="attachment">
			  <a href="<?php print $attachment->address; ?>"><img src="<?php print $image; ?>"></a>
				<form class="delattachment" action="?show=<?php echo $appointment->id; ?>" method="POST">
  				<button type="submit" name="deleteattachment" value="<?php echo $attachment->id; ?>"><?php echo loc('delete');?></button>
				</form>
			</div>
<?php } // foreach ?>
		</div>
<?php } // if	?>



<form class="detailactions" action="." method="POST">
  <h3><?php echo loc('Actions');?></h3>
  <button type="submit"><?php echo loc('Back to overview'); ?></button>
  <button type="submit" name="edit" value="<?php echo $appointment->id; ?>"><?php echo loc('edit'); ?></button>
  <button type="submit" name="clone" value="<?php echo $appointment->id; ?>"><?php echo loc('clone'); ?></button>
  <button type="submit" name="delete" value="<?php echo $appointment->id; ?>"><?php echo loc('delete'); ?></button>
</form>
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
<form class="bottomline right">
  <input type="hidden" name="show" value="<?php echo $appointment->id; ?>" />
  <button type="submit" name="format" value="ical">iCal</button>
</form>
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
