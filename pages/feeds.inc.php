<h2><?php _e('LifeStream Feeds', 'lifestream'); ?> <small>(<a href="?page=lifestream.php&amp;op=refreshall"><?php _e('Refresh All Feeds', 'lifestream'); ?></a>)</small></h2><?php

if (count($results))
{
    ?>
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col" style="width: 80px;"><?php _e('Feed Type', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php _e('ID', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Description', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php _e('Events', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Owner', 'lifestream'); ?></th>
                <th scope="col" style="width: 40px;">&nbsp;</th>
                <th scope="col" style="width: 40px;">&nbsp;</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) { ?>
            <?php $instance = LifeStream_Feed::construct_from_query_result($result); ?>
                <tr valign="top">
                    <td><?php echo htmlspecialchars($instance->get_constant('NAME')); ?></td>
                    <td class="num"><?php echo $result->id; ?></td>
                    <td><strong><a class="row-title" href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->id; ?>"><?php echo htmlspecialchars((string)$instance); ?></a></strong></td>
                    <td class="num"><?php echo $result->events; ?></td>
                    <td><?php echo $result->owner; ?></td>
                    <td><a href="?page=lifestream.php&amp;op=delete&amp;id=<?php echo $result->id; ?>"><?php _e('Delete', 'lifestream'); ?></a></td>
                    <td><a href="?page=lifestream.php&amp;op=refresh&amp;id=<?php echo $result->id; ?>"><?php _e('Refresh', 'lifestream'); ?></a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
<?php } else { ?>
    <p>You do not currently have ownership of any feeds.</p>
<?php } ?>
<br />
<?php include('add-feed.inc.php'); ?>