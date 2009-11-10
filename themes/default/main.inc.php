<?php
$day = '';
if (count($events))
{
	$today = date('m d Y');
	$yesterday = date('m d Y', time()-86400);
	if ($has_paging)
	{
		if ($has_prev_page) {
			echo '<p class="lifestream-paging"><a href="' . $lifestream->get_previous_page_url($page) . '">Newer Entries</a></p>';
		}
	}
	?>
	<table class="lifestream">
	<?php
	foreach ($events as $event)
	{
		$timestamp = $event->get_date();
		if ($today == date('m d Y', $timestamp)) $this_day = $lifestream->__('Today');
		else if ($yesterday == date('m d Y', $timestamp)) $this_day = $lifestream->__('Yesterday');
		else $this_day = $lifestream->__(ucfirst(htmlentities(date($lifestream->get_option('day_format'), $timestamp))));
		if ($day != $this_day)
		{
			?>
			<tr>
				<th colspan="2">
					<h2 class="lifestream_date"><?php echo $this_day; ?></h2>
				</th>
			</tr>
			<?php
			$day = $this_day;
		}
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
	<?php
	if ($has_paging)
	{
		if ($has_next_page) {
			echo '<p class="lifestream-paging"><a href="' . $lifestream->get_next_page_url($page) . '">Older Entries</a></p>';
		}
	}
}
else
{
	?>
	<p class="lifestream"><?php $lifestream->_e('There are no events to show at this time.'); ?></p>
	<?php
}
?>