<?php get_header(); ?>

<div id="content" class="narrowcolumn" role="main">

	<div class="post">
		<h2><?php $lifestream->_e('Lifestream'); ?></h2>
		<div class="entry">
			<?php if (ls_have_activity()): ?>
				<table class="lifestream">
					<?php while (ls_have_activity()) : ls_the_event(); global $event; ?>
						<tr class="<?php ls_event_class(); ?>">
							<td class="lifestream_icon">
								<img src="<?php ls_event_icon(); ?>" alt="" />
							</td>
							<td class="lifestream_text">
								<div class="lifestream_label"><?php ls_event_label(); ?></div>

								<div class="lifestream_meta">
									&#8212; <abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php ls_event_date(); ?></abbr> <span class="lifestream_via">via <?php ls_event_feed_label() ?></span>
								</div>

								<?php ls_event_content(); ?>
							</td>
						</tr>
					<?php endwhile; ?>
				</table>
			<?php else: ?>
				<p>There are no events to show.</p>
			<?php endif; ?>
			<?php ls_do_credits(); ?>
		</div>
	</div>

	<?php comments_template(); ?>

</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
