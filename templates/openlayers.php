<?php if ($format=='html') { ?>
<div id="map">
<noscript>
<?php echo loc("You decided to not use JavaScript. That is totally ok, but you will not be able to use the interactive map. Don't worry, you can still enter coordinates manually!"); ?>
</noscript>
<script src="scripts/OpenLayers.js"></script>
<script defer="defer" type="text/javascript">

  var toProjection = new OpenLayers.Projection("EPSG:4326");
  OpenLayers.Control.Click = OpenLayers.Class(OpenLayers.Control, {                
    defaultHandlerOptions: {
      'single': true,
      'double': false,
      'pixelTolerance': 0,
      'stopSingle': false,
      'stopDouble': false
    },
    initialize: function(options) {
      this.handlerOptions = OpenLayers.Util.extend(
        {}, this.defaultHandlerOptions                            
      );
      OpenLayers.Control.prototype.initialize.apply(
        this, arguments
      ); 
      this.handler = new OpenLayers.Handler.Click(
        this, {
          'click': this.trigger
        }, this.handlerOptions
      );
    }, 
    
    trigger: function(e) {
      var lonlat = map.getLonLatFromPixel(e.xy).transform(map.getProjectionObject(), toProjection);
      var elem=document.getElementById('coords');
      elem.value=(Math.round(10000*lonlat.lat)/10000) + "," + (Math.round(10000*lonlat.lon)/10000);
    }
  });



  var map = new OpenLayers.Map('map');
  var wms = new OpenLayers.Layer.OSM();
  map.addLayer(wms);                              
  map.zoomToMaxExtent();
  var click = new OpenLayers.Control.Click();
  map.addControl(click);
  click.activate();

</script>
</div>
<?php } ?>