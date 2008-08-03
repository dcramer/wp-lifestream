<?php
$day = false;
if (count($results))
{
    $today = date('m d Y');
    $yesterday = date('m d Y', time()-86400);
    ?>
    <ul class="lifestream">
    <?php
    foreach ($results as $result)
    {
        $timestamp = $result->timestamp+$offset;
        if ($today == date('m d Y', $timestamp)) $this_day = 'Today';
        else if ($yesterday == date('m d Y', $timestamp)) $this_day = 'Yesterday';
        else $this_day = ucfirst(htmlentities(date($day_format, $timestamp)));
        if ($day != $this_day)
        {
            if ($day)
            {
                echo '</ul></li>';
            }
            ?>
            <li>
                <h2 class="lifestream_date"><?php echo $this_day; ?></h2>
                <ul>
            <?php
            $day = $this_day;
        }
        ?>
        <li class="lifestream_feedid_<?php echo $result->feed; ?>" style="background-image: url('<?php echo $lifestream_path . '/images/'. $result->feed; ?>.png');">
            <span class="lifestream_text"><?php echo $result->text; ?></span>
            <abbr class="lifestream_hour" title="<?php echo date("c", $timestamp); ?>"><?php echo date($hour_format, $timestamp); ?>.</abbr>
        </li>
        <?php
    }
    if ($day) {
        echo '</ul></li>';
    } ?>
    </ul>
    <?php
}
else
{
    ?>
    <p class="lifestream">There are no events to show at this time.</p>
    <?php
}
?>