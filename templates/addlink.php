<?php
	include 'forms.php';
?>
<div class="addlink">
	<h2>
		<?php echo loc('add link');?>
	</h2>
	<form class="addlink" method="POST">
		<?php echo '<input type="hidden" name="newlink[aid]" value="'.$appointment->id.'" />'.PHP_EOL;?>	
		<div id="description">
			<?php echo loc('description'); ?>
			<input type="text" name="newlink[description]" />
		</div>
		<div id="url">
			<?php echo loc('url'); ?>
			<input type="text" name="newlink[url]" />
		</div>
			<div class="submit">
		  <input type="checkbox" id="addsession" name="addsession" />
		  <label for="addsession">
				<?php echo loc('Add a session to this appointment in the next step.'); ?>
			</label>
			<input type="checkbox" id="addlink" name="addlink" />
			<label for="addlink">			
				<?php echo loc('Add a link to this appointment in the next step.'); ?>
			</label>
			<?php echo '<input type="submit" value="'.loc('add link').'"/><br/>'.PHP_EOL; ?>
		</div>
	</form>
</div>
