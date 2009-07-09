<?php
$day = '';
if (count($events))
{
	$today = date('m d Y');
	$yesterday = date('m d Y', time()-86400);
	?>
	<ol class="lifestream">
	<?php
	foreach ($events as $event)
	{
		?>
		<li class="lifestream_feedid_<?php echo $event->feed->id; ?> lifestream_feed_<?php echo $event->feed->get_constant('ID'); ?>">
			<span class="lifestream_icon">
				<a href="<?php echo htmlspecialchars($event->get_url()); ?>"><img src="<?php echo $event->feed->get_icon_url(); ?>" alt="<?php echo $event->feed->get_constant('ID'); ?> (feed #<?php echo $event->feed->id; ?>)" /></a>
			</span>
			<span class="lifestream_label"><?php echo $event->get_label($options); ?></span>
			<?php if ($show_metadata) { ?>
				<span class="lifestream_meta"><abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo $lifestream->timesince($event->timestamp); ?></abbr><span class="lifestream_via">via <?php echo $event->get_feed_label($options) ?></span></span>
			<?php } ?>
		</li>
		<?php
	}
	?>
	</ol>
	<div style="clear:left;"></div>
	<?php
}
else
{
	?>
	<p class="lifestream"><?php $lifestream->_e('There are no events to show at this time.'); ?></p>
	<?php
}
?>