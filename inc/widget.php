<?php
// TODO: Refactor into class

// Pattern for multi-widget (allows multiple instances such as the text widget).

// Displays widget on blag
// $widget_args: number
//	number: which of the several widgets of this type do we mean
function lifestream_widget($args, $widget_args=1)
{
	global $lifestream;
	
	extract($args, EXTR_SKIP);
	if (is_numeric($widget_args))
		$widget_args = array('number' => $widget_args);
	$widget_args = wp_parse_args($widget_args, array('number' => -1));
	extract($widget_args, EXTR_SKIP);

	// Data should be stored as array:  array( number => data for that instance of the widget, ... )
	$options = (array)$lifestream->get_option('widget');
	if (!isset($options[$number]))
		return;

	$options = $options[$number];

	echo $before_widget;

	echo $before_title . ($options['title'] ? apply_filters('widget_title', $options['title']) : $lifestream->__('Lifestream')) . $after_title;

	$args = array(
		'limit'=>$options['amount'],
		'feed_ids'=>@$options['feeds'],
		'hide_metadata'=>@$options['hide_metadata'],
		'break_groups'=>@$options['break_groups'] ? true : false,
		'event_total_max'=>-1,
		'date_interval'=>-1,
	);

	if ($lifestream->is_buddypress && is_home())
	{
		$args['show_owners'] = 1;
	}
	lifestream_sidebar_widget($args);
	// Do stuff for this widget, drawing data from $options[$number]

	echo $after_widget;
}

