<h2><?php echo __('Edit Feed', 'lifestream'); ?> <small>(<a href="?page=<?php echo $basename ?>&amp;action=feeds">Back to LifeStream Feeds</a>)</small></h2>

<form action="?page=<?php echo $basename; ?>&amp;action=feeds&amp;op=edit&amp;id=<?php echo $instance->id; ?>" method="post">
    <h3><?php echo __($instance->get_constant('NAME'), 'lifestream');?> Feed Settings</h3>
    <?php if ($description = $instance->get_constant('DESCRIPTION')) { ?>
    <p><?php echo __($description, 'lifestream');?></p>
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
                    <th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars(__($option_meta[0], 'lifestream'));?>:</label></th>
                    <td><select name="<?php echo $option;?>">
                    <?php foreach ($option_meta[3] as $choice=>$label) { ?>
                        <option value="<?php echo $choice;?>"<?php if ($current_value == $choice) echo ' selected="selected"'; ?>><?php echo htmlspecialchars($label);?></option>
                    <?php } ?>
                    </select></td>
                <?php } elseif (is_bool($option_meta[3])) { ?>
                    <th>&nbsp;</th>
                    <td><label<?php if ($option_meta[1]) echo ' class="required"'; ?>><input type="checkbox" value="1"<?php if ($current_value == 1) echo ' checked="checked"'; ?> name="<?php echo $option;?>" /> <?php echo htmlspecialchars(__($option_meta[0], 'lifestream'));?></label></td>
                <?php } else { ?>
                    <th><label<?php if ($option_meta[1]) echo ' class="required"'; ?> for="id_<?php echo $option;?>"><?php echo htmlspecialchars(__($option_meta[0], 'lifestream'));?>:</label></th>
                    <td><input name="<?php echo $option;?>" type="text" value="<?php echo htmlspecialchars($current_value); ?>"></td>
                <?php } ?>
            </tr>
        <?php } ?>
        <tr>
            <th>&nbsp;</th>
            <td>
                <label><input type="checkbox" name="show_label" value="1"<?php if (isset($_POST['show_label']) ? $_POST['show_label'] : $instance->options['show_label']) echo ' checked="checked"'; ?>/> Show labels for events in this feed. This will not effect grouped events.</label>
                <div class="helptext">(e.g. <?php echo sprintf($instance->get_constant('LABEL_SINGLE'), '#', $instance->get_public_name()); ?>)</div>
            </td>
        </tr>
        <?php if ($instance->get_constant('CAN_GROUP')) { ?>
            <tr>
                <th>&nbsp;</th>
                <td>
                    <label><input type="checkbox" name="grouped" value="1"<?php if (isset($_POST['grouped']) ? $_POST['grouped'] : $instance->options['grouped']) echo ' checked="checked"'; ?>/> Group events from the same day together.</label>
                    <div class="helptext">(This will not affect any event's already listed.)</div>
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>
    <?php if ($about['url']) { ?>
    <p>Find more information about <?php echo htmlspecialchars($about['name']); ?> by visiting <a href="<?php echo htmlspecialchars($about['url']); ?>"><?php echo htmlspecialchars($about['url']); ?></a>.</p>
    <?php } ?>
    <p class="submit">
        <input type="submit" name="save" value="<?php echo __('Save Feed', 'lifestream');?>" />
    </p>
</form>
<br />
<h2><?php echo __('Recent Events', 'lifestream'); ?></h2><br />
<table class="widefat">
    <thead>
        <tr>
            <th scope="col" class="num"><?php echo __('ID', 'lifestream'); ?></th>
            <th scope="col"><?php echo __('Event', 'lifestream'); ?></th>
            <th scope="col" style="width: 150px;"><?php echo __('Date', 'lifestream'); ?></th>
            <th scope="col" style="width: 40px;">&nbsp;</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($instance->get_events(50) as $result) { ?>
            <tr valign="top">
                <td class="num"><?php echo $result->id; ?></td>
                <td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->link); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo $result->link; ?></a></strong></td>
                <td><?php echo date($date_format, $result->timestamp); ?></td>
                <td><a href="?page=<?php echo $basename; ?>&amp;action=events&amp;op=delete&amp;id=<?php echo $result->id; ?>">Delete</a></td>
            </tr>
        <?php } ?>
    </tbody>
</table>