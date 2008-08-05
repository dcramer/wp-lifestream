<h2><?php echo __('Add a Feed', 'lifestream');?></h2>
<noscript>
    <style type="text/css">.requires-javascript { display: none; }</style>
    <p>You will need JavaScript enabled in order to add a feed.</p>
</noscript>
<div class="requires-javascript" id="poststuff">
    <p>Add a new feed by first selecting the type of feed: 
    <select id="id_feed_type" onkeyup="showFeedOptions(this.options[this.selectedIndex].value);" onchange="showFeedOptions(this.options[this.selectedIndex].value);"><?php
    foreach ($lifestream_feeds as $identifier=>$class_name)
    {
        ?><option value="<?php echo $identifier; ?>"<?php if ($_POST['feed_type'] == $identifier) echo ' selected="selected"'; ?>><?php echo htmlspecialchars(get_class_constant($class_name, 'NAME')); ?></option><?php
    }
    ?></select></p>
    <?php
    foreach ($lifestream_feeds as $identifier=>$class_name)
    {
        $feed = new $class_name();
        $options = $feed->get_options();
        ?>
        <form action="?page=<?php echo $basename; ?>&amp;action=feeds" method="post" id="feed_options_<?php echo $identifier; ?>" style="display:none;">
            <h3><?php echo __(get_class_constant($class_name, 'NAME'), 'lifestream');?> Feed Settings</h3>
            <?php if ($description = get_class_constant($class_name, 'DESCRIPTION')) { ?>
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
                    <?php $current_value = (isset($_POST[$option]) ? $_POST[$option] : $option_meta[2]); ?>
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
                <?php if ($feed->get_constant('CAN_GROUP')) { ?>
                    <tr>
                        <th>&nbsp;</th>
                        <td><label><input type="checkbox" name="grouped" value="1"<?php if ($_POST['grouped']) echo ' checked="checked"'; ?>/> Group events from the same day together.</label></td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <?php if ($url = get_class_constant($class_name, 'URL')) { ?>
            <p>Find more information about <?php echo htmlspecialchars(get_class_constant($class_name, 'NAME')); ?> by visiting <a href="<?php echo htmlspecialchars($url); ?>"><?php echo htmlspecialchars($url); ?></a>.</p>
            <?php } ?>
            <p class="submit">
                <input type="submit" name="save" value="<?php echo __('Add Feed', 'lifestream');?>" />
            </p>
    
        </form>
    <?php
    }
    ?>
    <script type="text/javascript">
        var _current_feed = null;
        function showFeedOptions(feed) {
            if (_current_feed) _current_feed.style.display = 'none';
            var el = document.getElementById('feed_options_' + feed);
            if (!el) return;
            _current_feed = el;
            _current_feed.style.display = '';
        }
        var el = document.getElementById('id_feed_type');
        showFeedOptions(el.options[el.selectedIndex].value);
    </script>
</div>