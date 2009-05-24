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
<?php } elseif ($visible && $this->has_excerpt($event, $event->data[0])) { ?>
<blockquote class="lifestream_blogpost">
	<strong><a href="<?php echo $event->data[0]['link']; ?>"><?php echo htmlspecialchars($event->data[0]['title']); ?></a></strong><br/>
	<?php echo nl2br(htmlspecialchars(strip_tags($this->get_event_excerpt($event, $event->data[0])))); ?>
</blockquote>
<?php } ?>
