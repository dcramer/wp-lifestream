<h2><?php echo __('LifeStream Events', 'lifestream'); ?> <small>(<a href="?page=<?php echo $basename ?>&amp;action=feeds">Go to Feed Management</a>)</small></h2><?php

if (count($results))
{
    ?>
    <p>Feeds automatically refresh every hour looking for new events.</p>
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col" style="width: 80px;"><?php echo __('Feed Type', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php echo __('Feed', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php echo __('ID', 'lifestream'); ?></th>
                <th scope="col"><?php echo __('Event', 'lifestream'); ?></th>
                <th scope="col" style="width: 150px;"><?php echo __('Date', 'lifestream'); ?></th>
                <th scope="col" style="width: 40px;">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) { ?>
                <tr valign="top">
                    <td><?php echo htmlspecialchars(get_class_constant($lifestream_feeds[$result->feed], 'NAME')); ?></td>
                    <td class="num"><a href="?page=<?php echo $basename; ?>&amp;action=feeds&amp;op=edit&amp;id=<?php echo $result->feed_id; ?>"><?php echo $result->feed_id; ?></a></td>
                    <td class="num"><?php echo $result->id; ?></td>
                    <td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->url); ?>"><?php echo $result->text; ?></a></strong></td>
                    <td><?php echo date($date_format, $result->timestamp); ?></td>
                    <td><a href="?page=<?php echo $basename; ?>&amp;action=events&amp;op=delete&amp;id=<?php echo $result->id; ?>">Delete</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } ?>