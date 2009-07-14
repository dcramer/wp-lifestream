<?php if (count($event->data) > 1) { ?>
<ul id="<?php echo $this->get_id($event, $options['id']); ?>" class="lifestream_events"<?php if (!$visible) echo ' style="display:none;"'; ?>>
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
