<h2><?php _e('LifeStream Events', 'lifestream'); ?></h2><?php

if (count($results))
{
    ?>
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col" style="width: 80px;"><?php _e('Feed Type', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php _e('Feed', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php _e('ID', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Event', 'lifestream'); ?></th>
                <th scope="col" style="width: 150px;"><?php _e('Date', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Owner', 'lifestream'); ?></th>
                <th scope="col" style="width: 40px;">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) { ?>
                <tr valign="top">
                    <td><?php echo htmlspecialchars(get_class_constant($lifestream_feeds[$result->feed], 'NAME')); ?></td>
                    <td class="num"><a href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->feed_id; ?>"><?php echo $result->feed_id; ?></a></td>
                    <td class="num"><?php echo $result->id; ?></td>
                    <td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->link); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo $result->link; ?></a></strong></td>
                    <td><?php echo date($date_format, $result->timestamp); ?></td>
                    <td><?php echo $result->owner; ?></td>
                    <td><a href="?page=lifestream-events.php&amp;op=delete&amp;id=<?php echo $result->id; ?>"><?php _e('Delete', 'lifestream'); ?></a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>