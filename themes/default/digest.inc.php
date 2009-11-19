<table class="lifestream">
<?php
foreach ($events as $event)
{
	?>
	<tr class="lifestream_feedid_<?php echo $event->feed->id; ?> lifestream_feed_<?php echo $event->feed->get_constant('ID'); ?>">
		   <td class="lifestream_icon">
			   <a href="<?php echo htmlspecialchars($event->get_url()); ?>"><img src="<?php echo $event->feed->get_icon_url(); ?>" alt="<?php echo $event->feed->get_constant('ID'); ?> (feed #<?php echo $event->feed->id; ?>)" /></a>
		   </td>
		   <td class="lifestream_text">
				<div class="lifestream_label"><?php echo $event->get_label($options); ?></div>
				<?php if ($show_metadata) { ?>
					<div class="lifestream_meta">&#8212; <abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php echo date($lifestream->get_option('hour_format'), $event->timestamp); ?></abbr> <span class="lifestream_via">via <?php echo $event->get_feed_label($options) ?></span></div>
				<?php } ?>
				<?php echo $event->render($options); ?>
		   </td>
	</tr>
	<?php
}
?>
</table>