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
 * Restaurant helper.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @since       3.2
 */
class RestaurantHelper extends JHelperContent
{
	/**
	 * Configure the Linkbar.
	 *
	 * @param   string  $vName  The name of the active view.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public static function addSubmenu($vName)
	{
		JHtmlSidebar::addEntry(
			JText::_('COM_RESTAURANT_SUBMENU_DISHES'),
			'index.php?option=com_restaurant&view=dishes',
			$vName == 'dishes'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_RESTAURANT_SUBMENU_CATEGORIES'),
			'index.php?option=com_categories&extension=com_restaurant',
			$vName == 'categories'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_RESTAURANT_SUBMENU_FEATURED'),
			'index.php?option=com_restaurant&view=featured',
			$vName == 'featured'
		);

		JHtmlSidebar::addEntry(
			JText::_('COM_RESTAURANT_SUBMENU_DRINKS'),
			'index.php?option=com_restaurant&view=drinks',
			$vName == 'drinks'
		);
	}
}
