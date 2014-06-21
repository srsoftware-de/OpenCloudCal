<h2><?php echo $appointment->title ?></h2>
<?php 
	echo '<a href="?edit='.$appointment->id.'">'.loc('edit').'</a>&nbsp;'; 
	echo '<a href="?delete='.$appointment->id.'">'.loc('delete').'</a>'.PHP_EOL;
	?>
<div id="detail_time"><?php echo $appointment->start.' - '.$appointment->end; ?></div>
<div id="description"><?php echo str_replace("\n", "<br/>\n", $appointment->description); ?></div>
<div id="location"><?php echo $appointment->location; ?></div>
<div id="tags"><?php echo loc('tags').': '.$appointment->tagLinks(); ?></div>
<div id="coordinates"><?php
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
?></div>