<?php
$authors = get_users_of_blog();
$form_name = 'feed_options_'.htmlspecialchars($feed->get_constant('ID'));
?>
<div id="poststuff" class="metabox-holder">
	<h2><?php $lifestream->_e('Add a Feed');?> <small><?php $lifestream->_e('(<a href="%s">Back to Lifestream Feeds</a>)', '?page='.$_GET['page']); ?></small></h2>

	<form action="?page=lifestream.php&amp;op=add&amp;feed=<?php echo urlencode($identifier); ?>" method="post" name="<?php echo $form_name; ?>">
		<div id="feeddiv" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('%s Feed Settings', $feed->get_constant('NAME')) ;?></span></h3>

			<input type="hidden" name="feed_type" value="<?php echo htmlspecialchars($identifier); ?>"/>
			<div class="inside">
				<?php if ($description = $feed->get_constant('DESCRIPTION')) { ?>
					<p><?php echo nl2br($description); ?></p>
				<?php } ?>
				
				<table class="form-table">
					<tbody>
						<?php foreach ($options as $option=>$option_meta) { ?>
							<?php if ($option_meta[1] === null) continue; ?>
							<?php $current_value = (isset($_POST[$option]) ? stripslashes($_POST[$option]) : $option_meta[2]); ?>
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
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Add Feed');?>" />
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
								<?php $current_value = (isset($_POST['feed_label']) ? stripslashes($_POST['feed_label']) : ''); ?>
								<input type="text" id="id_feed_label" name="feed_label" value="<?php echo htmlspecialchars($current_value); ?>"/>
								<div class="helptext"><?php $lifestream->_e('A label to use for this feed instead of the default: <strong>%s</strong>.', $feed->get_constant('NAME')); ?></div>
							</td>
						</tr>
						<tr>
							<th><?php $lifestream->_e('Icon:'); ?></th>
							<td>
								<?php $current_value = (isset($_POST['icon_type']) ? $_POST['icon_type'] : 1); ?>
								<ul>
									<li style="background: url('<?php echo $feed->get_icon_url(); ?>') left center no-repeat;padding-left:20px;"><label><input type="radio" name="icon_type" value="1"<?php if ($current_value == 1) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use Lifestream\'s included icon.'); ?></label></li>
									<li style="background: url('<?php echo $feed->get_constant('URL'); ?>favicon.ico') left center no-repeat;padding-left:20px;"><label><input type="radio" name="icon_type" value="2"<?php if ($current_value == 2) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use extension website\'s favicon.'); ?></label></li>
									<li style="padding-left:20px;">
										<label><input type="radio" name="icon_type" value="3"<?php if ($current_value == 3) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use your own custom icon.'); ?></label><br/>
										<label style="margin: 5px 0 0 50px;">URL: <input type="text" name="icon_url" id="id_icon_url" style="width:300px;" value="<?php echo htmlspecialchars((isset($_POST['icon_url']) ? $_POST['icon_url'] : 'http://')); ?>"/></label>
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
						<?php if ($feed->get_constant('CAN_GROUP') && !$feed->get_constant('MUST_GROUP')) { ?>
							<tr>
								<th>&nbsp;</th>
								<td>
									<label><input type="checkbox" name="grouped" id="id_grouped" value="1"<?php if (isset($_POST['grouped']) && $_POST['grouped'] == '1') echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Group events from the same day together.'); ?></label>
								</td>
							</tr>
						<?php } ?>
						<?php if ($feed->get_constant('HAS_EXCERPTS')) { ?>
							<?php $current = (int)(isset($_POST['excerpt']) ? $_POST['excerpt'] : 1); ?>
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
										echo '<option value="'.$author->ID.'"'.($userdata->ID == $author->ID ? ' selected="selected"' : '').'>'.$author->display_name.'</option>';
									}
									?>
								</select>
								<?php } else { ?>
								<?php echo $userdata->display_name; ?>
								<?php } ?>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Add Feed');?>" />
				</p>
			</div>
		</div>
		<?php if ($url = $feed->get_constant('URL')) { ?>
		<p><?php $lifestream->_e('Find more information about %s by visiting <a href="%s">%s</a>.', htmlspecialchars($feed->get_constant('NAME')), htmlspecialchars($url), htmlspecialchars($url)); ?></p>
		<?php } ?>
	</form>
</div>
