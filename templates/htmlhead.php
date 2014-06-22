<?php if ($format=='ical') { ?>
BEGIN:VCALENDAR
VERSION:2.0
METHOD:PUBLISH
PRODID:<?php echo "https://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]".PHP_EOL; ?>
<?php } else {?>
<html>
  <head>
    <meta charset="utf-8"/>
    <title>OpenCloudCal</title>
    <link type="text/css" rel="stylesheet" media="all" href="templates/css/style.css" />
   </head>
 <body>
<?php } ?>