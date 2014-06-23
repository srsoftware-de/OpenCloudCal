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
<?php } // foreach








} else if ($format=='html') { ?>
<h2 class="overview">
	<a href='.'><?php echo loc('appointment overview');?> </a>
</h2>
<div class="options">
	<?php echo loc('Number of entries shown'); ?>:
	<a class="button" href="?limit=1000">1000</a>
	<a class="button" href="?limit=100">100</a>
	<a class="button" href="?limit=50">50</a>	
	<a class="button" href="?limit=20">20</a>	
	<a class="button" href="?limit=10">10</a> |	
	<a class="button" href="?past=true"><?php echo loc('show previous events')?></a>
</div>
<table class="appointments">
	<tr class="appointment">
		<th class="datestart"><?php echo loc('Start date'); ?></th>
		<th class="title"><?php echo loc('Title'); ?></th>
		<th class="coords"><?php echo loc('Location');?></th>
		<th class="tags"><?php echo loc('Tags'); ?></th>
		<th class="edit"><?php echo loc('Actions'); ?></th>
	</tr>

	<?php
	foreach ($appointments as $app){ ?>
  <tr class="appointment">
    <td class="datestart"><?php echo $app->start; ?></th>
    <td class="title"><a href="?show=<?php echo $app->id; ?>"><?php echo $app->title; ?></a></td>
    <td class="location"><?php echo $app->location;
    if ($app->coords){ ?>
      <br/><a href="<?php echo $app->mapLink() ?>"><?php echo implode(', ',$app->coords); ?></a> <?php
  	} // if
    ?> 
	  </td>
    <td class="tags"><?php echo $app->tagLinks(); ?></td>
	  <td class="edit">
      <form action="." method="GET">
        <button name="clone" value="<?php echo $app->id; ?>" type="submit"><?php echo loc('clone'); ?></button>
        <button name="edit" value="<?php echo $app->id; ?>" type="submit"><?php echo loc('edit'); ?></button>
        <button name="delete" value="<?php echo $app->id; ?>" type="submit"><?php echo loc('delete'); ?></button>
      </form>
    </td>
	</tr>
<?php } // foreach
?>
</table>
<div class="bottomline right">
	<a class="button"
		href="?<?php
		 if (isset($_GET['tag'])) echo 'tag='.$_GET['tag'].'&';
		 if (isset($_GET['limit'])) echo 'limit='.$_GET['limit'].'&';		 	
		 if (isset($_GET['past'])) echo 'past='.$_GET['past'].'&';		 	
		 ?>format=webdav">webDAV</a>
	<a class="button"
		href="?<?php
		 if (isset($_GET['tag'])) echo 'tag='.$_GET['tag'].'&';
		 if (isset($_GET['limit'])) echo 'limit='.$_GET['limit'].'&';		 	
		 if (isset($_GET['past'])) echo 'past='.$_GET['past'].'&';		 	
		 ?>format=ical">iCal</a>
</div>
<?php






 } else if ($format=='webdav') {?>
<h1>
	Index for calendar<?php if (isset($_GET['tag'])) echo '/'.$_GET['tag']?>
</h1>
<table>
	<tr>
		<th width="24"></th>
		<th>Name</th>
		<th>Type</th>
		<th>Size</th>
		<th>Last modified</th>
	</tr>
	<tr>
		<td colspan="5"><hr /></td>
	</tr>
	<tr>
		<td>
			<a href="?format=webdav">
				<img src="templates/img/folder.png" width="24" alt="Parent" />
			</a>
		</td>
		<td>
			<a href="?format=webdav">..</a>
		</td>
		<td>[parent]</td>
		<td></td>
		<td></td>
	</tr>
	<?php
	foreach ($appointments as $app){ ?>
<tr>
<td><a href="?format=ical&show=<?php echo $app->id; ?>"><img src="templates/img/file.png" alt="" width="24" /></a></td>
<td><a href="?format=ical&show=<?php echo $app->id; ?>">appointment<?php echo $app->id; ?>.ics</a></td>
<td>text/calendar; charset=utf-8</td>
<td>1024</td>
<td><?php echo str_replace(' ', 'T', $app->start); ?>+00:00</td>
</tr>
<?php } ?>
</table>
<?php } ?>
