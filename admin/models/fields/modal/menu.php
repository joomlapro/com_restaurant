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
defined('JPATH_BASE') or die;

/**
 * Supports a modal menu picker.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @since       3.2
 */
class JFormFieldModal_Menu extends JFormField
{
	/**
	 * The form field type.
	 *
	 * @var     string
	 * @since   3.2
	 */
	protected $type = 'Modal_Menu';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   3.2
	 */
	protected function getInput()
	{
		// Initialiase variables.
		$allowEdit  = ((string) $this->element['edit'] == 'true') ? true : false;
		$allowClear = ((string) $this->element['clear'] != 'false') ? true : false;

		// Load language.
		JFactory::getLanguage()->load('com_restaurant', JPATH_ADMINISTRATOR);

		// Load the modal behavior script.
		JHtml::_('behavior.modal', 'a.modal');

		// Build the script.
		$script = array();

		// Select button script.
		$script[] = 'function jSelectMenu_' . $this->id . '(id, title, catid, object) {';
		$script[] = '	document.getElementById("' . $this->id . '_id").value = id;';
		$script[] = '	document.getElementById("' . $this->id . '_name").value = title;';

		if ($allowEdit)
		{
			$script[] = '	jQuery("#' . $this->id . '_edit").removeClass("hidden");';
		}

		if ($allowClear)
		{
			$script[] = '	jQuery("#' . $this->id . '_clear").removeClass("hidden");';
		}

		$script[] = '	SqueezeBox.close();';
		$script[] = '}';

		// Clear button script.
		static $scriptClear;

		if ($allowClear && !$scriptClear)
		{
			$scriptClear = true;

			$script[] = 'function jClearMenu(id) {';
			$script[] = '	document.getElementById(id + "_id").value = "";';
			$script[] = '	document.getElementById(id + "_name").value = "' . htmlspecialchars(JText::_('COM_RESTAURANT_SELECT_AN_MENU', true), ENT_COMPAT, 'UTF-8') . '";';
			$script[] = '	jQuery("#"+id + "_clear").addClass("hidden");';
			$script[] = '	if (document.getElementById(id + "_edit")) {';
			$script[] = '		jQuery("#"+id + "_edit").addClass("hidden");';
			$script[] = '	}';
			$script[] = '	return false;';
			$script[] = '}';
		}

		// Add the script to the document head.
		JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));

		// Setup variables for display.
		$html = array();
		$link = 'index.php?option=com_restaurant&amp;view=menus&amp;layout=modal&amp;tmpl=component&amp;function=jSelectMenu_' . $this->id;

		if (isset($this->element['language']))
		{
			$link .= '&amp;forcedLanguage=' . $this->element['language'];
		}

		// Initialiase variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Create the base select statement.
		$query->select('title')
			->from($db->quoteName('#__restaurant_menus'))
			->where($db->quoteName('id') . ' = ' . $db->quote((int) $this->value));

		// Set the query and load the result.
		$db->setQuery($query);

		try
		{
			$title = $db->loadResult();
		}
		catch (RuntimeException $e)
		{
			JError::raiseWarning(500, $e->getMessage());
		}

		if (empty($title))
		{
			$title = JText::_('COM_RESTAURANT_SELECT_AN_MENU');
		}

		$title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

		// The active menu id field.
		if (0 == (int) $this->value)
		{
			$value = '';
		}
		else
		{
			$value = (int) $this->value;
		}

		// The current menu display field.
		$html[] = '<span class="input-append">';
		$html[] = '<input type="text" class="input-medium" id="' . $this->id . '_name" value="' . $title . '" disabled="disabled" size="35" />';
		$html[] = '<a class="modal btn hasTooltip" title="' . JHtml::tooltipText('COM_RESTAURANT_CHANGE_MENU') . '" href="' . $link . '&amp;' . JSession::getFormToken() . '=1" rel="{handler: \'iframe\', size: {x: 800, y: 450}}"><i class="icon-file"></i> ' . JText::_('JSELECT') . '</a>';

		// Edit menu button.
		if ($allowEdit)
		{
			$html[] = '<a class="btn hasTooltip' . ($value ? '' : ' hidden') . '" href="index.php?option=com_restaurant&layout=modal&tmpl=component&task=menu.edit&id=' . $value . '" target="_blank" title="' . JHtml::tooltipText('COM_RESTAURANT_EDIT_MENU') . '" ><span class="icon-edit"></span> ' . JText::_('JACTION_EDIT') . '</a>';
		}

		// Clear menu button.
		if ($allowClear)
		{
			$html[] = '<button id="' . $this->id . '_clear" class="btn' . ($value ? '' : ' hidden') . '" onclick="return jClearMenu(\'' . $this->id . '\')"><span class="icon-remove"></span> ' . JText::_('JCLEAR') . '</button>';
		}

		$html[] = '</span>';

		// Set class='required' for client side validation.
		$class = '';

		if ($this->required)
		{
			$class = ' class="required modal-value"';
		}

		$html[] = '<input type="hidden" id="' . $this->id . '_id"' . $class . ' name="' . $this->name . '" value="' . $value . '" />';

		return implode("\n", $html);
	}
}
