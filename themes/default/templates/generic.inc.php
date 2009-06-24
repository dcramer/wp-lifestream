<div class="lifestream_label"><?php echo $label; ?></div>
<?php if ($show_metadata) { ?>
	<?php include('metadata.inc.php'); ?>
<?php } ?>
<?php if (count($event->data) > 1) { ?>
<ul id="<?php echo $id; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<li><?php echo $this->render_item($event, $chunk); ?></li>
	<?php } ?>
</ul>
<?php } else { ?>
	<?php echo $this->render_item($event, $event->data[0]); ?>
<?php } ?>
