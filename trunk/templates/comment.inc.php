<div class="lifestream_label"><?php echo $label; ?></div>
<?php if ($show_metadata) { ?>
	<div class="lifestream_meta">&mdash; <abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo date($hour_format, $event->timestamp); ?></abbr> <span class="lifestream_via">via <?php echo $feed_label ?></span></div>
<?php } ?>
<?php if (count($event->data) > 1) { ?>
<ul id="<?php echo $id; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<li><?php echo $this->render_item($event, $chunk); ?></li>
	<?php } ?>
</ul>
<?php } elseif ($visible && $event->data[0]['description']) { ?>
<blockquote class="lifestream_blogpost">
	<strong><a href="<?php echo $event->data[0]['link']; ?>"><?php echo htmlspecialchars($event->data[0]['title']); ?></a></strong><br/>
	<?php echo strip_tags($lifestream->truncate($event->data[0]['description'], $lifestream->get_option('truncate_length'))); ?>
</blockquote>
<?php } ?>
