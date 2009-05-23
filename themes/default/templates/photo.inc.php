<div class="lifestream_label"><?php echo $label; ?></div>
<?php if ($show_metadata) { ?>
	<div class="lifestream_meta">&mdash; <abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo date($hour_format, $event->timestamp); ?></abbr> <span class="lifestream_via">via <?php echo $feed_label ?></span></div>
<?php } ?>
<div id="<?php echo $id; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<?php echo $this->render_item($event, $chunk); ?>
	<?php } ?>
</div>