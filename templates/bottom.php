<?php if ($format=='ical') { ?>
X-WR-CALNAME:OpenCloudCal
END:VCALENDAR
<?php } else if ($format=='html') {	
    if (strlen($warnings)>3){
      echo '<div class="warnings">'.$warnings.'</div>'.PHP_EOL;
    }
    ?>
    <div class="bottomline">
    <?php echo 'OpenCloudCal 0.6 - '.str_replace('%link', 'https://github.com/SRSoftware/OpenCloudCal', loc('Proudly developed using PHP and JavaScript. Find the sources at <a href="%link">Github</a>.')); ?>
    </div>
  </body>
</html>
<?php } ?>