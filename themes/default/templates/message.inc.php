<?php foreach ($event->data as $chunk) { ?>
	<div class="lifestream_label"><?php if ($lifestream->get_option('show_owners')) { echo $label_inst->get_user_label().': '; } ?><?php echo $this->render_item($event, $chunk); ?></div>
	<?php if ($show_metadata) { ?>
		<?php include('metadata.inc.php'); ?>
	<?php } ?>
<?php } ?>
