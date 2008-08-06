<h2><?php _e('LifeStream Configuration', 'lifestream');?> <small><?php printf(__('(<a href="%s">Go to Feed Management</a>)', 'lifestream'), '?page=' . $basename . '&amp;action=feeds'); ?></small></h2>
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
        </tbody>
    </table>
    <p class="submit">
        <input type="submit" name="save" value="<?php _e('Save Changes', 'lifestream');?>" />
    </p>
</form>