<?php
// This is only here to give a good example of a custom feed.
$day = '';
if (count($events))
{
    ?>
    <ul class="lifestream">
    <?php
    foreach ($events as $result)
    {
        ?>
        <li class="lifestream_feedid_<?php echo $result->feed->id; ?> lifestream_feed_<?php echo $result->feed->get_constant('ID'); ?>" style="background-image: url('<?php echo $result->feed->get_icon_url(); ?>');">
            <div class="lifestream_text"><?php echo $result->render($_); ?></div>
        </li>
        <?php
    } ?>
    </ul>
    <?php
}
else
{
    ?>
    <p class="lifestream"><?php $lifestream->_e('There are no events to show at this time.'); ?></p>
    <?php
}
?>