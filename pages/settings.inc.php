<?php

$categories = get_categories('hide_empty=0');
$authors = get_users_of_blog();

?>
<h2><?php _e('LifeStream Configuration', 'lifestream');?></h2>
<p><?php printf(__('The following settings that will affect feeds globally. If you wish to modify per-feed settings, you may do so via the <a href="%s">Feed Management page</a>.', 'lifestream'), '?page=lifestream.php'); ?></p>
<form method="post" action="">
    <table class="form-table">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tbody>
            <tr>
                <th><label for="id_day_format"><?php _e('Day Format:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_day_format" id="id_day_format" value="<?php echo htmlspecialchars(get_option('lifestream_day_format')); ?>"/> (e.g. <?php echo date(get_option('lifestream_day_format')); ?>)
                    <div class="helptext"><?php _e('For more information, please see PHP\'s <a href="http://www.php.net/date/">date()</a> method.', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_hour_format"><?php _e('Hour Format:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_hour_format" id="id_hour_format" value="<?php echo htmlspecialchars(get_option('lifestream_hour_format')); ?>"/> (e.g. <?php echo date(get_option('lifestream_hour_format')); ?>)
                    <div class="helptext"><?php _e('For more information, please see PHP\'s <a href="http://www.php.net/date/">date()</a> method.', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_timezone"><?php _e('Current Time:', 'lifestream'); ?></label></th>
                <td>
                    <select name="lifestream_timezone" id="id_timezone">
                        <?php for ($i=-12; $i<12; $i++) {?>
                            <option value="<?php echo $i; ?>"<?php if (get_option('lifestream_timezone') == $i) echo ' selected="selected"'; ?>><?php echo date('g:ia', time()+(3600*$i)); ?></option>
                        <?php } ?>
                    </select>
                    <div class="helptext"><?php _e('This will adjust the timezone offset for your LifeStream.', 'lifestream'); ?>
                </td>
            </tr>
            <tr>
                <th><label for="id_update_interval"><?php _e('Update Interval:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_update_interval" id="id_update_interval" value="<?php echo htmlspecialchars(get_option('lifestream_update_interval')); ?>"/> <?php printf(__('(Default: %s)', 'lifestream'), 15); ?>
                    <div class="helptext"><?php _e('The number of minutes between updates to your feeds. Value is in minutes.', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_number_of_items"><?php _e('Number of Items:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_number_of_items" id="id_number_of_items" value="<?php echo htmlspecialchars(get_option('lifestream_number_of_items')); ?>"/> <?php printf(__('(Default: %s)', 'lifestream'), 50); ?>
                    <div class="helptext"><?php _e('The number of items to display in the default lifestream call.', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_date_interval"><?php _e('Date Cutoff:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_date_interval" id="id_date_interval" value="<?php echo htmlspecialchars(get_option('lifestream_date_interval')); ?>"/> <?php printf(__('(Default: %s)', 'lifestream'), '1 month'); ?>
                    <div class="helptext"><?php _e('The cutoff time for the default lifestream feed call. Available unit names are: <code>year</code>, <code>quarter</code>, <code>month</code>, <code>week</code>, <code>day</code>, <code>hour</code>, <code>second</code>, and <code>microsecond</code>', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><?php _e('Show Owners:', 'lifestream'); ?></th>
                <td><label for="id_show_owners"><input type="checkbox" name="lifestream_show_owners" id="id_show_owners" value="1"<?php if (get_option('lifestream_show_owners')) echo ' checked="checked"'; ?>/> <?php _e('Show the owner of the feed in the display.', 'lifestream'); ?></label>
                    <div class="helptext">e.g. <a href="#">admin</a> posted a new photo on <a href="http://www.flickr.com/">Flickr</a></div>
                </td>
            </tr>
            <tr>
                <th><?php _e('Enable iBox:', 'lifestream'); ?></th>
                <td><label for="id_use_ibox"><input type="checkbox" name="lifestream_use_ibox" id="id_use_ibox" value="1"<?php if (get_option('lifestream_use_ibox')) echo ' checked="checked"'; ?>/> <?php _e('Enable iBox on plugins that support it.', 'lifestream'); ?></label>
                    <div class="helptext">Requires the <a href="http://www.ibegin.com/labs/ibox/">iBox</a> plugin.</div>
                </td>
            </tr>
            <tr>
                <th><?php _e('Hide Grouped Details:', 'lifestream'); ?></th>
                <td><label for="id_hide_details_default"><input type="checkbox" name="lifestream_hide_details_default" id="id_hide_details_default" value="1"<?php if (get_option('lifestream_hide_details_default')) echo ' checked="checked"'; ?>/> <?php _e('Hide details of grouped events by default.', 'lifestream'); ?></label>
                </td>
            </tr>
            <tr>
                <th><label for="id_url_handler"><?php _e('URL Handler:', 'lifestream'); ?></label></th>
                <td><select name="lifestream_url_handler" id="id_url_handler">
                    <option value="auto"<?php if (get_option('lifestream_url_handler') == 'auto') echo ' selected="selected"'; ?>><?php _e('(Automatic)', 'lifestream'); ?></option>
                    <option value="curl"<?php if (get_option('lifestream_url_handler') == 'curl') echo ' selected="selected"'; ?>><?php _e('Curl', 'lifestream'); ?></option>
                    <option value="fopen"<?php if (get_option('lifestream_url_handler') == 'fopen') echo ' selected="selected"'; ?>><?php _e('fopen', 'lifestream'); ?></option>
                </td>
            </tr>
            <tr>
                <th><?php _e('Show Credits:', 'lifestream'); ?></th>
                <td><label for="id_show_credits"><input type="checkbox" name="lifestream_show_credits" id="id_show_credits" value="1"<?php if (get_option('lifestream_show_credits')) echo ' checked="checked"'; ?>/> <?php _e('Give credit to LifeStream when it\'s embedded.', 'lifestream'); ?></label>
                    <div class="helptext">e.g. <?php echo lifestream_credits(); ?></div>
                </td>
            </tr>
        </tbody>
    </table>
    <br />
    <h2><?php _e('Feed', 'lifestream'); ?></h2>
    <?php $url = trailingslashit(get_bloginfo('wpurl')) . 'wp-rss2.php?feed=lifestream-feed'; ?>
    <p><?php printf(__('You can access your feed URL at <a href="%s">%s</a>.'), $url, $url); ?></p>
    <table class="form-table">
        <colgroup>
            <col style="width:150px;"/>
            <col/>
        </colgroup>
        <tr>
            <th><label for="id_feed_items"><?php _e('Number of Items:', 'lifestream'); ?></label></th>
            <td>
                <input type="text" class="text" name="lifestream_feed_items" id="id_feed_items" value="<?php echo htmlspecialchars(get_option('lifestream_feed_items')); ?>"/> <?php printf(__('(Default: %s)', 'lifestream'), 10); ?>
                <div class="helptext"><?php _e('The number of items to display in the default lifestream feed call.', 'lifestream'); ?></div></p>
            </td>
        </tr>
    </table>
    <br />
    <h2><?php _e('Digest', 'lifestream'); ?></h2>
    <p><?php _e('LifeStream gives you the ability to create a new blog post at regular intervals, containing all of the events which happened in that time period.', 'lifestream'); ?></p>
    <table class="form-table">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tr>
            <th><?php _e('Show Digest:', 'lifestream'); ?></th>
            <td><label for="id_daily_digest"><input type="checkbox" name="lifestream_daily_digest" id="id_daily_digest" value="1"<?php if (get_option('lifestream_daily_digest')) echo ' checked="checked"'; ?>/> <?php _e('Post a summary of my lifestream.', 'lifestream'); ?></label>
            </td>
        </tr>
        <tr>
            <th><label for="id_digest_interval"><?php _e('Post Interval:', 'lifestream'); ?></label></th>
            <td>
                <select name="lifestream_digest_interval" id="id_digest_interval" onchange="handleDigestTimeField();">
                    <?php foreach ($lifestream_digest_intervals as $interval=>$label) {?>
                        <option value="<?php echo $interval; ?>"<?php if (get_option('lifestream_digest_interval') == $interval) echo ' selected="selected"'; ?>><?php echo htmlspecialchars($label); ?></option>
                    <?php } ?>
                </select><span id="id_digest_time_wrap"> @ <select name="lifestream_digest_time" id="id_digest_time">
                    <?php for ($i=0; $i<=24; $i++) {?>
                        <option value="<?php echo $i; ?>"<?php if (get_option('lifestream_digest_time') == $i) echo ' selected="selected"'; ?>><?php echo ($i > 12 ? ($i-12) : ($i == 0 ? 12 : $i)); ?>:00 <?php echo ($i >= 12 ? 'pm' : 'am'); ?></option>
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
                <div class="helptext"><?php _e('This determines the approximate time when your digest should be posted.', 'lifestream'); ?>
            </td>
        </tr>
        <tr>
            <th><label for="id_digest_title"><?php _e('Summary Post Title:', 'lifestream'); ?></label></th>
            <td>
                <input type="text" name="lifestream_digest_title" size="40" value="<?php echo htmlspecialchars(get_option('lifestream_digest_title')); ?>"/>
                <div class="helptext"><?php _e('You may use <code>%1$s</code> for the current date, and <code>%2$s</code> for the current time.', 'lifestream'); ?></div>
            </td>
        </tr>
        <tr>
            <th><label for="id_digest_body"><?php _e('Summary Post Body:', 'lifestream'); ?></label></th>
            <td>
                <textarea name="lifestream_digest_body" id="id_digest_body" rows="15" cols="60"><?php echo htmlspecialchars(get_option('lifestream_digest_body')); ?></textarea>
                <div class="helptext"><?php _e('You may use <code>%1$s</code> for the list of events, <code>%2$s</code> for the day, and <code>%3$d</code> for the number of events.', 'lifestream'); ?></div>
            </td>
        </tr>
        <tr>
            <th><label for="id_digest_author"><?php _e('Summary Author:', 'lifestream'); ?></label></th>
            <td>
                <select name="lifestream_digest_author" id="id_digest_author">
                <?php
                $current_author = get_option('lifestream_digest_author');
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
            <th><label for="id_digest_category"><?php _e('Summary Category:', 'lifestream'); ?></label></th>
            <td>
                <select name="lifestream_digest_category" id="id_digest_category">
                <?php
                $current_category = get_option('lifestream_digest_category');
                foreach ($categories as $category)
                {
                    echo '<option value="'.$category->term_id.'"'.($category->term_id == $current_category ? ' selected="selected"' : '').'>'.$category->name.'</option>';
                }
                ?>
                </select>
            </td>
        </tr>
    </table>
    <p class="submit">
        <input type="submit" name="save" value="<?php _e('Save Changes', 'lifestream');?>" />
    </p>
</form>