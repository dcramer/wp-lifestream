<?php

$categories = get_categories('hide_empty=0');
$authors = get_users_of_blog();

?>
<div id="poststuff" class="metabox-holder">
	<h2><?php $lifestream->_e('Lifestream Configuration');?></h2>
	<p><?php $lifestream->_e('The following settings that will affect feeds globally. If you wish to modify per-feed settings, you may do so via the <a href="%s">Feed Management page</a>.', '?page=lifestream.php'); ?></p>
	<form method="post" action="">
		<div id="feeddiv" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('General'); ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<colgroup>
						<col style="width: 150px;"/>
						<col/>
					</colgroup>
					<tbody>
						<tr>
							<th><label for="id_day_format"><?php $lifestream->_e('Day Format:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_day_format" id="id_day_format" value="<?php echo htmlspecialchars($lifestream->get_option('day_format')); ?>"/> (e.g. <?php echo date($lifestream->get_option('day_format')); ?>)
								<div class="helptext"><?php $lifestream->_e('For more information, please see PHP\'s <a href="http://www.php.net/date/">date()</a> method.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_hour_format"><?php $lifestream->_e('Hour Format:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_hour_format" id="id_hour_format" value="<?php echo htmlspecialchars($lifestream->get_option('hour_format')); ?>"/> (e.g. <?php echo date($lifestream->get_option('hour_format')); ?>)
								<div class="helptext"><?php $lifestream->_e('For more information, please see PHP\'s <a href="http://www.php.net/date/">date()</a> method.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_update_interval"><?php $lifestream->_e('Update Interval:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_update_interval" id="id_update_interval" value="<?php echo htmlspecialchars($lifestream->get_option('update_interval')); ?>"/> <?php $lifestream->_e('(Default: %s)', $lifestream->_options['update_interval']); ?>
								<div class="helptext"><?php $lifestream->_e('The number of minutes between updates to your feeds. Value is in minutes.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_number_of_items"><?php $lifestream->_e('Number of Items:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_number_of_items" id="id_number_of_items" value="<?php echo htmlspecialchars($lifestream->get_option('number_of_items')); ?>"/> <?php $lifestream->_e('(Default: %s)', $lifestream->_options['number_of_items']); ?>
								<div class="helptext"><?php $lifestream->_e('The number of items to display in the default lifestream call.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_date_interval"><?php $lifestream->_e('Date Cutoff:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_date_interval" id="id_date_interval" value="<?php echo htmlspecialchars($lifestream->get_option('date_interval')); ?>"/> <?php $lifestream->_e('(Default: %s)', $lifestream->_options['date_interval']); ?>
								<div class="helptext"><?php $lifestream->_e('The cutoff time for the default lifestream feed call. Available unit names are: <code>year</code>, <code>quarter</code>, <code>month</code>, <code>week</code>, <code>day</code>, <code>hour</code>, <code>second</code>, and <code>microsecond</code>'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_truncate_length"><?php $lifestream->_e('Description Cutoff:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_truncate_length" id="id_truncate_length" value="<?php echo htmlspecialchars($lifestream->get_option('truncate_length')); ?>"/> <?php $lifestream->_e('(Default: %s)', $lifestream->_options['truncate_length']); ?>
								<div class="helptext"><?php $lifestream->_e('Some extensions will show a preview of the text (such as blogs and comments). Set this to the length, in characters, for the cutoff, or -1 to disable truncating posts.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><?php $lifestream->_e('Show Owners:'); ?></th>
							<td><label for="id_show_owners"><input type="checkbox" name="lifestream_show_owners" id="id_show_owners" value="1"<?php if ($lifestream->get_option('show_owners')) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Show the owner of the feed in the display.'); ?></label>
								<div class="helptext"><?php $lifestream->_e('e.g. %s posted a new photo on %s', '<a href="#">admin</a>', '<a href="http://www.flickr.com/">Flickr</a>'); ?></div>
							</td>
						</tr>
						<tr>
							<th><?php $lifestream->_e('Enable iBox:'); ?></th>
							<td><label for="id_use_ibox"><input type="checkbox" name="lifestream_use_ibox" id="id_use_ibox" value="1"<?php if ($lifestream->get_option('use_ibox')) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Enable iBox on plugins that support it.'); ?></label>
								<div class="helptext"><?php $lifestream->_e('Requires the %s plugin.', '<a href="http://www.enthropia.com/labs/ibox/">iBox</a>'); ?></div>
							</td>
						</tr>
						<tr>
							<th><?php $lifestream->_e('Hide Grouped Details:'); ?></th>
							<td><label for="id_hide_details_default"><input type="checkbox" name="lifestream_hide_details_default" id="id_hide_details_default" value="1"<?php if ($lifestream->get_option('hide_details_default')) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Hide details of grouped events by default.'); ?></label>
							</td>
						</tr>
						<tr>
							<th><?php $lifestream->_e('Link Targets:'); ?></th>
							<td><label for="id_links_new_windows"><input type="checkbox" name="lifestream_links_new_windows" id="id_links_new_windows" value="1"<?php if ($lifestream->get_option('links_new_windows')) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Open event links in new windows (this will use target="_blank").'); ?></label>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Save Changes');?>" />
				</p>
			</div>
		</div>
		<br />
		<div id="advdiv" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('Advanced'); ?></span></h3>
			<div class="inside">
				<p><?php $lifestream->_e('You can specify additional locations outside of the plugin directory for extensions. These MUST be located within <code>%s</code> and should be the full path.', WP_CONTENT_DIR)?>
				<table class="form-table">
					<colgroup>
						<col style="width: 150px;"/>
						<col/>
					</colgroup>
					<tbody>
						<tr>
							<th><label for="id_theme_dir"><?php $lifestream->_e('Theme Directory:'); ?></label></th>
							<td>
								<input type="text" class="text" size="70" name="lifestream_theme_dir" id="id_theme_dir" value="<?php echo htmlspecialchars($lifestream->get_option('theme_dir')); ?>"/>
								<div class="helptext"><?php $lifestream->_e('An additional directory where you may place themes.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_extension_dir"><?php $lifestream->_e('Extension Directory:'); ?></label></th>
							<td>
								<input type="text" class="text" size="70" name="lifestream_extension_dir" id="id_extension_dir" value="<?php echo htmlspecialchars($lifestream->get_option('extension_dir')); ?>"/>
								<div class="helptext"><?php $lifestream->_e('An additional directory where you may place extensions.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_icon_dir"><?php $lifestream->_e('Icon Directory:'); ?></label></th>
							<td>
								<input type="text" class="text" size="70" name="lifestream_icon_dir" id="id_icon_dir" value="<?php echo htmlspecialchars($lifestream->get_option('icon_dir')); ?>"/>
								<div class="helptext"><?php $lifestream->_e('An additional directory where you may place icon packs.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_url_handler"><?php $lifestream->_e('URL Handler:'); ?></label></th>
							<td><select name="lifestream_url_handler" id="id_url_handler">
								<option value="auto"<?php if ($lifestream->get_option('url_handler') == 'auto') echo ' selected="selected"'; ?>><?php $lifestream->_e('(Automatic)'); ?></option>
								<option value="curl"<?php if ($lifestream->get_option('url_handler') == 'curl') echo ' selected="selected"'; ?>><?php $lifestream->_e('Curl'); ?></option>
								<option value="fopen"<?php if ($lifestream->get_option('url_handler') == 'fopen') echo ' selected="selected"'; ?>><?php $lifestream->_e('fopen'); ?></option>
								</select>
								<div class="helptext"><?php $lifestream->_e('You may manually specify the method which Lifestream requests files form the internet.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_truncate_interval"><?php $lifestream->_e('Event History:'); ?></label></th>
							<td><select name="lifestream_truncate_interval" id="id_truncate_interval">
								<option value="0"<?php if ($lifestream->get_option('truncate_interval') == '0') echo ' selected="selected"'; ?>><?php $lifestream->_e('(Keep Full History)'); ?></option>
								<?php foreach (array(30, 60, 90, 180, 365) as $amnt) { ?>
									<option value="<?php echo $amnt; ?>"<?php if ($lifestream->get_option('truncate_interval') == (string)$amnt) echo ' selected="selected"'; ?>><?php $lifestream->_e('%s Days', $amnt); ?></option>
								<?php } ?>
								</select>
								<div class="helptext"><?php $lifestream->_e('You may truncate your event history at a certain point if you\'re concerned about database usage.'); ?></div>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Save Changes');?>" />
				</p>
			</div>
		</div>
		<br />
		<div id="feeddiv" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('Appearance'); ?></span></h3>
			<div class="inside">
				<table class="form-table">
					<colgroup>
						<col style="width:150px;"/>
						<col>
					</colgroup>
					<tbody>
						<tr>
							<th><label for="id_theme"><?php $lifestream->_e('Theme:'); ?></label></th>
							<td>
								<select name="lifestream_theme" id="id_theme">
									<?php foreach ($lifestream->themes as $key=>$theme) {?>
										<option value="<?php echo htmlspecialchars($key); ?>"<?php if ($lifestream->get_option('theme') == $key) echo ' selected="selected"'; ?>><?php echo htmlspecialchars($theme['name']); ?></option>
									<?php } ?>
								</select>
								<div class="helptext"><?php $lifestream->_e('Please see the included themes/README for information on creating your own theme.'); ?>
							</td>
						</tr>
						<tr>
							<th><label for="id_icons"><?php $lifestream->_e('Icons:'); ?></label></th>
							<td>
								<table>
									<?php foreach ($lifestream->icons as $key=>$data) {?>
										<tr>
											<td class="icon"><input type="radio" id="id_lifestream_icons_<?php echo $key; ?>" name="lifestream_icons" value="<?php echo $key; ?>"<?php if ($lifestream->get_option('icons', 'default') == $key) echo ' checked="checked"'; ?>/></td>
											<td class="icon"><label for="id_lifestream_icons_<?php echo $key; ?>"><img src="<?php echo $lifestream->get_media_url_for_icon('generic.png', $key); ?>" alt="icon"/></label></td>
											<td><label for="id_lifestream_icons_<?php echo $key; ?>"><?php echo htmlspecialchars($data['name']); ?>
												<?php if (!empty($data['author'])) { ?>
													<?php if (!empty($data['url'])) { ?>
													 by <a href="<?php echo htmlspecialchars($data['url']); ?>"><?php echo htmlspecialchars($data['author']); ?></a>
													<?php } else { ?>
													 by <em><?php echo htmlspecialchars($data['author']); ?></em>
													<?php } ?>
												<?php } ?></label>
											</td>
										</tr>
									<?php } ?>
								</table>
								<div class="helptext"><?php $lifestream->_e('Please see the included icons/README for information on creating your own icon set.'); ?>
							</td>
						</tr>
						<tr>
							<th><?php $lifestream->_e('Show Credits:'); ?></th>
							<td><label for="id_show_credits"><input type="checkbox" name="lifestream_show_credits" id="id_show_credits" value="1"<?php if ($lifestream->get_option('show_credits')) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Give credit to Lifestream when it\'s embedded.'); ?></label>
								<div class="helptext"><?php $lifestream->_e('e.g.'); ?> <?php echo $lifestream->credits(); ?></div>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Save Changes');?>" />
				</p>
			</div>
		</div>
		<br />
		<div id="feeddiv" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('Feed'); ?></span></h3>
			<div class="inside">
				<?php
				$url = $lifestream->get_rss_feed_url();
				?>
				<p><?php $lifestream->_e('You can access your feed URL at <a href="%s">%s</a>.', $url, $url); ?></p>
				<table class="form-table">
					<colgroup>
						<col style="width:150px;"/>
						<col/>
					</colgroup>
					<tbody>
						<tr>
							<th><label for="id_feed_items"><?php $lifestream->_e('Number of Items:'); ?></label></th>
							<td>
								<input type="text" class="text" name="lifestream_feed_items" id="id_feed_items" value="<?php echo htmlspecialchars($lifestream->get_option('feed_items')); ?>"/> <?php $lifestream->_e('(Default: %s)', $lifestream->_options['feed_items']); ?>
								<div class="helptext"><?php $lifestream->_e('The number of items to display in the default lifestream feed call.'); ?></div>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Save Changes');?>" />
				</p>
			</div>
		</div>
		<br />
		<div id="digestdiv" class="postbox">
			<div class="handlediv" title="Click to toggle"><br /></div><h3 class='hndle'><span><?php $lifestream->_e('Digest'); ?></span></h3>
			<div class="inside">
				<p><?php $lifestream->_e('Lifestream gives you the ability to create a new blog post at regular intervals, containing all of the events which happened in that time period.'); ?></p>
				<table class="form-table">
					<colgroup>
						<col style="width: 150px;"/>
						<col/>
					</colgroup>
					<tbody>
						<tr>
							<th><?php $lifestream->_e('Show Digest:'); ?></th>
							<td><label for="id_daily_digest"><input type="checkbox" name="lifestream_daily_digest" id="id_daily_digest" value="1"<?php if ($lifestream->get_option('daily_digest')) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Post a summary of my lifestream.'); ?></label>
							</td>
						</tr>
						<tr>
							<th><label for="id_digest_interval"><?php $lifestream->_e('Post Interval:'); ?></label></th>
							<td>
								<select name="lifestream_digest_interval" id="id_digest_interval" onchange="handleDigestTimeField();">
									<?php foreach ($lifestream_digest_intervals as $interval=>$label) {?>
										<option value="<?php echo $interval; ?>"<?php if ($lifestream->get_option('digest_interval') == $interval) echo ' selected="selected"'; ?>><?php echo htmlspecialchars($label); ?></option>
									<?php } ?>
								</select><span id="id_digest_time_wrap"> @ <select name="lifestream_digest_time" id="id_digest_time">
									<?php for ($i=0; $i<=24; $i++) {?>
										<option value="<?php echo $i; ?>"<?php if ($lifestream->get_option('digest_time') == $i) echo ' selected="selected"'; ?>><?php echo ($i > 12 ? ($i-12) : ($i == 0 ? 12 : $i)); ?>:00 <?php echo ($i >= 12 ? 'pm' : 'am'); ?></option>
									<?php } ?>
								</select></span>
								<script type="text/javascript">
								function handleDigestTimeField() {
									var el = document.getElementById('id_digest_interval');
									if (el.options[el.selectedIndex].value == 'hourly') {
										var display = 'none';
									} else {
										var display = '';
									}
									document.getElementById('id_digest_time_wrap').style.display = display;
								}
								handleDigestTimeField();
								</script>
								<div class="helptext"><?php $lifestream->_e('This determines the approximate time when your digest should be posted.'); ?>
							</td>
						</tr>
						<tr>
							<th><label for="id_digest_title"><?php $lifestream->_e('Summary Post Title:'); ?></label></th>
							<td>
								<input type="text" name="lifestream_digest_title" size="40" value="<?php echo htmlspecialchars($lifestream->get_option('digest_title')); ?>"/>
								<div class="helptext"><?php $lifestream->_e('You may use <code>%%1$s</code> for the current date, and <code>%%2$s</code> for the current time.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_digest_body"><?php $lifestream->_e('Summary Post Body:'); ?></label></th>
							<td>
								<textarea name="lifestream_digest_body" id="id_digest_body" rows="15" cols="60"><?php echo htmlspecialchars($lifestream->get_option('digest_body')); ?></textarea>
								<div class="helptext"><?php $lifestream->_e('You may use <code>%%1$s</code> for the list of events, <code>%%2$s</code> for the day, and <code>%%3$d</code> for the number of events.'); ?></div>
							</td>
						</tr>
						<tr>
							<th><label for="id_digest_author"><?php $lifestream->_e('Summary Author:'); ?></label></th>
							<td>
								<select name="lifestream_digest_author" id="id_digest_author">
								<?php
								$current_author = $lifestream->get_option('digest_author');
								foreach ($authors as $author)
								{
									$usero = new WP_User($author->user_id);
									$author = $usero->data;
									// Only list users who are allowed to publish
									if (!$usero->has_cap('publish_posts')) continue;
									echo '<option value="'.$author->ID.'"'.($author->ID == $current_author ? ' selected="selected"' : '').'>'.$author->display_name.'</option>';
								}
								?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="id_digest_category"><?php $lifestream->_e('Summary Category:'); ?></label></th>
							<td>
								<select name="lifestream_digest_category" id="id_digest_category">
								<?php
								$current_category = $lifestream->get_option('digest_category');
								foreach ($categories as $category)
								{
									echo '<option value="'.$category->term_id.'"'.($category->term_id == $current_category ? ' selected="selected"' : '').'>'.$category->name.'</option>';
								}
								?>
								</select>
							</td>
						</tr>
					</tbody>
				</table>
				<p class="submit">
					<input type="submit" name="save" class="button-primary" value="<?php $lifestream->_e('Save Changes');?>" />
				</p>
			</div>
		</div>
	</form>
</div>