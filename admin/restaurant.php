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

// Load the tabstate behavior script.
JHtml::_('behavior.tabstate');

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_restaurant'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// Register dependent classes.
JLoader::register('RestaurantHelper', __DIR__ . '/helpers/restaurant.php');
JLoader::register('DishesHelper', __DIR__ . '/helpers/dishes.php');
JLoader::register('DrinksHelper', __DIR__ . '/helpers/drinks.php');

// Load the parameters.
$params = JComponentHelper::getParams('com_restaurant');

define('COM_RESTAURANT_BASE',    JPATH_ROOT . '/' . $params->get('image_path', 'images'));
define('COM_RESTAURANT_BASEURL', JUri::root() . $params->get('image_path', 'images'));

// Execute the task.
$controller = JControllerLegacy::getInstance('Restaurant');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
