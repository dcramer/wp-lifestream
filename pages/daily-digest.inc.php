<table class="lifestream">
<?php
foreach ($events as $result)
{
    $timestamp = $result->get_date();
    ?>
    <tr class="lifestream_feedid_<?php echo $result->feed->id; ?> lifestream_feed_<?php echo $result->feed->get_constant('ID'); ?>">
           <td class="lifestream_icon">
               <a href="<?php echo htmlspecialchars($result->get_url()); ?>"><img src="<?php echo $result->feed->get_icon_url(); ?>" alt="<?php echo $result->feed->get_constant('ID'); ?> (feed #<?php echo $result->feed->id; ?>)" /></a>
           </td>
           <td class="lifestream_text">
               <?php echo $result->render($_); ?>
           </td>
    </tr>
    <?php
}
?>
</table>