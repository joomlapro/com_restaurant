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

// Load the helper class.
JLoader::register('RestaurantHelper', JPATH_ADMINISTRATOR . '/components/com_restaurant/helpers/restaurant.php');

/**
 * Item Model for an Dish.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @author      Bruno Batista <bruno@atomtech.com.br>
 * @since       3.2
 */
class RestaurantModelDish extends JModelAdmin
{
	/**
	 * The prefix to use with controller messages.
	 *
	 * @var     string
	 * @since   3.2
	 */
	protected $text_prefix = 'COM_RESTAURANT_DISH';

	/**
	 * The type alias for this content type (for example, 'com_restaurant.dish').
	 *
	 * @var      string
	 * @since    3.2
	 */
	public $typeAlias = 'com_restaurant.dish';

	/**
	 * Batch copy items to a new category or current.
	 *
	 * @param   integer  $value     The new category.
	 * @param   array    $pks       An array of row IDs.
	 * @param   array    $contexts  An array of item contexts.
	 *
	 * @return  mixed  An array of new IDs on success, boolean false on failure.
	 *
	 * @since   3.2
	 */
	protected function batchCopy($value, $pks, $contexts)
	{
		// Initialiase variables.
		$categoryId = (int) $value;
		$i          = 0;

		// Check that the category exists.
		if (!parent::checkCategoryId($categoryId))
		{
			return false;
		}

		// Parent exists so we let's proceed.
		while (!empty($pks))
		{
			// Pop the first ID off the stack.
			$pk = array_shift($pks);

			$this->table->reset();

			// Check that the row actually exists.
			if (!$this->table->load($pk))
			{
				if ($error = $this->table->getError())
				{
					// Fatal error.
					$this->setError($error);

					return false;
				}
				else
				{
					// Not fatal error.
					$this->setError(JText::sprintf('JLIB_APPLICATION_ERROR_BATCH_MOVE_ROW_NOT_FOUND', $pk));

					continue;
				}
			}

			// Alter the title & alias.
			$data = $this->generateNewTitle($categoryId, $this->table->alias, $this->table->title);
			$this->table->title = $data['0'];
			$this->table->alias = $data['1'];

			// Reset the ID because we are making a copy.
			$this->table->id = 0;

			// New category ID.
			$this->table->catid = $categoryId;

			// Get the featured state.
			$featured = $this->table->featured;

			// Check the row.
			if (!$this->table->check())
			{
				$this->setError($table->getError());

				return false;
			}

			parent::createTagsHelper($this->tagsObserver, $this->type, $pk, $this->typeAlias, $this->table);

			// Store the row.
			if (!$this->table->store())
			{
				$this->setError($table->getError());

				return false;
			}

			// Get the new item ID.
			$newId = $this->table->get('id');

			// Add the new ID to the array.
			$newIds[$i] = $newId;
			$i++;

			// Check if the dish was featured and update the #__restaurant_dishes_frontpage table.
			if ($featured == 1)
			{
				$db = $this->getDbo();
				$query = $db->getQuery(true)
					->insert($db->quoteName('#__restaurant_dishes_frontpage'))
					->values($newId . ', 0');
				$db->setQuery($query);
				$db->execute();
			}
		}

		// Clean the cache.
		$this->cleanCache();

		return $newIds;
	}

	/**
	 * Method to test whether a record can be deleted.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to delete the record. Defaults to the permission set in the component.
	 *
	 * @since   3.2
	 */
	protected function canDelete($record)
	{
		if (!empty($record->id))
		{
			if ($record->state != -2)
			{
				return;
			}

			// Get the current user object.
			$user = JFactory::getUser();

			return $user->authorise('core.delete', 'com_restaurant.dish.' . (int) $record->id);
		}
	}

	/**
	 * Method to test whether a record can have its state edited.
	 *
	 * @param   object  $record  A record object.
	 *
	 * @return  boolean  True if allowed to change the state of the record. Defaults to the permission set in the component.
	 *
	 * @since   3.2
	 */
	protected function canEditState($record)
	{
		// Get the current user object.
		$user = JFactory::getUser();

		// Check for existing dish.
		if (!empty($record->id))
		{
			return $user->authorise('core.edit.state', 'com_restaurant.dish.' . (int) $record->id);
		}
		// New dish, so check against the category.
		elseif (!empty($record->catid))
		{
			return $user->authorise('core.edit.state', 'com_restaurant.category.' . (int) $record->catid);
		}
		// Default to component settings if neither dish nor category known.
		else
		{
			return parent::canEditState('com_restaurant');
		}
	}

