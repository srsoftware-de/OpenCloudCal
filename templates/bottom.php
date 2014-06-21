    <?php
    if (strlen($warnings)>3){
      echo '<div class="warnings">'.$warnings.'</div>'.PHP_EOL;
    }
    echo 'OpenCloudCal 0.2 - '.str_replace('%link', 'https://github.com/SRSoftware/OpenCloudCal', loc('Proudly developed using PHP and JavaScript. Find the sources at <a href="%link">Github</a>.')); 
    ?>
  </body>
</html>
