<h2><?php $lifestream->_e('Lifestream Events'); ?></h2><?php

if (count($results))
{
	?>
	<form method="post" action="">
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
		<div class="alignleft">
			<button type="submit" name="op" value="delete" class="button-secondary delete"><?php $lifestream->_e('Delete'); ?></button>
		</div>
		<br class="clear" />
	</div>

	<br class="clear" />
	<table class="widefat">
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" /></th>
				<th scope="col" class="num"><?php $lifestream->_e('ID'); ?></th>
				<th scope="col" class="num"><?php $lifestream->_e('Feed'); ?></th>
				<th scope="col" colspan="2"><?php $lifestream->_e('Event'); ?></th>
				<th scope="col" style="width: 150px;"><?php $lifestream->_e('Date'); ?></th>
				<th scope="col"><?php $lifestream->_e('Owner'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results as $result) { ?>
				<tr valign="top">
					<th scope="row" class="check-column"><input type="checkbox" name="id[]" value="<?php echo $result->id; ?>" /></th>
					<td class="num"><?php echo $result->id; ?></td>
					<td class="num"><a href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->feed->id; ?>"><?php echo $result->feed->id; ?></a></td>
					<td class="icon"><img src="<?php echo $result->feed->get_icon_url(); ?>" alt="icon"/></td>
					<td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->get_event_link()); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo htmlspecialchars($result->get_event_display()); ?></a></strong><br/><small><?php echo htmlspecialchars($result->feed->get_public_name()); ?> &#8211; <?php echo htmlspecialchars($result->get_event_link()); ?></small></td>
					<td><?php echo date($date_format, $result->date); ?></td>
					<td><?php echo $result->owner; ?></td>
				</tr>
			<?php } ?>
		</tbody>
	</table>
	<div class="tablenav">
		<?php
		if ( $page_links )
			echo "<div class='tablenav-pages'>$page_links</div>";
		?>
		<div class="alignleft">
			<button type="submit" name="op" value="delete" class="button-secondary delete"><?php $lifestream->_e('Delete'); ?></button>
		</div>
		<br class="clear" />
	</div>
	</form>
<?php } else { ?>
	<p><?php $lifestream->_e('There are no events to show.'); ?></p>
<?php } ?>