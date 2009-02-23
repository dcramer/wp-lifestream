<table class="lifestream">
<?php
foreach ($events as $result)
{
    $timestamp = $result->get_date();
    ?>
    <tr class="lifestream_feedid_<?php echo $result->feed->get_constant('ID'); ?>">
        <td class="lifestream_icon">
            <img src="<?php echo $lifestream_path . '/images/' . $result->feed->get_constant('ID'); ?>.png" alt="<?php echo $result->feed->get_constant('ID'); ?> (feed #<?php echo $result->feed->id; ?>)" />
        </td>
        <td class="lifestream_hour">
            <abbr title="<?php echo date("c", $timestamp); ?>"><?php echo date($hour_format, $timestamp); ?></abbr>
        </td>
        <td class="lifestream_text">
            <?php echo $result->render($_); ?>
        </td>
    </tr>
    <?php
}
?>
</table>