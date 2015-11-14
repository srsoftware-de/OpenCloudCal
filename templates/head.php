<?php if ($format=='ical') { 
	echo icalLine('BEGIN','VCALENDAR');
	echo icalLine('VERSION','2.0');
	echo icalLine('METHOD','PUBLISH');	
	echo icalLine('PRODID',"https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
} else if ($format=='webdav') {?>
<html>
	<head>
  	<title>Index for calendars/srichter/default calendar/ - SabreDAV 1.7.6-stable</title>
  	<style type="text/css">
  		body { Font-family: arial}
  		h1 { font-size: 150% }
  	</style>
  </head>
  <body>
<?php } else if ($format=='html') {?>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>OpenCloudCal</title>
    <link type="text/css" rel="stylesheet" media="all" href="templates/css/style.css" />
   </head>
 <body>
<form method="POST" class="timezone">
  <?php echo loc('timezone')?>:
  <select name="country" onchange='this.form.submit();'>
    <?php foreach ($countries as $code=>$country){
	   	if ($country == $_SESSION['country']){
	  		echo '<option value="'.$code.'" selected>'.$country.'</option>'.PHP_EOL;
		  } else {
			  echo '<option value="'.$code.'">'.$country.'</option>'.PHP_EOL;
		 	}
		}	?>
	</select>
	<button type="submit"><?php echo loc('change');?></button>
	<?php echo loc('Note: Summertime ("daylight saving time") will be automatically taken into account.')?>	
</form>
<?php } ?>
