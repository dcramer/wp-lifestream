<h2><?php _e('LifeStream Change Log', 'lifestream'); ?></h2>

<p>This is the current change log, pulled directly from the latest version. It allows you to see past, and future changes.</p>

<pre>
<?php echo htmlspecialchars(lifestream_file_get_contents('http://svn.wp-plugins.org/lifestream/trunk/CHANGES')); ?>
</pre>