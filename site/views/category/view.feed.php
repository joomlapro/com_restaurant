<?php
/**
 * @package     Restaurant
 * @subpackage  com_restaurant
 *
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @copyright   Copyright (C) 2014 AtomTech, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * HTML Category View class for the Restaurant component.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @since       3.2
 */
class RestaurantViewCategory extends JViewCategoryfeed
{
	/**
	 * The name of the view to link individual items to.
	 *
	 * @var    string
	 * @since  3.2
	 */
	protected $viewName = 'dish';

	/**
	 * Method to reconcile non standard names from components to usage in this class.
	 * Typically overriden in the component feed view class.
	 *
	 * @param   object  $item  The item for a feed, an element of the $items array.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function reconcileNames($item)
	{
		// Get description, author and date.
		$app    = JFactory::getApplication();
		$params = $app->getParams();

		// Add readmore link to description.
		if (!$item->params->get('feed_summary', 0) && $item->params->get('feed_show_readmore', 0) && $item->description)
		{
			$item->description .= '<p class="feed-readmore"><a target="_blank" href ="' . $item->link . '">' . JText::_('COM_RESTAURANT_FEED_READMORE') . '</a></p>';
		}

		$item->author = $item->created_by_alias ? $item->created_by_alias : $item->author;
	}
}
