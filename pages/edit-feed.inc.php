<?php
$authors = get_users_of_blog();
$form_name = 'feed_options_'.htmlspecialchars($instance->get_constant('ID'));

?>
<div id="poststuff" class="metabox-holder">
	<h2><?php $lifestream->_e('%s Feed Settings', $instance->get_constant('NAME')); ?> <small><?php $lifestream->_e('(<a href="%s">Back to Lifestream Feeds</a>)', '?page=lifestream.php'); ?></small></h2>

	<?php if ($instance) { ?>
		<form action="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $instance->id; ?>" method="post">
			<div id="feeddiv" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('%s Feed Settings', $instance->get_constant('NAME')) ;?></span></h3>
					
				<div class="inside">
					<?php if ($description = $instance->get_constant('DESCRIPTION')) { ?>
						<p><?php echo nl2br($description); ?></p>
					<?php } ?>
					<table class="form-table">
						<tbody>
							<?php foreach ($options as $option=>$option_meta) { ?>
								<?php if ($option_meta[1] === null) continue; ?>
								<?php $current_value = (isset($_POST[$option]) ? stripslashes($_POST[$option]) : $instance->options[$option]); ?>
								<tr>
									<?php if (is_array($option_meta[3])) { ?>
										<th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars($option_meta[0]);?></label></th>
										<td><select name="<?php echo $option;?>">
										<?php foreach ($option_meta[3] as $choice=>$label) { ?>
											<option value="<?php echo $choice;?>"<?php if ($current_value == $choice) echo ' selected="selected"'; ?>><?php echo htmlspecialchars($label);?></option>
										<?php } ?>
										</select>
										<?php if ($option_meta[4]) { ?>
										<div class="helptext"><?php echo $option_meta[4]; ?></div>
										<?php } ?></td>
									<?php } elseif (is_bool($option_meta[3])) { ?>
										<th>&nbsp;</th>
										<td><label<?php if ($option_meta[1]) echo ' class="required"'; ?>><input type="checkbox" value="1"<?php if ($current_value == 1) echo ' checked="checked"'; ?> name="<?php echo $option;?>" /> <?php echo htmlspecialchars($option_meta[0]);?></label>
										<?php if (@$option_meta[4]) { ?>
											<div class="helptext"><?php echo $option_meta[4]; ?></div>
										<?php } ?></td>
									<?php } else { ?>
										<th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars($option_meta[0]);?></label></th>
										<td><input name="<?php echo $option;?>" type="<?php echo (lifestream_str_endswith($option, 'password') ? 'password': 'text'); ?>" size="40" value="<?php echo htmlspecialchars($current_value); ?>">
										<?php if (!empty($option_meta[4])) { ?>
											<div class="helptext"><?php echo $option_meta[4]; ?></div>
										<?php } ?></td>
									<?php } ?>
								</tr>
							<?php } ?>
						</tbody>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" name="save" value="<?php $lifestream->_e('Save Feed');?>" />
					</p>
				</div>
			</div>
				
			<div id="optdiv" class="postbox">
				<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('Additional Settings') ;?></span></h3>

				<div class="inside">
					<table class="form-table">
						<tbody>
							<tr>
								<th><label for="id_feed_label"><?php $lifestream->_e('Feed Label:'); ?></label></th>
								<td>
									<?php $current_value = (isset($_POST['feed_label']) ? stripslashes($_POST['feed_label']) : $instance->options['feed_label']); ?>
									<input type="text" id="id_feed_label" name="feed_label" value="<?php echo htmlspecialchars($current_value); ?>"/>
									<div class="helptext"><?php $lifestream->_e('A label to use for this feed instead of the default.'); ?></div>
								</td>
							</tr>
							<tr>
								<th><?php $lifestream->_e('Icon:'); ?></th>
								<td>
									<?php $current_value = (isset($_POST['icon_type']) ? $_POST['icon_type'] : ($instance->get_option('auto_icon') ? 2 : ($instance->get_option('icon_url') ? 3 : 1))); ?>
									<ul>
										<li style="background: url('<?php echo $instance->get_icon_url(); ?>') left center no-repeat;padding-left:20px;"><label><input type="radio" name="icon_type" value="1"<?php if ($current_value == 1) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use Lifestream\'s included icon.'); ?></label></li>
										<li style="background: url('<?php echo $instance->get_constant('URL'); ?>favicon.ico') left center no-repeat;padding-left:20px;"><label><input type="radio" name="icon_type" value="2"<?php if ($current_value == 2) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use extension website\'s favicon.'); ?></label></li>
										<li style="padding-left:20px;">
											<label><input type="radio" name="icon_type" value="3"<?php if ($current_value == 3) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use your own custom icon.'); ?></label><br/>
											<label style="margin: 5px 0 0 50px;">URL: <input type="text" name="icon_url" id="id_icon_url" style="width:300px;" value="<?php echo htmlspecialchars((isset($_POST['icon_url']) ? $_POST['icon_url'] : $instance->get_option('icon_type', 'http://'))); ?>"/></label>
										</li>
									</ul>
								</td>
							</tr>
							<script type="text/javascript">
							function checkAutoIcon() {
								var form = document.forms['<?php echo $form_name; ?>'];
								user_input = null;
								for (i=0, el=null; (el=form.icon_type[i]); i++) {
									if (el.checked) {
										user_input = el.value;
									}
								}
								form.icon_url.disabled = (user_input != 3 ? true : false);
							}
							checkAutoIcon();
							var form = document.forms['<?php echo $form_name; ?>'];
							for (i=0, el=null; (el=form.icon_type[i]); i++) {
								el.onchange = checkAutoIcon;
							}
							</script>
							<?php if ($instance->get_constant('CAN_GROUP') && !$instance->get_constant('MUST_GROUP')) { ?>
								<tr>
									<th>&nbsp;</th>
									<td>
										<label><input type="checkbox" name="grouped" value="1"<?php if (isset($_POST['grouped']) ? $_POST['grouped'] : $instance->options['grouped']) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Group events from the same day together.'); ?></label>
										<div class="helptext"><?php $lifestream->_e('This will not affect any event\'s already listed.'); ?></div>
									</td>
								</tr>
							<?php } ?>
							<?php if ($instance->get_constant('HAS_EXCERPTS')) { ?>
								<?php $current = (int)(isset($_POST['excerpt']) ? $_POST['excerpt'] : $instance->options['excerpt']); ?>
								<tr>
									<th><label for="id_excerpt"><?php $lifestream->_e('Excerpts'); ?></label></th>
									<td>
										<select name="excerpt" id="id_excerpt">
											<option value="0"<?php if (!$current) echo 'selected = "selected"'; ?>><?php $lifestream->_e('Do not show any excerpts.'); ?></option>
											<option value="1"<?php if ($current == 1) echo 'selected = "selected"'; ?>><?php $lifestream->_e('Show partial excerpt for events.'); ?></option>
											<option value="2"<?php if ($current == 2) echo 'selected = "selected"'; ?>><?php $lifestream->_e('Show full description for events.'); ?></option>
										</select>
										<div class="helptext"><?php $lifestream->_e('This feed can show a more detailed description of the event.'); ?></div>
									</td>
							<?php } ?>
							<tr>
								<th><label for="id_owner"><?php $lifestream->_e('Owner:'); ?></label></th>
								<td>
									<?php if (current_user_can('manage_options')) { ?>
									<select name="owner" id="id_owner">
										<?php
										foreach ($authors as $author)
										{
											$usero = new WP_User($author->user_id);
											$author = $usero->data;
											echo '<option value="'.$author->ID.'"'.($instance->owner_id == $author->ID ? ' selected="selected"' : '').'>'.$author->display_name.'</option>';
										}
										?>
									</select>
									<?php } else { ?>
									<?php echo $instance->owner; ?>
									<?php } ?>
								</td>
							</tr>
						</tbody>
					</table>
					<p class="submit">
						<input type="submit" class="button-primary" name="save" value="<?php $lifestream->_e('Save Feed');?>" />
					</p>
				</div>
			</div>
			<?php if ($url = $instance->get_constant('URL')) { ?>
				<p><?php $lifestream->_e('Find more information about %s by visiting <a href="%s">%s</a>.', htmlspecialchars($instance->get_constant('NAME')), htmlspecialchars($url), htmlspecialchars($url)); ?></p>
			<?php } ?>
		</form>
		<form action="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $instance->id; ?>" method="post">
			<h2><?php $lifestream->_e('Feed Tasks'); ?></h2>
			<p><?php $lifestream->_e('If you are having problems with this feed, you may reset it by reimporting the feed.'); ?></p>
			<p class="submit">
				<input type="submit" class="button-secondary" name="truncate" value="<?php $lifestream->_e('Wipe Events');?>" />
			</p>
		</form>
		<h2><?php $lifestream->_e('Recent Events'); ?></h2><br />
		<?php $events =& $instance->get_events(50); ?>
		<?php if (count($events)) { ?>
			<table class="widefat">
				<thead>
					<tr>
						<th scope="col" class="num"><?php $lifestream->_e('ID'); ?></th>
						<th scope="col"><?php $lifestream->_e('Event'); ?></th>
						<th scope="col" style="width: 150px;"><?php $lifestream->_e('Date'); ?></th>
						<th scope="col" style="width: 40px;">&nbsp;</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($events as $result) { ?>
						<tr valign="top">
							<td class="num"><?php echo $result->id; ?></td>
							<td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->get_event_link()); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo htmlspecialchars($result->get_event_display()); ?></a></strong><br/><small><?php echo htmlspecialchars($result->feed->get_public_name()); ?> &#8211; <?php echo htmlspecialchars($result->get_event_link()); ?></small>
							<td><?php echo date($date_format, $result->date); ?></td>
							<td><?php echo $result->owner; ?></td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
		<?php } else { ?>
			<p><?php $lifestream->_e('There are no events to show.'); ?></p>
		<?php } ?>	
		<br/>
	<?php } ?>
</div>