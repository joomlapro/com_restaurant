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
 * Featured Table class.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @since       3.2
 */
class RestaurantTableFeatured extends JTable
{
	/**
	 * Constructor
	 *
	 * @param   JDatabase  &$db  Driver A database connector object.
	 *
	 * @since   3.2
	 */
	public function __construct(& $db)
	{
		parent::__construct('#__restaurant_dishes_frontpage', 'dish_id', $db);
	}
}
