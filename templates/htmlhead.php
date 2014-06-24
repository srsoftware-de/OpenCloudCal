<?php if ($format=='ical') { ?>
BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
PRODID:<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]".PHP_EOL; ?>
<?php } else if ($format=='webdav') {?>
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
<?php } ?>
