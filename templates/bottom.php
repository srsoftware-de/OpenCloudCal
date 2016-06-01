<?php if ($format=='ical') {
	echo icalLine('X-WR-CALNAME','OpenCloudCal');
	echo icalLine('END','VCALENDAR');
} else if ($format=='html') {	
    if (strlen($notifications)>3){
      echo '<div class="notifications">'.$notifications.'</div>'.PHP_EOL;
    }
	  if (strlen($warnings)>3){
      echo '<div class="warnings">'.$warnings.'</div>'.PHP_EOL;
    }
    ?>
    <br/>
    <div class="bottomline">
    <?php echo 'OpenCloudCal 0.44 - '.str_replace('%link', 'https://github.com/keawe-software/OpenCloudCal', loc('Proudly developed using PHP and JavaScript. Find the sources at <a href="%link">Github</a>.')); ?>
    </div>
  </body>
</html>
<?php } ?>