	/**
	 * Prepare and sanitise the table data prior to saving.
	 *
	 * @param   JTable  $table  A JTable object.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function prepareTable($table)
	{
		// Set the publish date to now.
		$db = $this->getDbo();

		if ($table->state == 1 && (int) $table->publish_up == 0)
		{
			$table->publish_up = JFactory::getDate()->toSql();
		}

		if ($table->state == 1 && intval($table->publish_down) == 0)
		{
			$table->publish_down = $db->getNullDate();
		}

		// Increment the dishes version number.
		$table->version++;

		// Reorder the dishes within the category so the new dish is first.
		if (empty($table->id))
		{
			$table->reorder('catid = ' . (int) $table->catid . ' AND state >= 0');
		}

		$table->price = floatval(str_replace(',', '.', str_replace('.', '', $table->price)));
	}

	/**
	 * Returns a Table object, always creating it.
	 *
	 * @param   type    $type    The table type to instantiate.
	 * @param   string  $prefix  A prefix for the table class name. Optional.
	 * @param   array   $config  Configuration array for model. Optional.
	 *
	 * @return  JTable    A database object.
	 *
	 * @since   3.2
	 */
	public function getTable($type = 'Dish', $prefix = 'RestaurantTable', $config = array())
	{
		return JTable::getInstance($type, $prefix, $config);
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   integer  $pk  The id of the primary key.
	 *
	 * @return  mixed  Object on success, false on failure.
	 *
	 * @since   3.2
	 */
	public function getItem($pk = null)
	{
		if ($item = parent::getItem($pk))
		{
			// Convert the metadata field to an array.
			$registry = new JRegistry;
			$registry->loadString($item->metadata);
			$item->metadata = $registry->toArray();

			if (!empty($item->id))
			{
				$item->tags = new JHelperTags;
				$item->tags->getTagIds($item->id, 'com_restaurant.dish');
			}

			$item->price = number_format($item->price, 2, ',', '.');
		}

		// Load associated dishes items.
		$app = JFactory::getApplication();
		$assoc = JLanguageAssociations::isEnabled();

		if ($assoc)
		{
			$item->associations = array();

			if ($item->id != null)
			{
				$associations = JLanguageAssociations::getAssociations('com_restaurant', '#__restaurant_dishes', 'com_restaurant.item', $item->id);

				foreach ($associations as $tag => $association)
				{
					$item->associations[$tag] = $association->id;
				}
			}
		}

		return $item;
	}

	/**
	 * Method to get the record form.
	 *
	 * @param   array    $data      Data for the form.
	 * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
	 *
	 * @return  mixed  A JForm object on success, false on failure.
	 *
	 * @since   3.2
	 */
	public function getForm($data = array(), $loadData = true)
	{
		// Get the form.
		$form = $this->loadForm('com_restaurant.dish', 'dish', array('control' => 'jform', 'load_data' => $loadData));

		if (empty($form))
		{
			return false;
		}

		$jinput = JFactory::getApplication()->input;

		// The front end calls this model and uses r_id to avoid id clashes so we need to check for that first.
		if ($jinput->get('r_id'))
		{
			$id = $jinput->get('r_id', 0);
		}
		// The back end uses id so we use that the rest of the time and set it to 0 by default.
		else
		{
			$id = $jinput->get('id', 0);
		}

		// Determine correct permissions to check.
		if ($this->getState('dish.id'))
		{
			$id = $this->getState('dish.id');

			// Existing record. Can only edit in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit');

			// Existing record. Can only edit own dishes in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.edit.own');
		}
		else
		{
			// New record. Can only create in selected categories.
			$form->setFieldAttribute('catid', 'action', 'core.create');
		}

		// Get the current user object.
		$user = JFactory::getUser();

		// Check for existing dish.
		// Modify the form based on Edit State access controls.
		if ($id != 0 && (!$user->authorise('core.edit.state', 'com_restaurant.dish.' . (int) $id))
			|| ($id == 0 && !$user->authorise('core.edit.state', 'com_restaurant')))
		{
			// Disable fields for display.
			$form->setFieldAttribute('state', 'disabled', 'true');
			$form->setFieldAttribute('ordering', 'disabled', 'true');
			$form->setFieldAttribute('publish_up', 'disabled', 'true');
			$form->setFieldAttribute('publish_down', 'disabled', 'true');
			$form->setFieldAttribute('featured', 'disabled', 'true');

			// Disable fields while saving.
			// The controller has already verified this is an dish you can edit.
			$form->setFieldAttribute('state', 'filter', 'unset');
			$form->setFieldAttribute('ordering', 'filter', 'unset');
			$form->setFieldAttribute('publish_up', 'filter', 'unset');
			$form->setFieldAttribute('publish_down', 'filter', 'unset');
			$form->setFieldAttribute('featured', 'filter', 'unset');
		}

		// Prevent messing with dish language and category when editing existing dish with associations.
		$app = JFactory::getApplication();
		$assoc = JLanguageAssociations::isEnabled();

		if ($app->isSite() && $assoc && $this->getState('dish.id'))
		{
			$form->setFieldAttribute('language', 'readonly', 'true');
			$form->setFieldAttribute('catid', 'readonly', 'true');
			$form->setFieldAttribute('language', 'filter', 'unset');
			$form->setFieldAttribute('catid', 'filter', 'unset');
		}

		return $form;
	}

	/**
	 * Method to get the data that should be injected in the form.
	 *
	 * @return  mixed  The data for the form.
	 *
	 * @since   3.2
	 */
	protected function loadFormData()
	{
		// Check the session for previously entered form data.
		$app  = JFactory::getApplication();
		$data = $app->getUserState('com_restaurant.edit.dish.data', array());

		if (empty($data))
		{
			$data = $this->getItem();

			// Prime some default values.
			if ($this->getState('dish.id') == 0)
			{
				$data->set('catid', $app->input->getInt('catid', $app->getUserState('com_restaurant.dishes.filter.category_id')));
			}
		}

		$this->preprocessData('com_restaurant.dish', $data);

		return $data;
	}

	/**
	 * Method to save the form data.
	 *
	 * @param   array  $data  The form data.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function save($data)
	{
		// Get the application.
		$app = JFactory::getApplication();

		// Alter the title for save as copy.
		if ($app->input->get('task') == 'save2copy')
		{
			list($title, $alias) = $this->generateNewTitle($data['catid'], $data['alias'], $data['title']);

			$data['title'] = $title;
			$data['alias'] = $alias;
			$data['state'] = 0;
		}

		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		// Get some data from the request.
		$file = $app->input->files->get('jform', '', 'array');

		if ($file['image']['size'])
		{
			$filepath = JPath::clean(COM_RESTAURANT_BASE . '/dishes/');

			if (!JFolder::exists($filepath))
			{
				JFolder::create($filepath);
			}

			if (!JFolder::exists($filepath . '/thumbnails/'))
			{
				JFolder::create($filepath . '/thumbnails/');
			}

			if (JFile::exists($filepath . $data['image_old']))
			{
				JFile::delete($filepath . $data['image_old']);
				JFile::delete($filepath . '/thumbnails/' . $data['image_old']);
			}

			$extension = JFile::getExt($file['image']['name']);

			$data['image'] = uniqid() . '.' . $extension;
			$file['image']['name'] = $data['image'];

			$object_file = new JObject($file['image']);
			$object_file->filepath = $filepath . strtolower($file['image']['name']);

			JFile::upload($object_file->tmp_name, $object_file->filepath);

			$JImage = new JImage($object_file->filepath);

			try
			{
				$image = $JImage->cropResize('700', '300', false);
				$image->toFile($object_file->filepath);

				$thumbnail = $JImage->cropResize('100', '100', false);
				$thumbnail->toFile($filepath . '/thumbnails/' . strtolower($file['image']['name']));
			}
			catch (Exception $e)
			{
				$app->enqueueMessage($e->getMessage(), 'error');
			}
		}

		if (parent::save($data))
		{
			if (isset($data['featured']))
			{
				$this->featured($this->getState($this->getName() . '.id'), $data['featured']);
			}

			$assoc = JLanguageAssociations::isEnabled();

			if ($assoc)
			{
				$id   = (int) $this->getState($this->getName() . '.id');
				$item = $this->getItem($id);

				// Adding self to the association.
				$associations = $data['associations'];

				foreach ($associations as $tag => $id)
				{
					if (empty($id))
					{
						unset($associations[$tag]);
					}
				}

				// Detecting all item dishes.
				$all_language = $item->language == '*';

				if ($all_language && !empty($associations))
				{
					JError::raiseNotice(403, JText::_('COM_RESTAURANT_ERROR_ALL_LANGUAGE_ASSOCIATED'));
				}

				$associations[$item->language] = $item->id;

				// Deleting old association for these items.
				$db = JFactory::getDbo();
				$query = $db->getQuery(true)
					->delete('#__associations')
					->where('context=' . $db->quote('com_restaurant.item'))
					->where('id IN (' . implode(',', $associations) . ')');
				$db->setQuery($query);
				$db->execute();

				if ($error = $db->getErrorMsg())
				{
					$this->setError($error);

					return false;
				}

				if (!$all_language && count($associations))
				{
					// Adding new association for these items.
					$key = md5(json_encode($associations));
					$query->clear()
						->insert('#__associations');

					foreach ($associations as $id)
					{
						$query->values($id . ',' . $db->quote('com_restaurant.item') . ',' . $db->quote($key));
					}

					$db->setQuery($query);
					$db->execute();

					if ($error = $db->getErrorMsg())
					{
						$this->setError($error);

						return false;
					}
				}
			}

			return true;
		}

		return false;
	}

	/**
	 * Method to toggle the featured setting of dishes.
	 *
	 * @param   array    $pks    The ids of the items to toggle.
	 * @param   integer  $value  The value to toggle to.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   3.2
	 */
	public function featured($pks, $value = 0)
	{
		// Sanitize the ids.
		$pks = (array) $pks;

		JArrayHelper::toInteger($pks);

		if (empty($pks))
		{
			$this->setError(JText::_('COM_RESTAURANT_NO_ITEM_SELECTED'));

			return false;
		}

		$table = $this->getTable('Featured', 'RestaurantTable');

		try
		{
			// Initialiase variables.
			$db = $this->getDbo();

			// Create the base update statement.
			$query = $db->getQuery(true)
				->update($db->quoteName('#__restaurant_dishes'))
				->set('featured = ' . (int) $value)
				->where('id IN (' . implode(',', $pks) . ')');

			// Set the query and execute the update.
			$db->setQuery($query);
			$db->execute();

			if ((int) $value == 0)
			{
				// Adjust the mapping table.
				// Clear the existing features settings.
				$query = $db->getQuery(true)
					->delete($db->quoteName('#__restaurant_dishes_frontpage'))
					->where('dish_id IN (' . implode(',', $pks) . ')');

				// Set the query and execute the update.
				$db->setQuery($query);
				$db->execute();
			}
			else
			{
				// First, we find out which of our new featured dishes are already featured.
				$query = $db->getQuery(true)
					->select('f.dish_id')
					->from('#__restaurant_dishes_frontpage AS f')
					->where('dish_id IN (' . implode(',', $pks) . ')');

				// Set the query and execute the update.
				$db->setQuery($query);

				$old_featured = $db->loadColumn();

				// We diff the arrays to get a list of the dishes that are newly featured.
				$new_featured = array_diff($pks, $old_featured);

				// Featuring.
				$tuples = array();

				foreach ($new_featured as $pk)
				{
					$tuples[] = $pk . ', 0';
				}

				if (count($tuples))
				{
					// Initialiase variables.
					$db = $this->getDbo();
					$columns = array('dish_id', 'ordering');

					// Create the base insert statement.
					$query = $db->getQuery(true)
						->insert($db->quoteName('#__restaurant_dishes_frontpage'))
						->columns($db->quoteName($columns))
						->values($tuples);

					// Set the query and execute the insert.
					$db->setQuery($query);
					$db->execute();
				}
			}
		}
		catch (Exception $e)
		{
			$this->setError($e->getMessage());

			return false;
		}

		$table->reorder();

		// Clear the cache.
		$this->cleanCache();

		return true;
	}

	/**
	 * A protected method to get a set of ordering conditions.
	 *
	 * @param   object  $table  A record object.
	 *
	 * @return  array  An array of conditions to add to add to ordering queries.
	 *
	 * @since   3.2
	 */
	protected function getReorderConditions($table)
	{
		$condition = array();
		$condition[] = 'catid = ' . (int) $table->catid;

		return $condition;
	}

	/**
	 * Auto-populate the model state.
	 *
	 * Note. Calling getState in this method will result in recursion.
	 *
	 * @param   JForm   $form   A JForm object.
	 * @param   mixed   $data   The data expected for the form.
	 * @param   string  $group  The name of the plugin group to import.
	 *
	 * @return  void
	 *
	 * @since   $TM_VERSION
	 */
	protected function preprocessForm(JForm $form, $data, $group = 'content')
	{
		// Association dishes items.
		$app   = JFactory::getApplication();
		$assoc = JLanguageAssociations::isEnabled();

		if ($assoc)
		{
			$languages = JLanguageHelper::getLanguages('lang_code');

			// Force to array (perhaps move to $this->loadFormData()).
			$data = (array) $data;

			$addform  = new SimpleXMLElement('<form />');
			$fields   = $addform->addChild('fields');
			$fields->addAttribute('name', 'associations');
			$fieldset = $fields->addChild('fieldset');
			$fieldset->addAttribute('name', 'item_associations');
			$fieldset->addAttribute('description', 'COM_RESTAURANT_ITEM_ASSOCIATIONS_FIELDSET_DESC');
			$add = false;

			foreach ($languages as $tag => $language)
			{
				if (empty($data['language']) || $tag != $data['language'])
				{
					$add = true;
					$field = $fieldset->addChild('field');
					$field->addAttribute('name', $tag);
					$field->addAttribute('type', 'modal_article');
					$field->addAttribute('language', $tag);
					$field->addAttribute('label', $language->title);
					$field->addAttribute('translate_label', 'false');
					$field->addAttribute('edit', 'true');
					$field->addAttribute('clear', 'true');
				}
			}

			if ($add)
			{
				$form->load($addform, false);
			}
		}

		parent::preprocessForm($form, $data, $group);
	}

	/**
	 * Method to set a dish as potluck.
	 *
	 * @param   integer  $id  The primary key ID for the dish.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws  RuntimeException
	 * @since   3.2
	 */
	public function setPotluck($id = 0)
	{
		// Initialiase variables.
		$user = JFactory::getUser();
		$db   = $this->getDbo();

		// Access checks.
		if (!$user->authorise('core.edit.state', 'com_restaurant'))
		{
			throw new RuntimeException(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
		}

		$dish = JTable::getInstance('Dish', 'RestaurantTable');

		if (!$dish->load((int) $id))
		{
			throw new RuntimeException(JText::_('COM_RESTAURANT_ERROR_DISH_NOT_FOUND'));
		}

		// Detect disabled row.
		if ($dish->state == 0 || $dish->state == -2)
		{
			throw new RuntimeException(JText::_('COM_RESTAURANT_ERROR_SAVE_DISABLED_DISH'));
		}

		// Detect archived row.
		if ($dish->state == 2)
		{
			throw new RuntimeException(JText::_('COM_RESTAURANT_ERROR_SAVE_ARCHIVED_DISH'));
		}

		// Reset the potluck fields.
		$db->setQuery(
			'UPDATE #__restaurant_dishes'
			. ' SET `potluck` = \'0\''
			. ' WHERE `potluck` = \'1\''
		);

		$db->execute();

		// Set the new potluck dish.
		$db->setQuery(
			'UPDATE #__restaurant_dishes'
			. ' SET `potluck` = \'1\''
			. ' WHERE id = ' . (int) $id
		);

		$db->execute();

		// Clean the cache.
		$this->cleanCache();

		return true;
	}

	/**
	 * Method to unset a dish as potluck.
	 *
	 * @param   integer  $id  The primary key ID for the dish.
	 *
	 * @return  boolean  True if successful.
	 *
	 * @throws	Exception
	 * @since   3.2
	 */
	public function unsetPotluck($id = 0)
	{
		// Initialiase variables.
		$user = JFactory::getUser();
		$db   = $this->getDbo();

		// Access checks.
		if (!$user->authorise('core.edit.state', 'com_restaurant'))
		{
			throw new Exception(JText::_('JLIB_APPLICATION_ERROR_EDITSTATE_NOT_PERMITTED'));
		}

		// Set the new potluck dish.
		$db->setQuery(
			'UPDATE #__restaurant_dishes' .
			' SET potluck = \'0\'' .
			' WHERE id = ' . (int) $id
		);

		$db->execute();

		// Clean the cache.
		$this->cleanCache();

		return true;
	}
}
