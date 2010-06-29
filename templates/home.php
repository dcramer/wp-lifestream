<?php get_header(); ?>

<div id="content" class="narrowcolumn" role="main">
	<!-- Powered by Lifestream (version: <?php echo LIFESTREAM_VERSION; ?>) -->

	<h2><?php $lifestream->_e('Lifestream'); ?></h2>
	<?php
	/* TODO this should be the page text
	<div class="entry"> 
		<p>This is an example of a WordPress page, you could edit this to put information about yourself or your site so readers know where you are coming from. You can create as many pages like this one or sub-pages as you like and manage all of your content inside of WordPress.</p> 
	</div>
	*/
	?>
	
	
	<div class="navigation">
		<div class="alignleft"><?php ls_prev_page('&laquo; %link') ?></div>
		<div class="alignright"><?php ls_next_page('%link &raquo;') ?></div>
	</div>

	<div class="post">

		<div class="entry">
			<?php if (ls_have_activity()): ?>
				<table class="lifestream">
					<?php while (ls_have_activity()) : ls_the_event(); global $event; ?>
						<tr class="<?php ls_event_class(); ?>">
							<td class="lifestream_icon">
								<a href="<?php ls_event_permalink(); ?>"><img src="<?php ls_event_icon(); ?>" alt="" /></a>
							</td>
							<td class="lifestream_text">
								<div class="lifestream_label"><?php ls_event_label(); ?></div>
						
								<div class="lifestream_meta">
									&#8212; <a href="<?php ls_event_permalink(); ?>"><abbr title="<?php echo date("c", $event->timestamp); ?>" class="lifestream_hour"><?php ls_event_date(); ?></abbr></a><?php if ($event->feed->get_constant('ID') != 'generic' || $event->feed->options['feed_label']) { ?> <span class="lifestream_via">via <?php ls_event_feed_label() ?><?php } ?> | <?php comments_popup_link('No Comments &#187;', '1 Comment &#187;', '% Comments &#187;'); ?></span>
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
	<div class="navigation">
		<div class="alignleft"><?php ls_prev_page('&laquo; %link') ?></div>
		<div class="alignright"><?php ls_next_page('%link &raquo;') ?></div>
	</div>
	
</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
