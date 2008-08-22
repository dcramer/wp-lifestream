<h2><?php _e('Edit Feed', 'lifestream'); ?> <small><?php printf(__('(<a href="%s">Back to LifeStream Feeds</a>)', 'lifestream'), '?page=' . $basename . '&amp;action=feeds'); ?></small></h2>

<form action="?page=<?php echo $basename; ?>&amp;action=feeds&amp;op=edit&amp;id=<?php echo $instance->id; ?>" method="post">
    <h3><?php printf(__('%s Feed Settings', 'lifestream'), $instance->get_constant('NAME')); ?></h3>
    <?php if ($description = $instance->get_constant('DESCRIPTION')) { ?>
    <p><?php echo $description; ?></p>
    <?php } ?>
    <input type="hidden" name="feed_type" value="<?php echo $identifier; ?>"/>
    <table class="options">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tbody>
        <?php foreach ($options as $option=>$option_meta) { ?>
            <?php $current_value = (isset($_POST[$option]) ? $_POST[$option] : $instance->options[$option]); ?>
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
                    <td><input name="<?php echo $option;?>" type="text" value="<?php echo htmlspecialchars($current_value); ?>">
                    <?php if ($option_meta[4]) { ?>
                    <div class="helptext"><?php echo __($option_meta[4], 'lifestream'); ?></div>
                    <?php } ?></td>
                <?php } ?>
            </tr>
        <?php } ?>
        <tr>
            <th>&nbsp;</th>
            <td>
                <label><input type="checkbox" name="show_label" value="1"<?php if (isset($_POST['show_label']) ? $_POST['show_label'] : $instance->options['show_label']) echo ' checked="checked"'; ?>/> <?php _e('Show labels for events in this feed. This will not effect grouped events.', 'lifestream'); ?></label>
                <div class="helptext">e.g. <?php printf($instance->get_constant('LABEL_SINGLE'), '#', $instance->get_public_name()); ?></div>
            </td>
        </tr>
        <?php if ($instance->get_constant('CAN_GROUP')) { ?>
            <tr>
                <th>&nbsp;</th>
                <td>
                    <label><input type="checkbox" name="grouped" value="1"<?php if (isset($_POST['grouped']) ? $_POST['grouped'] : $instance->options['grouped']) echo ' checked="checked"'; ?>/> <?php _e('Group events from the same day together.', 'lifestream'); ?></label>
                    <div class="helptext"><?php _e('This will not affect any event\'s already listed.', 'lifestream'); ?></div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php if ($url = $instance->get_constant('URL')) { ?>
        <p><?php printf(__('Find more information about %s by visiting <a href="%s">%s</a>.', 'lifestream'), htmlspecialchars($instance->get_constant('NAME')), htmlspecialchars($url), htmlspecialchars($url)); ?></p>
    <?php } ?>
    <p class="submit">
        <input type="submit" name="save" value="<?php _e('Save Feed', 'lifestream');?>" />
    </p>
</form>
<br />
<h2><?php _e('Recent Events', 'lifestream'); ?></h2><br />
<table class="widefat">
    <thead>
        <tr>
            <th scope="col" class="num"><?php _e('ID', 'lifestream'); ?></th>
            <th scope="col"><?php _e('Event', 'lifestream'); ?></th>
            <th scope="col" style="width: 150px;"><?php _e('Date', 'lifestream'); ?></th>
            <th scope="col" style="width: 40px;">&nbsp;</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($instance->get_events(50) as $result) { ?>
            <tr valign="top">
                <td class="num"><?php echo $result->id; ?></td>
                <td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->link); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo $result->link; ?></a></strong></td>
                <td><?php echo date($date_format, $result->timestamp); ?></td>
                <td><a href="?page=<?php echo $basename; ?>&amp;action=events&amp;op=delete&amp;id=<?php echo $result->id; ?>"><?php _e('Delete', 'lifestream'); ?></a></td>
            </tr>
        <?php } ?>
    </tbody>
</table>