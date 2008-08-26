<?php
// This is only here to give a good example of a custom feed.
$day = '';
if (count($events))
{
    $today = date('m d Y');
    $yesterday = date('m d Y', time()-86400);
    ?>
    <ul class="lifestream">
    <?php
    foreach ($events as $result)
    {
        $timestamp = $result->get_date();
        if ($today == date('m d Y', $timestamp)) $this_day = 'Today';
        else if ($yesterday == date('m d Y', $timestamp)) $this_day = 'Yesterday';
        else $this_day = ucfirst(htmlentities(date($day_format, $timestamp)));
        
        list($label, $items) = $result->feed->get_render_output($result);
        ?>
        <li class="lifestream_feedid_<?php echo $result->feed->get_constant('ID'); ?>" style="background-image: url('<?php echo $lifestream_path . '/images/'. $result->feed->get_constant('ID'); ?>.png');">
            <span class="lifestream_text"><?php echo $label; ?><p><?php echo $items[0]; ?></p></span>
            
        </li>
        <?php
    } ?>
    </ul>
    <?php
}
else
{
    ?>
    <p class="lifestream"><?php _e('There are no events to show at this time.', 'lifestream'); ?></p>
    <?php
}
?>