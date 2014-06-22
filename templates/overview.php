<?php if ($format=='ical') {
foreach ($appointments as $app){?>
BEGIN:VEVENT
UID:<?php echo $app->id.'@'.$_SERVER['HTTP_HOST'].PHP_EOL; ?>
DTSTART:<?php echo str_replace(array('-',' ',':'),array('','T',''),$app->start).'Z'.PHP_EOL; ?>
CATEGORIES:<?php echo $app->tags(',').PHP_EOL; ?>
CLASS:PUBLIC
DESCRIPTION:<?php echo str_replace("\r\n","\\n",$app->description).PHP_EOL; ?>
DTSTAMP:<?php echo str_replace(array('-',' ',':'),array('','T',''),$app->start).'Z'.PHP_EOL; ?>
GEO:<?php echo $app->coords['lat'].'\;'.$app->coords['lon'].PHP_EOL;?>
LOCATION:<?php echo $app->location.PHP_EOL; ?>
SUMMARY:<?php echo $app->title.PHP_EOL; ?>
<?php
foreach ($app->urls as $url){
	print 'URL:'.$url->address.PHP_EOL;
} 
?>
DTEND:<?php echo str_replace(array('-',' ',':'),array('','T',''),$app->end).'Z'.PHP_EOL; ?>
END:VEVENT	
<?php } // foreach ?>
<?php } else {?>
<h2><a href='.'><?php echo loc('appointment overview');?></a></h2>
<table class="appointments">
  <tr class="appointment">
    <th class="datestart"><?php echo loc('Start date'); ?></th>
    <th class="title"><?php echo loc('Title'); ?></th>
    <th class="coords"><?php echo loc('Location');?></th>
    <th class="tags"><?php echo loc('Tags'); ?></th>
    <th class="edit"><?php echo loc('Actions'); ?></th>
    </tr>

<?php
foreach ($appointments as $app){
  print '<tr class="appointment">'.PHP_EOL;
  print '  <td class="datestart">'.$app->start.'</th>'.PHP_EOL;
  print '  <td class="title"><a href=".?show='.$app->id.'">'.$app->title.'</a></th>'.PHP_EOL;  
  print '  <td class="location">'.$app->location;
  
  if ($app->coords){
		print '<br/><a href="'.$app->mapLink().'">'.implode(', ',$app->coords).'</a>';
	}
	print '  </td>'.PHP_EOL;
  print '  <td class="tags">'.$app->tagLinks().'</td>'.PHP_EOL;
  print '  <td class="edit">'.PHP_EOL;
  print '<a class="button" href="?edit='.$app->id.'">'.loc('edit').'</a>'.PHP_EOL;
  print '<a class="button" href="?delete='.$app->id.'">'.loc('delete').'</a>'.PHP_EOL;
  print '  </td>'.PHP_EOL;
  print '</tr>'.PHP_EOL;  
}
?>
</table>
<div class="bottomline right">
<a class="button" href="?<?php if (isset($_GET['tag'])) echo 'tag='.$_GET['tag'].'&'; ?>format=ical">iCal</a>
</div>
<?php } ?>