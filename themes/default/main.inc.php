<?php
$day = '';
$counter = 0;
$newline = "\n\t\t\t\t\t\t";
if (count($events))
{
    $today = date('m d Y');
    $yesterday = date('m d Y', time()-86400);
    
    if ($has_paging)
    {
        echo $newline . '<p class="lifestream-paging">';
        if ($has_prev_page) echo '<a href="' . $lifestream->get_previous_page_url($page) . '">Downstream</a>';
        echo '</ul>';
    }
    
    echo $newline .  '<ol id="lifestream">';
    foreach ($events as $event)
    {
        $timestamp = $event->get_date();
        if ($today == date('m d Y', $timestamp)) $this_day = $lifestream->__('Today');
        else if ($yesterday == date('m d Y', $timestamp)) $this_day = $lifestream->__('Yesterday');
        else $this_day = $lifestream->__(ucfirst(htmlentities(date($lifestream->get_option('day_format'), $timestamp))));
        if ($day != $this_day)
        {
            if ($counter) echo $newline .  '</ol>';      
            echo $newline . "\t" . '<li class="' . date('j F Y', $timestamp) . '">';
            echo $newline . "\t" . '<h2>' . $this_day . '</h2>';
            echo $newline . "\t" . '<ol>';
            $day = $this_day;
            $counter++;
        }
        echo $newline . "\t\t" . '<li class="lifestream-feed-' . $event->feed->get_constant('ID') . '">';
        echo $event->render($options);
        echo '<p class="lifestream-label">' . $event->get_label($options) . '</p>';
        echo '<p class="lifestream-meta">';
        echo '<img class="lifestream-icon" src="' . $event->feed->get_icon_url() . '" /> ';
        echo '<span class="lifestream-hour">';
        echo ($today == date('m d Y', $timestamp)) ? $lifestream->timesince($event->timestamp) : date($lifestream->get_option('hour_format'), $event->timestamp);
        echo '</span> ';
        echo '<span class="lifestream-via">via ' . $event->get_feed_label($options) . '</span>';
        echo '</p>'; // .lifestream-meta
        echo '</li>';
    }
    echo $newline . "\t" . '</ol><!-- /#lifestream -->';
    
    if ($has_paging)
    {
        echo $newline . '<p class="lifestream-paging">';
        if ($has_next_page) echo '<a href="' . $lifestream->get_next_page_url($page) . '">Upstream</a>';
        echo '</ul>';
    }    
}
else
{
	?>
	<p id="lifestream"><?php $lifestream->_e('There are no events to show at this time.'); ?></p>
	<?php
}
?>