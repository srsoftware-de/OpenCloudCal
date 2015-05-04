<?php

if ($format=='ical') {
	foreach ($appointments as $app){
  	echo $app->toVEvent();
	} // foreach








} else if ($format=='html') { ?>
<h2 class="overview">
	<a href='.'><?php echo loc('appointment overview');?> </a>
</h2>
<form class="options" action="." method="GET">
	<?php echo loc('Number of entries shown'); ?>:
	<button name="limit" value="1000">1000</button>
	<button name="limit" value="100">100</button>
	<button name="limit" value="50">50</button>	
	<button name="limit" value="20">20</button>	
	<button name="limit" value="10">10</button> |	
	<button name="past" value="true"><?php echo loc('show previous events')?></button>
</form>
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
    <td class="datestart"><?php echo clientTime($app->start); ?></th>
    <td class="title"><a href="?show=<?php echo $app->id; ?>"><?php echo $app->title; ?></a></td>
    <td class="location"><?php echo $app->location;
    if ($app->coords){ ?>
      <br/><a href="<?php echo $app->mapLink() ?>"><?php echo implode(', ',$app->coords); ?></a> <?php
  	} // if
    ?> 
	  </td>
    <td class="tags"><?php echo $app->tagLinks(); ?></td>
	  <td class="edit">
        <form action="." method="POST">
        <div class="email">	
	<?php echo loc('email - bots only'); ?>
	<input type="text" name="email" />
	</div>
        <button name="clone" value="<?php echo $app->id; ?>" type="submit"><?php echo loc('clone'); ?></button>
        <button name="edit" value="<?php echo $app->id; ?>" type="submit"><?php echo loc('edit'); ?></button>
        <button name="delete" value="<?php echo $app->id; ?>" type="submit"><?php echo loc('delete'); ?></button>
      </form>
    </td>
	</tr>
<?php } // foreach
?>
</table>
<form class="bottomline right">
  <?php
    if (isset($_GET['tag'])) echo '<input type="hidden" name="tag" value="'.$_GET['tag'].'">';
    if (isset($_GET['limit'])) echo '<input type="hidden" name="limit" value="'.$_GET['limit'].'">';
    if (isset($_GET['past'])) echo '<input type="hidden" name="past" value="'.$_GET['past'].'">';
  ?>
  <button type="submit" name="import" value="ical"><?php print loc('import iCal'); ?></button>
  <button type="submit" name="format" value="webdav"><?php print loc('WebDAV'); ?></button>
  <button type="submit" name="format" value="ical"><?php print loc('iCal'); ?></button>
</form>
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
