<?php
$authors = get_users_of_blog();

?>
<h2><?php _e('Add a Feed', 'lifestream');?> <small><?php printf(__('(<a href="%s">Back to LifeStream Feeds</a>)', 'lifestream'), '?page=lifestream.php'); ?></small></h2>

<form action="?page=lifestream.php&amp;op=add&amp;feed=<?php echo urlencode($identifier); ?>" method="post" id="feed_options_<?php echo htmlspecialchars($identifier); ?>">
    <h3><?php printf(__('%s Feed Settings', 'lifestream'), $feed->get_constant('NAME')) ;?></h3>
    <?php if ($description = $feed->get_constant('DESCRIPTION')) { ?>
    <p><?php echo $description; ?></p>
    <?php } ?>
    <input type="hidden" name="feed_type" value="<?php echo htmlspecialchars($identifier); ?>"/>
    <table class="form-table">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tbody>
        <?php foreach ($options as $option=>$option_meta) { ?>
            <?php $current_value = (isset($_POST[$option]) ? stripslashes($_POST[$option]) : $option_meta[2]); ?>
            <tr>
                <?php if (is_array($option_meta[3])) { ?>
                    <th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars(__($option_meta[0], 'lifestream'));?></label></th>
                    <td><select name="<?php echo $option;?>">
                    <?php foreach ($option_meta[3] as $choice=>$label) { ?>
                        <option value="<?php echo $choice;?>"<?php if ($current_value == $choice) echo ' selected="selected"'; ?>><?php echo htmlspecialchars(__($label, 'lifestream'));?></option>
                    <?php } ?>
                    </select>
                    <?php if ($option_meta[4]) { ?>
                    <div class="helptext"><?php echo __($option_meta[4], 'lifestream'); ?></div>
                    <?php } ?></td>
                <?php } elseif (is_bool($option_meta[3])) { ?>
                    <th>&nbsp;</th>
                    <td><label<?php if ($option_meta[1]) echo ' class="required"'; ?>><input type="checkbox" value="1"<?php if ($current_value == 1) echo ' checked="checked"'; ?> name="<?php echo $option;?>" /> <?php echo htmlspecialchars(__($option_meta[0], 'lifestream'));?></label>
                    <?php if ($option_meta[4]) { ?>
                    <div class="helptext"><?php echo __($option_meta[4], 'lifestream'); ?></div>
                    <?php } ?></td>
                <?php } else { ?>
                    <th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars(__($option_meta[0], 'lifestream'));?></label></th>
                    <td><input name="<?php echo $option;?>" type="text" size="40" value="<?php echo htmlspecialchars($current_value); ?>">
                    <?php if ($option_meta[4]) { ?>
                    <div class="helptext"><?php echo __($option_meta[4], 'lifestream'); ?></div>
                    <?php } ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        <tr>
            <th>&nbsp;</th>
            <td>
                <label><input type="checkbox" name="show_label" value="1"<?php if (!isset($_POST['save']) || $_POST['show_label'] == '1') echo ' checked="checked"'; ?>/> <?php _e('Show labels for events in this feed. This will not affect grouped events.', 'lifestream'); ?></label>
                <div class="helptext">e.g. <?php printf($feed->get_constant('LABEL_SINGLE'), '#', $feed->get_public_name()); ?></div>
            </td>
        </tr>
        
        <?php if ($feed->get_constant('CAN_GROUP')) { ?>
            <tr>
                <th>&nbsp;</th>
                <td>
                    <label><input type="checkbox" name="grouped" id="id_grouped" value="1"<?php if ($_POST['grouped'] == '1') echo ' checked="checked"'; ?>/> <?php _e('Group events from the same day together.', 'lifestream'); ?></label>
                </td>
            </tr>
        <?php } ?>
        
        <tr>
            <th><label for="id_owner"><?php _e('Owner:', 'lifestream'); ?></label></th>
            <td>
                <?php if (current_user_can('manage_options')) { ?>
                <select name="owner" id="id_owner">
                    <?php
                    foreach ($authors as $author)
                    {
                        $usero = new WP_User($author->user_id);
                        $author = $usero->data;
                        echo '<option value="'.$author->ID.'"'.($userdata->ID == $author->ID ? ' selected="selected"' : '').'>'.$author->user_nicename.'</option>';
                    }
                    ?>
                </select>
                <?php } else { ?>
                <?php echo $userdata->user_nicename; ?>
                <?php } ?>
            </td>
        </tr>
        </tbody>
    </table>
    <?php if ($url = $feed->get_constant('URL')) { ?>
    <p><?php printf(__('Find more information about %s by visiting <a href="%s">%s</a>.', 'lifestream'), htmlspecialchars($feed->get_constant('NAME')), htmlspecialchars($url), htmlspecialchars($url)); ?></p>
    <?php } ?>
    <p class="submit">
        <input type="submit" name="save" value="<?php _e('Add Feed', 'lifestream');?>" />
    </p>
</form>
