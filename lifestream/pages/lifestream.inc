<div id="lifestream">
    <?php
    $day = '';
    if (count($results))
    {
        ?>
        <table class="hcalendar">
        <?php
        foreach ($results as $result)
        {
            $timestamp = $result->timestamp+$offset;
            $this_day = ucfirst(htmlentities(date($day_format, $timestamp)));
            if ($day != $this_day)
            {
                ?>
                <tr>
                    <th colspan="3">
                        <h2 class="lifestream_date"><?php echo $this_day; ?></h2>
                    </th>
                </tr>
                <?php
                $day = $this_day;
            }
            ?>
            <tr class="lifestream_row lifestream_feedid_<?php echo $result->feed; ?>">
                <td class="lifestream_icon">
                    <a href="<?php echo htmlspecialchars($item->link); ?>" title="<?php echo $result->feed; ?>"><img src="<?php echo $lifestream_path . '/images/'. $result->feed . '.png'; ?>" alt="<?php echo $result->feed; ?>" /></a>
                </td>
                <td class="lifestream_hour">
                    <abbr class="dtstart" title="<?php echo date("c", $timestamp); ?>"><?php echo date($hour_format, $timestamp); ?></abbr>
                </td>
                <td class="lifestream_text">
                    <?php echo $result->text; ?>
                </td>
            </tr>
            <?php
        }
        ?>
        </table>
        <?php
    }
    else
    {
        ?>
        <p>There are no events to show at this time.</p>
        <?php
    }
    ?>
</div>