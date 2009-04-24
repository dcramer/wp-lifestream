<?php foreach ($event->data as $chunk) { ?>
<div class="lifestream_label"><?php if ($lifestream->get_option('show_owners')) { echo $label_inst->get_user_label().': '; } ?><?php echo $this->render_item($event, $chunk); ?></div>
<?php if ($show_metadata) { ?>
	<div class="lifestream_meta">&mdash; <abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo date($hour_format, $event->timestamp); ?></abbr> <span class="lifestream_via">via <?php echo $feed_label ?></span></div>
<?php } ?>
<?php } ?>
