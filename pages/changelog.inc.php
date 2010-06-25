<h2><?php $lifestream->_e('Lifestream Change Log'); ?></h2>

<p><?php $lifestream->_e('You are using <strong>wp-lifestream %s</strong>.', LIFESTREAM_VERSION); ?></p>

<p><?php $lifestream->_e('This is the current change log, pulled directly from the latest version. It allows you to see past, and future changes.'); ?></p>

<pre>
<?php echo htmlspecialchars($lifestream->file_get_contents('http://github.com/dcramer/wp-lifestream/raw/master/CHANGES')); ?>
</pre>