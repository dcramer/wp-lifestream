=======================
Miscellaneous Functions
=======================

.. class:: Lifestream

   .. method:: get_rss_feed_url()
   
      :returns: The absolute URL to your Lifestream RSS feed.
      :rtype: string

   .. method:: get_icon_media_url($filename)
   
      :param filename: The icon filename (typically the extensions ID).
      :type filename: string
      :returns: The absolute URL to ``$filename`` contained current icon pack.
      :rtype: string

   .. method:: get_theme_media_url($filename)
   
      :param filename: The media filename.
      :type filename: string
      :returns: The absolute URL to ``$filename`` contained in current theme.
      :rtype: string

   .. method:: get_anchor_html($label, $href, [$attrs=array()])
   
      This method is used to generate proper &lt;A&gt; tags, based on specified settings by the user.
   
      :param label: The link's label.
      :type label: string
      :param href: The link's href.
      :type href: string
      :param attrs: A key/value list of additional attributes.
      :type attrs: array
      :returns: A generated anchor tag for links.
      :rtype: string