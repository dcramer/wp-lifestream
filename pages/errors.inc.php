<h2><?php _e('LifeStream Errors', 'lifestream'); ?></h2><?php

if (count($results))
{
    ?>
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
        <br class="clear" />
    </div>

    <br class="clear" />
    <table class="widefat">
        <thead>
            <tr>
                <th scope="col" class="num"><?php _e('Feed', 'lifestream'); ?></th>
                <th scope="col" colspan="2"><?php _e('Message', 'lifestream'); ?></th>
                <th scope="col" style="width: 150px;"><?php _e('Date', 'lifestream'); ?></th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($results as $result) { ?>
                <tr valign="top">
                    <td class="num">
                        <?php
                        if ($result->feed_id)
                        {
                            ?><a href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->feed_id; ?>"><?php echo $result->feed_id; ?></a><?php
                        }
                        else
                        {
                            echo '&ndash;';
                        }
                        ?>
                    </td>
                        <?php
                        if ($result->feed_id)
                        {
                            ?><td class="icon">
                            <img src="<?php echo $lifestream_path; ?>/images/<?php echo get_class_constant($lifestream_feeds[$result->feed], 'ID'); ?>.png"/></td>
                            <td><?php
                        }
                        else
                        {
                            ?><td colspan="2"><?php
                        }
                        if ($result->has_viewed)
                        {
                            ?><strong><?php echo LifeStream_Feed::parse_urls(htmlspecialchars($result->message)); ?></strong><?php
                        }
                        else
                        {
                            echo LifeStream_Feed::parse_urls(htmlspecialchars($result->message));
                        }
                        ?>
                    </td>
                    <td><?php echo date($date_format, $result->timestamp); ?></td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
    <div class="tablenav">
        <?php
        if ( $page_links )
            echo "<div class='tablenav-pages'>$page_links</div>";
        ?>
        <br class="clear" />
    </div>
<?php } else { ?>
    <p><?php _e('There are no errors to show.', 'lifestream'); ?></p>
<?php } ?>