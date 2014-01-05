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
 * Dishes list controller class.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @since       3.2
 */
class RestaurantControllerDishes extends JControllerAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var     string
	 * @since   3.2
	 */
	protected $text_prefix = 'COM_RESTAURANT_DISHES';

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 *
	 * @see     JControllerAdmin
	 * @since   3.2
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);

		// Dishes default form can come from the dishes or featured view.
		// Adjust the redirect view on the value of 'view' in the request.
		if ($this->input->get('view') == 'featured')
		{
			$this->view_list = 'featured';
		}

		$this->registerTask('unfeatured', 'featured');
	}

	/**
	 * Method to toggle the featured setting of a list of dishes.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function featured()
	{
		// Check for request forgeries.
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		// Initialiase variables.
		$user   = JFactory::getUser();
		$ids    = $this->input->get('cid', array(), 'array');
		$values = array('featured' => 1, 'unfeatured' => 0);
		$task   = $this->getTask();
		$value  = JArrayHelper::getValue($values, $task, 0, 'int');

		// Access checks.
		foreach ($ids as $i => $id)
		{
			if (!$user->authorise('core.edit.state', 'com_restaurant.dish.' . (int) $id))
			{
				// Prune items that you can not change.
				unset($ids[$i]);

				JError::raiseNotice(403, JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
			}
		}

		if (empty($ids))
		{
			JError::raiseWarning(500, JText::_('JERROR_NO_ITEMS_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			// Publish the items.
			if (!$model->featured($ids, $value))
			{
				JError::raiseWarning(500, $model->getError());
			}
		}

		$this->setRedirect('index.php?option=com_restaurant&view=dishes');
	}

	/**
	 * Method to get a model object, loading it if required.
	 *
	 * @param   string  $name    The model name. Optional.
	 * @param   string  $prefix  The class prefix. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JModelLegacy
	 *
	 * @since   3.2
	 */
	public function getModel($name = 'Dish', $prefix = 'RestaurantModel', $config = array('ignore_request' => true))
	{
		return parent::getModel($name, $prefix, $config);
	}

	/**
	 * Method to set the potluck dish.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function setPotluck()
	{
		// Check for request forgeries.
		JSession::checkToken() or die(JText::_('JINVALID_TOKEN'));

		$pks = $this->input->post->get('cid', array(), 'array');

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_RESTAURANT_NO_DISH_SELECTED'));
			}

			JArrayHelper::toInteger($pks);

			// Pop off the first element.
			$id    = array_shift($pks);
			$model = $this->getModel();
			$model->setPotluck($id);
			$this->setMessage(JText::_('COM_RESTAURANT_SUCCESS_POTLUCK_SET'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_restaurant&view=dishes');
	}

	/**
	 * Method to unset the potluck dish.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	public function unsetPotluck()
	{
		// Check for request forgeries.
		JSession::checkToken('request') or die(JText::_('JINVALID_TOKEN'));

		$pks = $this->input->get->get('cid', array(), 'array');

		JArrayHelper::toInteger($pks);

		try
		{
			if (empty($pks))
			{
				throw new Exception(JText::_('COM_RESTAURANT_NO_DISH_SELECTED'));
			}

			// Pop off the first element.
			$id    = array_shift($pks);
			$model = $this->getModel();
			$model->unsetPotluck($id);
			$this->setMessage(JText::_('COM_RESTAURANT_SUCCESS_POTLUCK_UNSET'));
		}
		catch (Exception $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		$this->setRedirect('index.php?option=com_restaurant&view=dishes');
	}
}
