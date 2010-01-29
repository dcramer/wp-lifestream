<h2><?php $lifestream->_e('Lifestream Errors'); ?> <small>(<a href="?page=lifestream-errors.php&amp;op=clear"><?php $lifestream->_e('Clear Log'); ?></a>)</small></h2>
<?php
if (count($results))
{
	?>
	<p><?php $lifestream->_e('The errors below may have been a one-time problem with a feed. If problems persist we suggest to try readding the feed and/or submitting a bug report.'); ?></p>
	
	<div class="tablenav">
		<?php
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' ),
			'format' => '',
			'total' => $number_of_pages,
			'current' => $page,
		));

		if ( $page_links )
			echo "<div class='tablenav-pages'>$page_links</div>";
		?>
		<br class="clear" />
	</div>

	<br class="clear" />
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" class="num"><?php $lifestream->_e('Feed'); ?></th>
				<th scope="col" colspan="2"><?php $lifestream->_e('Message'); ?></th>
				<th scope="col" style="width: 150px;"><?php $lifestream->_e('Date'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results as $result) { ?>
				<tr valign="top">
					<td class="num">
						<?php
						if ($result->feed_id)
						{
							?><a href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->feed_id; ?>"><?php echo $result->feed_id; ?></a><?php
						}
						else
						{
							echo '&#8211;';
						}
						?>
					</td>
						<?php
						if ($result->feed_id)
						{
							// TODO: add custom feed icon support here.
							?><td class="icon">
							<img src="<?php echo $lifestream->get_icon_media_url($result->feed.'.png'); ?>"/></td>
							<td><?php
						}
						else
						{
							?><td colspan="2"><?php
						}
						if ($result->has_viewed)
						{
							?><strong><?php echo Lifestream_Feed::parse_urls(htmlspecialchars($result->message)); ?></strong><?php
						}
						else
						{
							echo Lifestream_Feed::parse_urls(htmlspecialchars($result->message));
						}
						?>
					</td>
					<td><?php echo date($date_format, $result->timestamp); ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	<div class="tablenav">
		<?php
		if ( $page_links )
			echo "<div class='tablenav-pages'>$page_links</div>";
		?>
		<br class="clear" />
	</div>
<?php } else { ?>
	<p><?php $lifestream->_e('There are no errors to show.'); ?></p>
<?php } ?>