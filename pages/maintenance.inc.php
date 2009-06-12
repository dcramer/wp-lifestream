<h2><?php $lifestream->_e('Lifestream Maintenance'); ?></h2>

<form method="post" action="">
	<fieldset>
		<h3><?php $lifestream->_e('Restore Defaults'); ?></h3>
		<p><?php $lifestream->_e('If you are having issues with your installation you can easily restore the default settings for Lifestream.'); ?></p>
		<p class="submit">
			<input type="submit" name="restore" value="<?php $lifestream->_e('Restore default settings');?>"/>
		</p>
	</fieldset>
	
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
						if (str_startswith($name, 'lifestream'))
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
			<input type="submit" name="resetcron" value="<?php $lifestream->_e('Reset cron timers');?>"/>
		</p>
	</fieldset>
</form>