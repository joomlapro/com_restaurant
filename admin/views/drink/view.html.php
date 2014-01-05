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
 * View to edit a drink.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @since       3.2
 */
class RestaurantViewDrink extends JViewLegacy
{
	/**
	 * The form to use for the view.
	 *
	 * @var     JForm
	 */
	protected $form;

	/**
	 * The item to edit.
	 *
	 * @var     JObject
	 */
	protected $item;

	/**
	 * The model state.
	 *
	 * @var     JObject
	 */
	protected $state;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  A template file to load. [optional]
	 *
	 * @return  mixed  A string if successful, otherwise a JError object.
	 *
	 * @since   3.2
	 */
	public function display($tpl = null)
	{
		try
		{
			// Initialiase variables.
			$this->form  = $this->get('Form');
			$this->item  = $this->get('Item');
			$this->state = $this->get('State');
			$this->canDo = DrinksHelper::getActions(0, 'com_restaurant');
		}
		catch (Exception $e)
		{
			JErrorPage::render($e);

			return false;
		}

		if ($this->getLayout() == 'modal')
		{
			$this->form->setFieldAttribute('language', 'readonly', 'true');
		}

		$this->addToolbar();

		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function addToolbar()
	{
		JFactory::getApplication()->input->set('hidemainmenu', true);

		// Initialiase variables.
		$user       = JFactory::getUser();
		$userId     = $user->get('id');
		$isNew      = ($this->item->id == 0);
		$checkedOut = !($this->item->checked_out == 0 || $this->item->checked_out == $userId);

		// Since we do not track these assets at the item level.
		$canDo      = $this->canDo;

		JToolbarHelper::title(JText::_('COM_RESTAURANT_PAGE_' . ($checkedOut ? 'VIEW_DRINK' : ($isNew ? 'ADD_DRINK' : 'EDIT_DRINK'))), 'pencil-2 drink-add');

		// Built the actions for new and existing records.
		// For new records, check the create permission.
		if ($isNew)
		{
			JToolbarHelper::apply('drink.apply');
			JToolbarHelper::save('drink.save');
			JToolbarHelper::save2new('drink.save2new');
			JToolbarHelper::cancel('drink.cancel');
		}
		else
		{
			// Can not save the record if it's checked out.
			if (!$checkedOut)
			{
				// Since it's an existing record, check the edit permission, or fall back to edit own if the owner.
				if ($canDo->get('core.edit') || ($canDo->get('core.edit.own') && $this->item->created_by == $userId))
				{
					JToolbarHelper::apply('drink.apply');
					JToolbarHelper::save('drink.save');

					// We can save this record, but check the create permission to see if we can return to make a new one.
					if ($canDo->get('core.create'))
					{
						JToolbarHelper::save2new('drink.save2new');
					}
				}
			}

			// If checked out, we can still save.
			if ($canDo->get('core.create'))
			{
				JToolbarHelper::save2copy('drink.save2copy');
			}

			if ($this->state->params->get('save_history', 1) && $user->authorise('core.edit'))
			{
				JToolbarHelper::versions('com_restaurant.drink', $this->item->id);
			}

			JToolbarHelper::cancel('drink.cancel', 'JTOOLBAR_CLOSE');
		}

		JToolBarHelper::help('drink', $com = true);
	}
}
