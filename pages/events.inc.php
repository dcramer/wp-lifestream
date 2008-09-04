<h2><?php _e('LifeStream Events', 'lifestream'); ?></h2><?php

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
                <th scope="col" class="num"><?php _e('Feed', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Event', 'lifestream'); ?></th>
                <th scope="col" style="width: 150px;"><?php _e('Date', 'lifestream'); ?></th>
                <th scope="col"><?php _e('Owner', 'lifestream'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) { ?>
                <tr valign="top">
                    <th scope="row" class="check-column"><input type="checkbox" name="id[]" value="<?php echo $result->id; ?>" /></th>
                    <td class="num"><?php echo $result->id; ?></td>
                    <td><?php echo htmlspecialchars(get_class_constant($lifestream_feeds[$result->feed], 'NAME')); ?></td>
                    <td class="num"><a href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->feed_id; ?>"><?php echo $result->feed_id; ?></a></td>
                    <td><strong><a class="row-title" href="<?php echo htmlspecialchars($result->link); ?>"<?php if (!$result->visible) echo ' style="text-decoration: line-through;"'; ?>><?php echo $result->link; ?></a></strong></td>
                    <td><?php echo date($date_format, $result->timestamp); ?></td>
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
            <input type="submit" value="<?php _e('Delete'); ?>" name="op" class="button-secondary delete" />
        </div>
        <br class="clear" />
    </div>
<?php } else { ?>
    <p><?php _e('There are no events to show.', 'lifestream'); ?></p>
<?php } ?>