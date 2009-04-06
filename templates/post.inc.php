<?php if ($this->options['show_label'] && !$options['hide_label']) { ?>
	<div class="lifestream_label"><?php echo $label; ?> <small class="lifestream_meta"><abbr title="<?php echo date("c", $timestamp); ?>" class="lifestream_hour"><?php echo date($hour_format, $event->timestamp); ?></abbr> | <span class="lifestream_via">via <?php echo $feed_label ?></span></small></div>
<?php } ?>
<?php if (count($event->data) > 1) { ?>
<ul id="<?php echo $id; ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
	<?php foreach ($event->data as $chunk) { ?>
		<li><?php echo $this->render_item($event, $chunk); ?></li>
	<?php } ?>
</ul>
<?php } elseif ($visible && $event->data[0]['description']) { ?>
<blockquote class="lifestream_blogpost">
	<?php echo htmlspecialchars(strip_tags($lifestream->truncate($event->data[0]['description']))); ?>
</blockquote>
<?php } ?>
