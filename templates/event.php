<?php

get_header(); ?>

	<div id="content" class="narrowcolumn" role="main">

		<div class="post">
		<h2><?php $lifestream->_e('Lifestream'); ?></h2>
			<div class="entry">
				<?php this_event(); ?>
			</div>
		</div>
	
	<?php comments_template(); ?>
	
	</div>

<?php get_sidebar(); ?>

<?php get_footer(); ?>
