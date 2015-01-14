<?php
	if ($format=='html') {
		include 'forms.php';
?>
<div class="addlink">
	<h2>
		<?php echo loc('add link');?>
	</h2>
	<form class="addlink" method="POST" action="?show=<?php echo $appointment->id; ?>">
		<input type="hidden" name="newlink[aid]" value="<?php echo $appointment->id; ?>" />	
		<div id="description">
			<?php echo loc('description'); ?>
			<input type="text" name="newlink[description]" />
		</div>
		<div id="url">
			<?php echo loc('url'); ?>
			<input type="text" name="newlink[url]" />
		</div>
		<div class="submit">
			<div class="choice">
		  	<input type="radio" id="addsession" name="nextaction" value="addsession" />
		  	<label for="addsession">
					<?php echo loc('Add a session to this appointment in the next step.'); ?>
				</label>
			</div>
			<div class="choice">
				<input type="radio" id="addlink" name="nextaction" value="addlink" />
				<label for="addlink">			
					<?php echo loc('Add a link to this appointment in the next step.'); ?>
				</label>
			</div>
			<div class="choice">
				<input type="radio" id="addattachment" name="nextaction" value="addattachment"" />
				<label for="addattachment">			
					<?php echo loc('Add an attachment to this appointment in the next step.'); ?>
				</label>
			</div>
			
			<div class="choice">
				<input type="checkbox" id="gricalpost" name="gricalpost" <?php echo gricalValue(); ?>/>
				<label for="gricalpost">			
					<?php echo loc('Send this appointment to grical, too.').'*'; ?>
				</label>
			</div>
			<div class="choice">
				<input type="checkbox" id="calciferpost" name="calciferpost"  <?php echo calciferValue(); ?>/>
				<label for="calciferpost">			
					<?php echo loc('Send this appointment to calcifer, too.').'*'; ?>
				</label>
			</div>
			
			
			<input type="submit" value="<?php echo loc('add link'); ?>"/><br/> 
		</div>
	</form>
</div>
<?php }?>