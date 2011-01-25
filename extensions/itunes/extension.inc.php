<?php
class Lifestream_iTunesFeed extends Lifestream_Feed
{
	const ID			= 'itunes';
	const NAME			= 'iTunes';
	const URL			= '';
	const AUTHOR		= 'Anonymous, Alan Isherwood';
	const DESCRIPTION	= 'To obtain your iTunes feed URL you must first go to your account in the iTunes Store. Once there, follow the "Enable My iTunes" link at the bottom. Follow the instructions to enable any feeds you wish to use (it\'s easiest just to enable them all).

Once Enabled, you will need to click "Get HTML Code" on one of the feeds. On this page, click "Copy Feed URL", and you should now have the URL for your feed. Lifestream just needs one feed url, it doesn\'t matter which, to process any of the feeds.

<strong>Note:</strong> If HTML code link opened in Firefox, you may need to re-open it in Internet Explorer for the "Copy Feed URL" to work correctly.';
	
	function __toString()
	{
		return 'SF '.$this->get_option('sf_id').' User '.$this->get_option('user_id');
	}
	
	function get_options()
	{		
		return array(
			'url' => array($this->lifestream->__('Feed URL:'), true, '', ''),
			'sf_id' => array($this->lifestream->__('SF ID:'), null, '', ''),
			'user_id' => array($this->lifestream->__('User ID:'), null, '', ''),
			'show_purchases' => array($this->lifestream->__('Show Purchases.'), false, true, true),
			'show_favourites' => array($this->lifestream->__('Show Favourite Artists.'), false, true, true),
			'show_reviews' => array($this->lifestream->__('Show Reviews.'), false, true, true),
		);
	}
	
	function save_options()
	{
		if (preg_match('/\/sf=([0-9]+)\//i', $this->get_option('url'), $match))
		{
			$this->update_option('sf_id', $match[1]);
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}	
		if (preg_match('/\/userid=([0-9]+)\//i', $this->get_option('url'), $match))
		{
			$this->update_option('user_id', $match[1]);
		}
		else
		{
			throw new Lifestream_Error("Invalid feed URL.");
		}
		parent::save_options();
	}

	function get_url()
	{
		$urls = array();
		if ($user_id = $this->get_option('user_id'))
		{
			$sf_id = $this->get_option('sf_id');
			
			if ($this->get_option('show_purchases'))
			{
				$urls[] = array('http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/myrecentpurchases/sf='.$sf_id.'/userid='.$user_id.'/xml?v0=9985', 'purchase');
			}
			if ($this->get_option('show_favourites'))
			{
				$urls[] = array('http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/mymostpurchasedartists/sf='.$sf_id.'/userid='.$user_id.'/xml?v0=9986', 'favourite');
			}
			if ($this->get_option('show_reviews'))
			{
				$urls[] = array('http://ax.itunes.apple.com/WebObjects/MZStoreServices.woa/ws/RSS/myrecentreviews/sf='.$sf_id.'/toprated=true/userid='.$user_id.'/xml?v0=9987', 'review');
			}
		}
		return $urls;
	}
	
	# http://phobos.apple.com/rss
	# <im:contentType term="Music" label="Music"><im:contentType term="Track" label="Track"/></im:contentType>
	# <im:image height="170">http://a1.phobos.apple.com/us/r1000/022/Music/c4/ae/6e/mzi.qpurndic.170x170-75.jpg</im:image>
	function get_label_class($key)
	{
		if ($key == 'purchase') $cls = 'Lifestream_PurchaseItemLabel';
		elseif ($key == 'favourite') $cls = 'Lifestream_LikeLabel';
		elseif ($key == 'review') $cls = 'Lifestream_ReviewItemLabel';
		return $cls;
	}
}
$lifestream->register_feed('Lifestream_iTunesFeed');
?>