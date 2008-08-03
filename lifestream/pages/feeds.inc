<h2><?php echo __('LifeStream Feeds', 'lifestream'); ?> <small>(<a href="?page=<?php echo $basename ?>">Go to General Configuration</a>)</small></h2><?php

if (count($results))
{
    ?>
    <p>Feeds automatically refresh every hour, but if you wish to manually refresh a feed's events you can do so here.</p>
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col" style="width: 80px;"><?php echo __('Feed Type', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php echo __('ID', 'lifestream'); ?></th>
                <th scope="col"><?php echo __('Description', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php echo __('Events', 'lifestream'); ?></th>
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
                    <td><strong><a class="row-title" href="?page=<?php echo $basename; ?>&amp;action=feeds&amp;op=edit&amp;id=<?php echo $result->id; ?>"><?php echo htmlspecialchars((string)$instance); ?></a></strong></td>
                    <td class="num"><?php echo $result->events; ?></td>
                    <td><a href="?page=<?php echo $basename; ?>&amp;action=feeds&amp;op=delete&amp;id=<?php echo $result->id; ?>">Delete</a></td>
                    <td><a href="?page=<?php echo $basename; ?>&amp;action=feeds&amp;op=refresh&amp;id=<?php echo $result->id; ?>">Refresh</a></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <br />
<?php } ?>
<?php include('add-feed.inc'); ?>