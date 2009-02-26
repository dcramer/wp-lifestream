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
        <colgroup>
            <col style="width:20px;"/>
            <col style="width:40px;"/>
            <col style="width:16px;"/>
            <col/>
            <col style="width:130px"/>
            <col style="width:50px;"/>
            <col style="width:90px;"/>
        </colgroup>
        <thead>
            <tr>
                <th scope="col" class="check-column"><input type="checkbox" /></th>
                <th scope="col" class="num"><?php _e('ID', 'lifestream'); ?></th>
                <th scope="col" colspan="2"><?php _e('Description', 'lifestream'); ?></th>
                <th scope="col" class="date"><?php _e('Last Update', 'lifestream'); ?></th>
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
                    <td class="icon"><img src="<?php echo $lifestream_path; ?>/images/<?php echo $instance->get_constant('ID'); ?>.png"/></td>
                    <td><strong><a class="row-title" href="?page=lifestream.php&amp;op=edit&amp;id=<?php echo $result->id; ?>"><?php echo htmlspecialchars($instance->get_feed_display()); ?></a></strong><?php
                    if (isset($feedmsgs[$result->id]) && !empty($feedmsgs[$result->id]))
                    {
                        $msg = $feedmsgs[$result->id];
                        if (is_int($msg)) echo '<div class="success">'.$msg.' new event(s).</div>';
                    }
                    ?></td>
                    <td class="date"><?php echo date('F j, Y', $result->timestamp).'<br/>'.date('g:ia', $result->timestamp); ?></td>
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

<h2><?php _e('Add a Feed', 'lifestream');?></h2>
<p><?php _e('Add a new feed by first selecting the type of feed:', 'lifestream'); ?></p>
<ul class="feedlist">
    <?php
    foreach ($lifestream_feeds as $identifier=>$class_name)
    {
        ?><li><a href="?page=lifestream.php&amp;op=add&amp;feed=<?php echo urlencode($identifier); ?>" title="<?php echo htmlspecialchars(get_class_constant($class_name, 'NAME')); ?>"><img src="<?php echo $lifestream_path; ?>/images/<?php echo $identifier; ?>.png"/> <?php echo htmlspecialchars(get_class_constant($class_name, 'NAME')); ?></a></li><?php
    }
    ?>
</ul>
<br/><br/><br/>