<?php
// This is only here to give a good example of a custom feed.
$day = '';
if (count($events))
{
	?>
	<ul class="lifestream">
	<?php
	foreach ($events as $event)
	{
		?>
		<li class="lifestream_feedid_<?php echo $event->feed->id; ?> lifestream_feed_<?php echo $event->feed->get_constant('ID'); ?>" style="background-image: url('<?php echo $event->feed->get_icon_url(); ?>');">
			<div class="lifestream_text">
				<div class="lifestream_label"><?php echo $event->get_label($options); ?></div>
				<?php if ($show_metadata) { ?>
					<div class="lifestream_meta">&#8212; <abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo $lifestream->timesince($event->timestamp); ?></abbr> <span class="lifestream_via">via <?php echo $event->get_feed_label($options) ?></span></div>
				<?php } ?>
				<?php echo $event->render($options); ?>
			</div>
		</li>
		<?php
	} ?>
	</ul>
	<?php
}
else
{
	?>
	<p class="lifestream"><?php $lifestream->_e('There are no events to show at this time.'); ?></p>
	<?php
}
?>