======
Events
======

.. class:: Lifestream

  .. method:: get_events([$options=array()])
   
     Gets recent events from the lifestream::

     	$events = $lifestream->get_events(array(
     	    'feed_types'=>array('twitter', 'delicious'),
     	    'break_groups'=>true
     	));

     :note: Parameters should be passed as key/value pairs in a single array.

     :param $limit: The maximum number of events to return.
     :type $limit: integer
     :param $offset: The starting offset of events (useful for pagination).
     :type $offset: integer
     :param $feed_ids: A list of feed IDs to filter on.
     :type $feed_ids: array
     :param $user_ids: A list of user IDs to filter on.
     :type $user_ids: array
     :param $feed_types: A list of feed extension IDs to filter on.
     :type $feed_types: array
     :param $date_interval: Interval for date cutoff (see MySQL interval clause for values).
     :type $date_interval: string
     :param $start_date: The earliest date of events.
     :type $start_date: integer
     :param $end_date: The latest date of events.
     :type $end_date: integer
     :param $event_total_min: The minimum number of events in the group.
     :type $event_total_min: integer
     :param $event_total_max: The maximum number of events in a group.
     :type $event_total_max: integer
     :param $break_groups: Break groups into single event instances.
     :type $break_groups: boolean
     :returns: An array of :class:`Event` or :class:`EventGroup` instances.
     :rtype: array

  .. method:: get_single_event($feed_type)
   
     A shortcut for :meth:`get_events`. Returns the latest event from `$feed_type`::
      
     	$tweet = $lifestream->get_single_event('twitter');
      
     :param $feed_type: The extension ID. e.g. `twitter`
     :type $feed_type: string
     :returns: Event instance.
     :rtype: :class:`Lifestream_Event`