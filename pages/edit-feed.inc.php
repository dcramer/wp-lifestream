<?php
$authors = get_users_of_blog();

?>
<h2><?php $lifestream->_e('Edit Feed'); ?> <small><?php $lifestream->_e('(<a href="%s">Back to LifeStream Feeds</a>)', '?page=lifestream.php'); ?></small></h2>

<?php if ($instance) { ?>
    <form action="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $instance->id; ?>" method="post">
        <h3><?php $lifestream->_e('%s Feed Settings', $instance->get_constant('NAME')); ?></h3>
        <?php if ($description = $instance->get_constant('DESCRIPTION')) { ?>
        <p><?php echo nl2br($description); ?></p>
        <?php } ?>
        <table class="form-table">
            <colgroup>
                <col style="width: 150px;"/>
                <col/>
            </colgroup>
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
                    <?php $current_value = (isset($_POST['feed_label']) ? stripslashes($_POST['feed_label']) : $instance->options['feed_label']); ?>
                    <input type="text" name="feed_label" value="<?php echo htmlspecialchars($current_value); ?>"/>
                    <div class="helptext"><?php $lifestream->_e('A label to use for this feed instead of the default.'); ?></div>
                </td>
            </tr>
            <tr>
                <?php $current_value = (isset($_POST['auto_icon']) ? $_POST['auto_icon'] : $instance->options['auto_icon']); ?>
                <th>&nbsp;</th>
                <td><input type="checkbox" id="id_auto_icon"name="auto_icon" onclick="checkAutoIcon" value="1"<?php if($current_value) echo ' checked="checked"'; ?>/> <?php $lifestream->_e('Use websites default favicon.'); ?></td>
            </tr>
            <tr id="id_icon_url_row">
                <th><label>Icon URL:</label><br /><small>(<?php $lifestream->_e('Optional'); ?>)</small></th>
                <td>
                    <?php $current_value = (isset($_POST['icon_url']) ? stripslashes($_POST['icon_url']) : $instance->options['icon_url']); ?>
                    <input type="text" name="icon_url" value="<?php echo htmlspecialchars($current_value); ?>"/>
                    <div class="helptext"><?php $lifestream->_e('An icon to use for this feed instead of the default.'); ?></div>
                </td>
            </tr>
            <script type="text/javascript">
            function checkAutoIcon() {
                var obj = document.getElementById('id_auto_icon');
                document.getElementById('id_icon_url_row').style.display = (obj.checked ? 'none': '');
            }
            checkAutoIcon();
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
        <?php if ($url = $instance->get_constant('URL')) { ?>
            <p><?php $lifestream->_e('Find more information about %s by visiting <a href="%s">%s</a>.', htmlspecialchars($instance->get_constant('NAME')), htmlspecialchars($url), htmlspecialchars($url)); ?></p>
        <?php } ?>
        <p class="submit">
            <input type="submit" name="save" value="<?php $lifestream->_e('Save Feed');?>" />
        </p>
    </form>
    <br />
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
                        <td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->link); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo $result->link; ?></a></strong></td>
                        <td><?php echo date($date_format, $result->timestamp); ?></td>
                        <td><a href="?page=lifestream-events.php&amp;op=delete&amp;id=<?php echo $result->id; ?>"><?php $lifestream->_e('Delete'); ?></a></td>
                    </tr>
                <?php } ?>
            </tbody>
        </table>
    <?php } else { ?>
        <p><?php $lifestream->_e('There are no events to show.'); ?></p>
    <?php } ?>    
<?php } ?>