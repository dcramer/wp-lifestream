<h2><?php echo __('LifeStream Configuration', 'lifestream');?> <small>(<a href="?page=<?php echo $basename ?>&amp;action=feeds">Go to Feed Management</a>)</small></h2>
<p>The following settings that will affect feeds globally. If you wish to modify per-feed settings, you may do so via the <a href="?page=<?php echo $basename ?>&amp;action=feeds">Feed Management page</a>.</p>
<form method="post" action="">
    <table class="options">
        <colgroup>
            <col style="width: 150px;"/>
            <col/>
        </colgroup>
        <tbody>
            <tr>
                <th><label for="id_day_format">Day Format:</label></th>
                <td>
                    <input type="text" class="text" name="lifestream_day_format" id="id_day_format" value="<?php echo htmlspecialchars(get_option('lifestream_day_format')); ?>"/> (Ex: <?php echo date(get_option('lifestream_day_format')); ?>)
                    <p><small>For more information, please see PHP's <a href="http://www.php.net/date/">date()</a> method for more information.</small></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_hour_format">Hour Format:</label></th>
                <td>
                    <input type="text" class="text" name="lifestream_hour_format" id="id_hour_format" value="<?php echo htmlspecialchars(get_option('lifestream_hour_format')); ?>"/> (Ex: <?php echo date(get_option('lifestream_hour_format')); ?>)
                    <p><small>For more information, please see PHP's <a href="http://www.php.net/date/">date()</a> method for more information.</small></p>
                </td>
            </tr>
            <tr>
                <th><label for="id_timezone">Current Time:</label></th>
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
        <input type="submit" name="save" value="<?php echo __('Save Changes', 'lifestream');?>" />
    </p>
</form>