// Displays form for a particular instance of the widget.  Also updates the data after a POST submit
// $widget_args: number
//	number: which of the several widgets of this type do we mean
function lifestream_widget_control($widget_args=1)
{
	global $wp_registered_widgets, $wpdb, $lifestream;
	// Whether or not we have already updated the data after a POST submit
	static $updated = false;

	if (is_numeric($widget_args))
		$widget_args = array('number' => $widget_args);
	$widget_args = wp_parse_args($widget_args, array('number' => -1));
	extract($widget_args, EXTR_SKIP);

	// Data should be stored as array:  array( number => data for that instance of the widget, ... )
	$options = (array)$lifestream->get_option('widget');

	// We need to update the data
	if (!$updated && !empty($_POST['sidebar']))
	{
		// Tells us what sidebar to put the data in
		$sidebar = (string)$_POST['sidebar'];

		$sidebars_widgets = wp_get_sidebars_widgets();
		if (isset($sidebars_widgets[$sidebar]))
			$this_sidebar =& $sidebars_widgets[$sidebar];
		else
			$this_sidebar = array();

		foreach ($this_sidebar as $_widget_id)
		{
			// Remove all widgets of this type from the sidebar.  We'll add the new data in a second.  This makes sure we don't get any duplicate data
			// since widget ids aren't necessarily persistent across multiple updates

			if ('lifestream_widget' == $wp_registered_widgets[$_widget_id]['callback'] && isset($wp_registered_widgets[$_widget_id]['params'][0]['number']))
			{
				$widget_number = $wp_registered_widgets[$_widget_id]['params'][0]['number'];
				if (!in_array("lifestream-$widget_number", $_POST['widget-id'])) // the widget has been removed. "many-$widget_number" is "{id_base}-{widget_number}
					unset($options[$widget_number]);
			}
		}

		foreach ((array)$_POST['lifestream'] as $widget_number => $widget_options)
		{
			// user clicked cancel
			if (!isset($widget_options['submit']) && isset($options[$widget_number]))
				continue;
				
			$options[$widget_number] = $widget_options;
		}
		
		$lifestream->update_option('widget', $options);
		
		// So that we don't go through this more than once
		$updated = true;
	}


	// Here we echo out the form
	if (-1 == $number)
	{
		$current_options = array(
			'amount' => 10,
			'title' => 'Lifestream',
			'break_groups' => false,
			'feeds' => array(),
			'hide_metadata' => false,
		);
		$number = '%i%';
	}
	else
	{
		$current_options = (array)$options[$number];
		foreach ($current_options as $key=>$value)
		{
			$current_options[$key] = attribute_escape($value);
		}
	}
	
	$results =& $wpdb->get_results("SELECT * FROM `".$wpdb->prefix."lifestream_feeds` ORDER BY `id`");
	$feeds = array();
	foreach ($results as &$result)
	{
		$feeds[] = Lifestream_Feed::construct_from_query_result($lifestream, $result);
	}

	// The form has inputs with names like widget-many[$number][something] so that all data for that instance of
	// the widget are stored in one $_POST variable: $_POST['widget-many'][$number]
?>
	<script type="text/javascript">
	// TODO: move this out of the recursive wrapper
	function lifestreamClearSelection(obj)  {
		for (var i=0; i<obj.options.length; i++) {
			obj.options[i].selected = false;
		}
	}
	</script>
	<p>
		<label>
			<?php $lifestream->_e('Title:'); ?>
			<input class="widefat" name="lifestream[<?php echo $number; ?>][title]" type="text" value="<?php echo $current_options['title']; ?>" />
		</label>
	</p>
	<p>
		<label>
			<?php $lifestream->_e('Number of events to show:'); ?>
			<input style="width: 35px; text-align: center;" name="lifestream[<?php echo $number; ?>][amount]" type="text" value="<?php echo $current_options['amount']; ?>" />
		</label>
	</p>
	<p>
		<label>
			<input type="checkbox" name="lifestream[<?php echo $number; ?>][break_groups]" value="1"<?php if (@$current_options['break_groups']) echo ' checked = "checked"'; ?>/>
			<?php $lifestream->_e('Break up grouped events.'); ?>
		</label><br />
	</p>
	<p>
		<label>
			<input type="checkbox" name="lifestream[<?php echo $number; ?>][hide_metadata]" value="1"<?php if (@$current_options['hide_metadata']) echo ' checked = "checked"'; ?>/>
			<?php $lifestream->_e('Hide meta data, such as the time.'); ?>
		</label><br />
	</p>
	<p>
		<?php $lifestream->_e('Feeds to show (optional):'); ?> <small>(<a href="javascript:void(0);" onclick="lifestreamClearSelection(this.parentNode.parentNode.getElementsByTagName('select')[0]);"><?php $lifestream->_e('Clear Selection'); ?></a>)</small><br />
		<select multiple="multiple" style="width: 92%; height: 80px;" name="lifestream[<?php echo $number; ?>][feeds][]">
			<?php foreach ($feeds as &$feed) { ?>
				<option value="<?php echo $feed->id; ?>"<?php if (in_array((string)$feed->id, @(array)$current_options['feeds'])) echo ' selected="selected"'; ?>><?php echo $feed->get_public_name(); ?> (<?php echo $feed->get_feed_display(); ?>)</option>
			<?php } ?>
		</select>
	</p>
	<input type="hidden" name="lifestream[<?php echo $number; ?>][submit]" value="1" />
<?php
}

// Registers each instance of our widget on startup
function lifestream_widget_register()
{
	global $lifestream;
		
	if (!($options = $lifestream->get_option('widget')))
		$options = array();

	$widget_ops = array('classname' => 'widget_lifestream', 'description' => $lifestream->__('Displays your activity from your lifestream'));
	// 'width' => 250, 'height' => 350,
	$control_ops = array('id_base' => 'lifestream', 'width' => 400);
	$name = $lifestream->__('Lifestream');

	$registered = false;
	foreach (array_keys($options) as $o)
	{
		// $id should look like {$id_base}-{$o}
		$id = "lifestream-$o"; // Never never never translate an id
		$registered = true;
		wp_register_sidebar_widget($id, $name, 'lifestream_widget', $widget_ops, array('number' => $o));
		wp_register_widget_control($id, $name, 'lifestream_widget_control', $control_ops, array('number' => $o));
	}

	// If there are none, we register the widget's existance with a generic template
	if (!$registered)
	{
		wp_register_sidebar_widget('lifestream-1', $name, 'lifestream_widget', $widget_ops, array('number' => -1));
		wp_register_widget_control( 'lifestream-1', $name, 'lifestream_widget_control', $control_ops, array('number' => -1));
	}
}

// This is important
add_action('widgets_init', 'lifestream_widget_register');
?>