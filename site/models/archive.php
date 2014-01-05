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

// Load dependent classes.
require_once __DIR__ . '/dishes.php';

/**
 * Restaurant Component Archive Model.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @since       3.2
 */
class RestaurantModelArchive extends RestaurantModelDishes
{
	/**
	 * Model context string.
	 *
	 * @var     string
	 */
	public $_context = 'com_restaurant.archive';

	/**
	 * Method to auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   string  $ordering   An optional ordering field.
	 * @param   string  $direction  An optional direction (asc|desc).
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function populateState($ordering = null, $direction = null)
	{
		parent::populateState();

		// Get the application.
		$app    = JFactory::getApplication();

		// Add archive properties.
		$params = $this->state->params;

		// Filter on archived dishes.
		$this->setState('filter.state', 2);

		// Filter on month, year.
		$this->setState('filter.month', $app->input->getInt('month'));
		$this->setState('filter.year', $app->input->getInt('year'));

		// Optional filter text.
		$this->setState('list.filter', $app->input->getString('filter-search'));

		// Get list limit.
		$itemid = $app->input->get('Itemid', 0, 'int');
		$limit  = $app->getUserStateFromRequest('com_restaurant.archive.list' . $itemid . '.limit', 'limit', $params->get('display_num'), 'uint');
		$this->setState('list.limit', $limit);
	}

	/**
	 * Method to build an SQL query to load the list data.
	 *
	 * @return  string  An SQL query.
	 *
	 * @since   3.2
	 */
	protected function getListQuery()
	{
		// Set the archive ordering.
		$params = $this->state->params;
		$dishOrderby   = $params->get('orderby_sec', 'rdate');
		$dishOrderDate = $params->get('order_date');

		// No category ordering.
		$categoryOrderby = '';
		$secondary       = RestaurantHelperQuery::orderbySecondary($dishOrderby, $dishOrderDate) . ', ';
		$primary         = RestaurantHelperQuery::orderbyPrimary($categoryOrderby);

		$orderby = $primary . ' ' . $secondary . ' a.created DESC';
		$this->setState('list.ordering', $orderby);
		$this->setState('list.direction', '');

		// Create a new query object.
		$query = parent::getListQuery();

		// Add routing for archive
		// sqlsrv changes.
		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('a.alias', '!=', '0');
		$case_when .= ' THEN ';
		$r_id      = $query->castAsChar('a.id');
		$case_when .= $query->concatenate(array($r_id, 'a.alias'), ':');
		$case_when .= ' ELSE ';
		$case_when .= $r_id . ' END as slug';

		$query->select($case_when);

		$case_when = ' CASE WHEN ';
		$case_when .= $query->charLength('c.alias', '!=', '0');
		$case_when .= ' THEN ';
		$c_id      = $query->castAsChar('c.id');
		$case_when .= $query->concatenate(array($c_id, 'c.alias'), ':');
		$case_when .= ' ELSE ';
		$case_when .= $c_id . ' END as catslug';

		$query->select($case_when);

		// Filter on month, year.
		// First, get the date field.
		$queryDate = RestaurantHelperQuery::getQueryDate($dishOrderDate);

		if ($month = $this->getState('filter.month'))
		{
			$query->where('MONTH(' . $queryDate . ') = ' . $month);
		}

		if ($year = $this->getState('filter.year'))
		{
			$query->where('YEAR(' . $queryDate . ') = ' . $year);
		}

		return $query;
	}

	/**
	 * Method to get the archived dish list.
	 *
	 * @access  public
	 * @return  array
	 *
	 * @since   3.2
	 */
	public function getData()
	{
		// Get the application.
		$app = JFactory::getApplication();

		// Lets load the content if it does not already exist.
		if (empty($this->_data))
		{
			// Get the page/component configuration.
			$params = $app->getParams();

			// Get the pagination request variables.
			$limit       = $app->input->get('limit', $params->get('display_num', 20), 'uint');
			$limitstart  = $app->input->get('limitstart', 0, 'uint');
			$query       = $this->_buildQuery();
			$this->_data = $this->_getList($query, $limitstart, $limit);
		}

		return $this->_data;
	}

	/**
	 * JModelLegacy override to add alternating value for $odd.
	 *
	 * @param   string   $query       The query.
	 * @param   integer  $limitstart  Offset.
	 * @param   integer  $limit       The number of records.
	 *
	 * @return  array
	 *
	 * @since   3.2
	 */
	protected function _getList($query, $limitstart = 0, $limit = 0)
	{
		// Initialiase variables.
		$result = parent::_getList($query, $limitstart, $limit);
		$odd    = 1;

		foreach ($result as $k => $row)
		{
			$result[$k]->odd = $odd;
			$odd             = 1 - $odd;
		}

		return $result;
	}
}
