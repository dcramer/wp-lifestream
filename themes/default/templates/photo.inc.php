<div id="<?php echo $options['id']; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<?php echo $this->render_item($event, $chunk); ?>
	<?php } ?>
</div>