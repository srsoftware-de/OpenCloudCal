<h2><?php echo loc('appointment overview');?></h2>
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
  print '  <td class="edit"><a href="?edit='.$app->id.'">'.loc('edit').'</a></td>'.PHP_EOL;
  print '</th>'.PHP_EOL;  
}
?>

</table>
