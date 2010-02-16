<style type="text/css">
dl {
	position: relative;
}
dt { font-weight: bold; width: 100px; position: absolute; left: 0; }
dd { padding-left: 100px; }
</style>
<h2><?php $lifestream->_e('Lifestream Maintenance'); ?></h2>

<form method="post" action="">
	<fieldset>
		<h3><?php $lifestream->_e('Restore Defaults'); ?></h3>
		<p><?php $lifestream->_e('If you are having issues with your installation you can easily restore the default settings for Lifestream.'); ?></p>
		<p><?php $lifestream->_e('If resetting to the defaults does not fix your problems, you may also completely reinstall the plugin\'s database. This will completely clear all of your feeds and events.'); ?></p>
		<p class="submit">
			<input type="submit" class="button-primary" name="restore" onclick="return confirm('Are you sure you wish to restore the settings?');" value="<?php $lifestream->_e('Restore default settings');?>"/> <input type="submit" class="button-secondary" name="restoredb" onclick="return confirm('Are you sure you wish to restore the database?');" value="<?php $lifestream->_e('Restore default database');?>"/>
		</p>
	</fieldset>
	
	<fieldset>
		<h3><?php $lifestream->_e('Other Tasks'); ?></h3>
		<p><?php $lifestream->_e('If you had notice events missing, or issues with permalinks, you want may to try creating any missing post events.'); ?></p>
		<p><?php $lifestream->_e('You may also use the cleanup posts option if you wish to remove any posts which may still exist and are unused.'); ?></p>
		<?php $page = $lifestream->get_page(); ?>
		<p><?php $lifestream->_e('Lifestream is currently set to appear on <strong>%s</strong> (ID: %s). If this page does not exist, or is incorrect, you may recreate it using the option below.', $page->post_title, $page->ID); ?></p>
		<p class="submit">
			<input type="submit" class="button-primary" name="fixposts" value="<?php $lifestream->_e('Fix missing posts');?>"/> <input type="submit" class="button-secondary" name="cleanupposts" value="<?php $lifestream->_e('Cleanup unused posts');?>"/> <input type="submit" class="button-secondary" name="recreatepage" value="<?php $lifestream->_e('Recreate page template');?>"/>
		</p>
	
	<fieldset>
		<h3><?php $lifestream->_e('Cron Tasks'); ?></h3>
		<p><?php $lifestream->_e('Cron tasks are regularly scheduled events, and are used to update lifestream and make automated posts.'); ?></p>
		<table class="widefat">
			<thead>
				<tr>
					<th scope="col">Task</th>
					<th scope="col" style="width: 250px;">Next Attempt</th>
				</tr>
			</thead>
			<tbody>
				<?php
				$jobs = _get_cron_array();
				foreach ($jobs as $ts=>$cron_data)
				{
					foreach ($cron_data as $name=>$data)
					{
						if (lifestream_str_startswith($name, 'lifestream'))
						{
							$data = array_values($data);
							?>
							<tr>
								<td><strong><?php echo htmlspecialchars($name); ?></strong><br /><small><?php echo htmlspecialchars($lifestream->get_cron_task_description($name)); ?></small></td>
								<td><?php echo date('r', $ts + LIFESTREAM_DATE_OFFSET); ?><br /><small>Every <?php echo $lifestream->duration($data[0]['interval']); ?></td>
							</tr>
							<?php
						}
					}
				}
				?>
			</tbody>
		</table>
		<p class="submit">
			<input type="submit" class="button-primary" name="resetcron" value="<?php $lifestream->_e('Reset cron timers');?>"/>
		</p>
	</fieldset>
	
	<fieldset>
		<h3><?php $lifestream->_e('Extension Directories'); ?></h3>
		<p><?php $lifestream->_e('Lifestream will search in several locations for themes, extensions, and icon packs. Below are the default, and custom directories which you have set.'); ?></p>
		<dl>
			<dt>Themes:</dt>
			<dd><ol>
				<?php foreach ($lifestream->get_theme_paths() as $dir) { ?>
					<li><?php echo htmlspecialchars($dir); ?></li>
				<?php } ?>
			</ol></dd>
			<dt>Extensions:</dt>
			<dd><ol>
				<?php foreach ($lifestream->get_extension_paths() as $dir) { ?>
					<li><?php echo htmlspecialchars($dir); ?></li>
				<?php } ?>
			</ol></dd>
			<dt>Icon Packs:</dt>
			<dd><ol>
				<?php foreach ($lifestream->get_icon_paths() as $dir) { ?>
					<li><?php echo htmlspecialchars($dir); ?></li>
				<?php } ?>
			</ol></dd>
		</dl>
	</fieldset>
	<br/>
</form>