<?php
$authors = get_users_of_blog();

?>
<h2><?php $lifestream->_e('Add a Feed');?> <small><?php $lifestream->_e('(<a href="%s">Back to LifeStream Feeds</a>)', '?page=lifestream.php'); ?></small></h2>

<form action="?page=lifestream.php&amp;op=add&amp;feed=<?php echo urlencode($identifier); ?>" method="post" id="feed_options_<?php echo htmlspecialchars($identifier); ?>">
    <h3><?php $lifestream->_e('%s Feed Settings', $feed->get_constant('NAME')) ;?></h3>
    <?php if ($description = $feed->get_constant('DESCRIPTION')) { ?>
    <p><?php echo nl2br($description); ?></p>
    <?php } ?>
    <input type="hidden" name="feed_type" value="<?php echo htmlspecialchars($identifier); ?>"/>
    <table class="form-table">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
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
                    <?php if ($option_meta[4]) { ?>
                    <div class="helptext"><?php echo $option_meta[4]; ?></div>
                    <?php } ?></td>
                <?php } else { ?>
                    <th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars($option_meta[0]);?></label></th>
                    <td><input name="<?php echo $option;?>" type="text" size="40" value="<?php echo htmlspecialchars($current_value); ?>">
                    <?php if ($option_meta[4]) { ?>
                    <div class="helptext"><?php echo $option_meta[4]; ?></div>
                    <?php } ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        
        <tr>
            <th><label>Feed Label:</label><br /><small>(<?php $lifestream->_e('Optional'); ?>)</small></th>
            <td>
                <?php $current_value = (isset($_POST['feed_label']) ? stripslashes($_POST['feed_label']) : ''); ?>
                <input type="text" name="feed_label" value="<?php echo htmlspecialchars($current_value); ?>"/>
                <div class="helptext"><?php $lifestream->_e('A label to use for this feed instead of the default.'); ?></div>
            </td>
        </tr>
        <tr>
            <th><label>Icon URL:</label><br /><small>(<?php $lifestream->_e('Optional'); ?>)</small></th>
            <td>
                <?php $current_value = (isset($_POST['icon_url']) ? stripslashes($_POST['icon_url']) : ''); ?>
                <input type="text" name="icon_url" value="<?php echo htmlspecialchars($current_value); ?>"/> <input type="checkbox" name="auto_icon" value="1"<?php if(empty($current_value)) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use websites default favicon.'); ?>
                <div class="helptext"><?php $lifestream->_e('An icon to use for this feed instead of the default.'); ?></div>
            </td>
        </tr>
        <?php if ($feed->get_constant('CAN_GROUP') && !$feed->get_constant('MUST_GROUP')) { ?>
            <tr>
                <th>&nbsp;</th>
                <td>
                    <label><input type="checkbox" name="grouped" id="id_grouped" value="1"<?php if ($_POST['grouped'] == '1') echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Group events from the same day together.'); ?></label>
                </td>
            </tr>
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
    <?php if ($url = $feed->get_constant('URL')) { ?>
    <p><?php $lifestream->_e('Find more information about %s by visiting <a href="%s">%s</a>.', htmlspecialchars($feed->get_constant('NAME')), htmlspecialchars($url), htmlspecialchars($url)); ?></p>
    <?php } ?>
    <p class="submit">
        <input type="submit" name="save" value="<?php $lifestream->_e('Add Feed');?>" />
    </p>
</form>
