<div class="lifestream_label"><?php echo $label; ?> <small class="lifestream_meta"><abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo date($hour_format, $event->timestamp); ?></abbr> | <span class="lifestream_via">via <?php echo $feed_label ?></span></small> </div>
<?php if (count($event->data) > 1) { ?>
<ul id="<?php echo $id; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<li><?php echo $this->render_item($event, $chunk); ?></li>
	<?php } ?>
</ul>
<?php } else { ?>
	<?php echo $this->render_item($event, $event->data[0]); ?>
<?php } ?>
