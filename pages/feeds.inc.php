<h2><?php $lifestream->_e('Lifestream Feeds'); ?> <small>(<a href="?page=lifestream.php&amp;op=refreshall"><?php $lifestream->_e('Refresh All Feeds'); ?></a>)</small></h2>
<?php

if (count($results))
{
	?>
	<form method="post" action="">
	<div class="tablenav">
		<?php
		$page_links = paginate_links( array(
			'base' => add_query_arg( 'paged', '%#%' , $_SERVER['SCRIPT_NAME'] . '?page=lifestream.php' ),
			'format' => '',
			'total' => $number_of_pages,
			'current' => $page,
		));

		if ( $page_links )
			echo "<div class='tablenav-pages'>$page_links</div>";
		?>
		<div class="alignleft">
			<button type="submit" name="op" value="refresh" class="button-primary refresh"><?php $lifestream->_e('Refresh'); ?></button>
			<button type="submit" name="op" value="pause" class="button-secondary"><?php $lifestream->_e('Pause'); ?></button>
			<button type="submit" name="op" value="unpause" class="button-secondary"><?php $lifestream->_e('Unpause'); ?></button>
			<button type="submit" name="op" value="delete" class="button-secondary delete"><?php $lifestream->_e('Delete'); ?></button>
		</div>
		<br class="clear" />
	</div>

	<br class="clear" />
	<table class="widefat">
		<colgroup>
			<col style="width:20px;"/>
			<col style="width:40px;"/>
			<col style="width:16px;"/>
			<col/>
			<col style="width:150px"/>
			<col style="width:50px;"/>
			<col style="width:90px;"/>
		</colgroup>
		<thead>
			<tr>
				<th scope="col" class="check-column"><input type="checkbox" /></th>
				<th scope="col" class="num"><?php $lifestream->_e('ID'); ?></th>
				<th scope="col" colspan="2"><?php $lifestream->_e('Description'); ?></th>
				<th scope="col" class="date"><?php $lifestream->_e('Last Update'); ?></th>
				<th scope="col" class="num"><?php $lifestream->_e('Events'); ?></th>
				<th scope="col"><?php $lifestream->_e('Owner'); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($results as $result) { ?>
				<tr valign="top">
					<th scope="row" class="check-column"><input type="checkbox" name="id[]" value="<?php echo $result->id; ?>" /></th>
					<td class="num"><?php echo $result->id; ?></td>
					<td class="icon"><img src="<?php echo $result->get_icon_url(); ?>" alt="icon"/></td>
					<td><strong><a class="row-title" href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->id; ?>"><?php echo $result->get_public_name(); ?></a></strong><?php if (!$result->active) echo ' (Paused)'; ?><br /><small><?php echo htmlspecialchars($lifestream->truncate($result->get_feed_display(), 100)); ?></small><?php
					if (isset($feedmsgs[$result->id]) && !empty($feedmsgs[$result->id]))
					{
						$msg = $feedmsgs[$result->id];
						if (is_int($msg)) echo '<div class="success">'.$msg.' new event(s).</div>';
					}
					?></td>
					<td class="date"><?php echo date('F j, Y', $result->date).'<br/>'.date('g:ia', $result->date); ?></td>
					<td class="num"><?php echo $result->events; ?></td>
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
			<button type="submit" name="op" value="refresh" class="button-primary refresh"><?php $lifestream->_e('Refresh'); ?></button>
			<button type="submit" name="op" value="pause" class="button-secondary"><?php $lifestream->_e('Pause'); ?></button>
			<button type="submit" name="op" value="unpause" class="button-secondary"><?php $lifestream->_e('Unpause'); ?></button>
			<button type="submit" name="op" value="delete" class="button-secondary delete"><?php $lifestream->_e('Delete'); ?></button>
		</div>
		<br class="clear" />
	</div>
	</form>
<?php } else { ?>
	<p><?php $lifestream->_e('You do not currently have ownership of any feeds.'); ?></p>
<?php } ?>
<br />

<h2><?php $lifestream->_e('Add a Feed');?></h2>
<p><?php $lifestream->_e('Add a new feed by first selecting the type of feed:'); ?></p>
<ul class="feedlist">
	<?php
	foreach ($lifestream->feeds as $identifier=>$class_name)
	{
		$result = new $class_name($lifestream);
		?><li><a href="?page=lifestream.php&amp;op=add&amp;feed=<?php echo urlencode($identifier); ?>" style="background-image: url('<?php echo $result->get_icon_url(); ?>');" title="<?php echo htmlspecialchars($result->get_constant('NAME')); ?>"><?php echo htmlspecialchars($result->get_constant('NAME')); ?></a></li><?php
	}
	?>
</ul>
<br style="clear:both;"/><br/>
<h2><?php $lifestream->_e('Add a Generic Feed');?></h2>
<p><?php $lifestream->_e('If you\'re not finding the extension that you\'re looking for, you may add a generic RSS or Atom feed instead.');?></p>
<form action="?page=lifestream.php&amp;op=add&amp;feed=generic" method="post">
	<input type="hidden" name="feed_type" value="generic"/>
	<div class="inside">
		<label class="required" for="id_url">Feed URL:</label>
		<input name="url" type="text" size="80" value=""> <input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Add Feed');?>" />
	</div>
</form>
<br style="clear:both;"/><br/>
