<h2><?php _e('LifeStream Feeds', 'lifestream'); ?> <small>(<a href="?page=lifestream.php&amp;op=refreshall"><?php _e('Refresh All Feeds', 'lifestream'); ?></a>)</small></h2><?php

if (count($results))
{
    ?>
    <form method="post" action="">
    <div class="tablenav">
        <?php
        $page_links = paginate_links( array(
            'base' => add_query_arg( 'paged', '%#%' ),
            'format' => '',
            'total' => $number_of_pages,
            'current' => $page,
        ));

        if ( $page_links )
            echo "<div class='tablenav-pages'>$page_links</div>";
        ?>
        <div class="alignleft">
            <input type="submit" value="<?php _e('Refresh', 'lifestream'); ?>" name="op" class="button-secondary refresh" />
            <input type="submit" value="<?php _e('Delete'); ?>" name="op" class="button-secondary delete" />
        </div>
        <br class="clear" />
    </div>

    <br class="clear" />
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col" class="check-column"><input type="checkbox" /></th>
                <th scope="col" class="num"><?php _e('ID', 'lifestream'); ?></th>
                <th scope="col" style="width: 80px;"><?php _e('Feed Type', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Description', 'lifestream'); ?></th>
                <th scope="col" class="num"><?php _e('Events', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Owner', 'lifestream'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) { ?>
            <?php $instance = LifeStream_Feed::construct_from_query_result($result); ?>
                <tr valign="top">
                    <th scope="row" class="check-column"><input type="checkbox" name="id[]" value="<?php echo $result->id; ?>" /></th>
                    <td class="num"><?php echo $result->id; ?></td>
                    <td><?php echo htmlspecialchars($instance->get_constant('NAME')); ?></td>
                    <td><strong><a class="row-title" href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->id; ?>"><?php echo htmlspecialchars((string)$instance); ?></a></strong></td>
                    <td class="num"><?php echo $result->events; ?></td>
                    <td><?php echo $result->owner; ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="tablenav">
        <?php
        if ( $page_links )
            echo "<div class='tablenav-pages'>$page_links</div>";
        ?>
        <div class="alignleft">
            <input type="submit" value="<?php _e('Refresh', 'lifestream'); ?>" name="op" class="button-secondary refresh" />
            <input type="submit" value="<?php _e('Delete'); ?>" name="op" class="button-secondary delete" />
        </div>
        <br class="clear" />
    </div>
    </form>
<?php } else { ?>
    <p><?php _e('You do not currently have ownership of any feeds.', 'lifestream'); ?></p>
<?php } ?>
<br />
<?php include('add-feed.inc.php'); ?>