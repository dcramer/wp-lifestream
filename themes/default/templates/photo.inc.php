<div class="lifestream_label"><?php echo $label; ?></div>
<?php if ($show_metadata) { ?>
	<?php include('metadata.inc.php'); ?>
<?php } ?>
<div id="<?php echo $id; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<?php echo $this->render_item($event, $chunk); ?>
	<?php } ?>
</div>