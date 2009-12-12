=======
Options
=======

WordPress includes several functions to easily access options for your plugin. However, we handle them a bit differently within Lifestream.

--------------
Global Options
--------------

.. class:: Lifestream

  .. method:: get_option($key, [$default=null])
   
     :param $key: The option's key name.
     :type $key: string
     :returns: The value of the option, or ``$default``

  .. method:: add_option($key, $value)
   
     Adds an option, if it does not already exist.
   
     :param $key: The option's key name.
     :type $key: string
     :param $value: The option's value.

  .. method:: update_option($key, $value)

     Updates the value of an option

     :param $key: The option's key name.
     :type $key: string
     :param $value: The option's value.

  .. method:: delete_option($key)
   
     Unsets an option.
   
     :param $key: The option's key name.
     :type $key: string

-----------------
Extension Options
-----------------

Extension options are handled differently than our global level options. There are no accessors built-in for them at the time of writing. Instead, they are available via the instance variable ``$options``.

.. class:: Lifestream_Extension

  .. method:: get_options()

     Returns a list of the available options for this extension.

     The return value should be an array of arrays, in the following format::

     	array(
     	    'option_name' => array(
     	        $this->lifestream->__('option_label'),
     	        boolean is_required,
     	        mixed default_value,
     	        mixed choices
     	    )
     	);

     :returns: Array of options
     :rtype: array
