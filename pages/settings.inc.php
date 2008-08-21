<?php

$categories = get_categories('hide_empty=0');
$authors = get_users_of_blog();

?>
<h2><?php _e('LifeStream Configuration', 'lifestream');?></h2>
<p><?php printf(__('The following settings that will affect feeds globally. If you wish to modify per-feed settings, you may do so via the <a href="%s">Feed Management page</a>.', 'lifestream'), '?page=' . $basename . '&amp;action=feeds'); ?></p>
<form method="post" action="">
    <table class="options">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tbody>
            <tr>
                <th><label for="id_day_format"><?php _e('Day Format:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_day_format" id="id_day_format" value="<?php echo htmlspecialchars(get_option('lifestream_day_format')); ?>"/> (Ex: <?php echo date(get_option('lifestream_day_format')); ?>)
                    <div class="helptext"><?php _e('For more information, please see PHP\'s <a href="http://www.php.net/date/">date()</a> method for more information.', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_hour_format"><?php _e('Hour Format:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_hour_format" id="id_hour_format" value="<?php echo htmlspecialchars(get_option('lifestream_hour_format')); ?>"/> (Ex: <?php echo date(get_option('lifestream_hour_format')); ?>)
                    <div class="helptext"><?php _e('For more information, please see PHP\'s <a href="http://www.php.net/date/">date()</a> method for more information.', 'lifestream'); ?></div></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_timezone"><?php _e('Current Time:', 'lifestream'); ?></label></th>
                <td>
                    <select name="lifestream_timezone">
                        <?php for ($i=-12; $i<12; $i++) {?>
                            <option value="<?php echo $i; ?>"<?php if (get_option('lifestream_timezone') == $i) echo ' selected="selected"'; ?>><?php echo date('g:ia', time()+(3600*$i)); ?></option>
                        <?php } ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label for="id_update_interval"><?php _e('Update Interval:', 'lifestream'); ?></label></th>
                <td>
                    <input type="text" class="text" name="lifestream_update_interval" id="id_update_interval" value="<?php echo htmlspecialchars(get_option('lifestream_update_interval')); ?>"/>
                    <div class="helptext"><?php _e('The number of minutes between updates to your feeds. Value is in minutes.', 'lifestream'); ?></div></p>
                </td>
            </tr>
        </tbody>
    </table>
    <br />
    <h2><?php _e('Daily Digest'); ?></h2>
    <p><?php _e('LifeStream gives you the ability to create a new blog post each day, containing all of the events which happened on up to that point on that day.', 'lifestream'); ?></p>
    <table class="options">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tr>
            <th>&nbsp;</th>
            <td><label for="id_daily_digest"><input type="checkbox" name="lifestream_daily_digest" id="id_daily_digest" value="1"<?php if (get_option('lifestream_daily_digest')) echo ' checked="checked"'; ?>/> <?php _e('Post a daily summary of my lifestream.', 'lifestream'); ?></label>
            </td>
        </tr>
        <tr>
            <th><label for="id_digest_title"><?php _e('Summary Post Title:', 'lifestream'); ?></label></th>
            <td>
                <input type="text" name="lifestream_digest_title" size="40" value="<?php echo htmlspecialchars(get_option('lifestream_digest_title')); ?>"/>
                <div class="helptext"><?php _e('You may use <code>%s</code> for the current date formatted with your <em>Day Format</em> option.', 'lifestream'); ?></div>
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
                    echo '<option value="'.$author->ID.'"'.($author->ID == $current_author ? ' selected="selected"' : '').'>'.$author->user_nicename.'</option>';
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