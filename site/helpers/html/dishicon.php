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
 * Restaurant Component HTML Helper.
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @since       3.2
 */
abstract class JHtmlDishicon
{
	/**
	 * Method to generate a link to the create item page for the given category.
	 *
	 * @param   object     $category  The category information.
	 * @param   JRegistry  $params    The item parameters.
	 * @param   array      $attribs   Optional attributes for the link.
	 * @param   boolean    $legacy    True to use legacy images, false to use icomoon based graphic.
	 *
	 * @return  string  The HTML markup for the create item link.
	 *
	 * @since   3.2
	 */
	public static function create($category, $params, $attribs = array(), $legacy = false)
	{
		// Load the tooltip bootstrap script.
		JHtml::_('bootstrap.tooltip');

		// Get the full current URI.
		$uri = JUri::getInstance();
		$url = 'index.php?option=com_restaurant&task=dish.add&return=' . base64_encode($uri) . '&r_id=0&catid=' . $category->id;

		if ($params->get('show_icons'))
		{
			if ($legacy)
			{
				$text = JHtml::_('image', 'system/new.png', JText::_('JNEW'), null, true);
			}
			else
			{
				$text = '<span class="icon-plus"></span>&#160;' . JText::_('JNEW') . '&#160;';
			}
		}
		else
		{
			$text = JText::_('JNEW') . '&#160;';
		}

		// Add the button classes to the attribs array.
		if (isset($attribs['class']))
		{
			$attribs['class'] = $attribs['class'] . ' btn btn-primary';
		}
		else
		{
			$attribs['class'] = 'btn btn-primary';
		}

		$button = JHtml::_('link', JRoute::_($url), $text, $attribs);

		$output = '<span class="hasTooltip" title="' . JHtml::tooltipText('COM_RESTAURANT_CREATE_DISH') . '">' . $button . '</span>';

		return $output;
	}

	/**
	 * Method to generate a link to the email item page for the given dish.
	 *
	 * @param   object     $dish     The dish information.
	 * @param   JRegistry  $params   The item parameters.
	 * @param   array      $attribs  Optional attributes for the link.
	 * @param   boolean    $legacy   True to use legacy images, false to use icomoon based graphic.
	 *
	 * @return  string  The HTML markup for the email item link.
	 *
	 * @since   3.2
	 */
	public static function email($dish, $params, $attribs = array(), $legacy = false)
	{
		// Load the frontend helper.
		require_once JPATH_SITE . '/components/com_mailto/helpers/mailto.php';

		// Initialiase variables.
		$uri      = JUri::getInstance();
		$base     = $uri->toString(array('scheme', 'host', 'port'));
		$template = JFactory::getApplication()->getTemplate();
		$link     = $base . JRoute::_(RestaurantHelperRoute::getDishRoute($dish->slug, $dish->catid), false);
		$url      = 'index.php?option=com_mailto&tmpl=component&template=' . $template . '&link=' . MailToHelper::addLink($link);

		$status = 'width=400,height=350,menubar=yes,resizable=yes';

		if ($params->get('show_icons'))
		{
			if ($legacy)
			{
				$text = JHtml::_('image', 'system/emailButton.png', JText::_('JGLOBAL_EMAIL'), null, true);
			}
			else
			{
				$text = '<span class="icon-envelope"></span> ' . JText::_('JGLOBAL_EMAIL');
			}
		}
		else
		{
			$text = JText::_('JGLOBAL_EMAIL');
		}

		$attribs['title']   = JText::_('JGLOBAL_EMAIL');
		$attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";

		$output = JHtml::_('link', JRoute::_($url), $text, $attribs);

		return $output;
	}

	/**
	 * Display an edit icon for the dish.
	 *
	 * This icon will not display in a popup window, nor if the dish is trashed.
	 * Edit access checks must be performed in the calling code.
	 *
	 * @param   object     $dish     The dish information.
	 * @param   JRegistry  $params   The item parameters.
	 * @param   array      $attribs  Optional attributes for the link.
	 * @param   boolean    $legacy   True to use legacy images, false to use icomoon based graphic.
	 *
	 * @return  string  The HTML for the dish edit icon.
	 *
	 * @since   3.2
	 */
	public static function edit($dish, $params, $attribs = array(), $legacy = false)
	{
		// Initialiase variables.
		$user = JFactory::getUser();
		$uri  = JUri::getInstance();

		// Ignore if in a popup window.
		if ($params && $params->get('popup'))
		{
			return;
		}

		// Ignore if the state is negative (trashed).
		if ($dish->state < 0)
		{
			return;
		}

		JHtml::_('bootstrap.tooltip');

		// Show checked_out icon if the dish is checked out by a different user.
		if (property_exists($dish, 'checked_out') && property_exists($dish, 'checked_out_time') && $dish->checked_out > 0 && $dish->checked_out != $user->get('id'))
		{
			$checkoutUser = JFactory::getUser($dish->checked_out);
			$button       = JHtml::_('image', 'system/checked_out.png', null, null, true);
			$date         = JHtml::_('date', $dish->checked_out_time);
			$tooltip      = JText::_('JLIB_HTML_CHECKED_OUT') . ' :: ' . JText::sprintf('COM_RESTAURANT_CHECKED_OUT_BY', $checkoutUser->name) . ' <br /> ' . $date;

			return '<span class="hasTooltip" title="' . JHtml::tooltipText($tooltip . '', 0) . '">' . $button . '</span>';
		}

		$url = 'index.php?option=com_restaurant&task=dish.edit&r_id=' . $dish->id . '&return=' . base64_encode($uri);

		if ($dish->state == 0)
		{
			$overlib = JText::_('JUNPUBLISHED');
		}
		else
		{
			$overlib = JText::_('JPUBLISHED');
		}

		$date    = JHtml::_('date', $dish->created);
		$author  = $dish->created_by_alias ? $dish->created_by_alias : $dish->author;

		$overlib .= '&lt;br /&gt;';
		$overlib .= $date;
		$overlib .= '&lt;br /&gt;';
		$overlib .= JText::sprintf('COM_RESTAURANT_WRITTEN_BY', htmlspecialchars($author, ENT_COMPAT, 'UTF-8'));

		if ($legacy)
		{
			$icon = $dish->state ? 'edit.png' : 'edit_unpublished.png';
			$text = JHtml::_('image', 'system/' . $icon, JText::_('JGLOBAL_EDIT'), null, true);
		}
		else
		{
			$icon = $dish->state ? 'edit' : 'eye-close';
			$text = '<span class="hasTooltip icon-' . $icon . ' tip" title="' . JHtml::tooltipText(JText::_('COM_RESTAURANT_EDIT_ITEM'), $overlib, 0) . '"></span>&#160;' . JText::_('JGLOBAL_EDIT') . '&#160;';
		}

		$output = JHtml::_('link', JRoute::_($url), $text, $attribs);

		return $output;
	}

	/**
	 * Method to generate a popup link to print an dish.
	 *
	 * @param   object     $dish     The dish information.
	 * @param   JRegistry  $params   The item parameters.
	 * @param   array      $attribs  Optional attributes for the link.
	 * @param   boolean    $legacy   True to use legacy images, false to use icomoon based graphic.
	 *
	 * @return  string  The HTML markup for the popup link.
	 *
	 * @since   3.2
	 */
	public static function print_popup($dish, $params, $attribs = array(), $legacy = false)
	{
		// Initialiase variables.
		$app     = JFactory::getApplication();
		$input   = $app->input;
		$request = $input->request;

		$url     = RestaurantHelperRoute::getDishRoute($dish->slug, $dish->catid);
		$url     .= '&tmpl=component&print=1&layout=default&page=' . @ $request->limitstart;

		$status  = 'status=no,toolbar=no,scrollbars=yes,titlebar=no,menubar=no,resizable=yes,width=640,height=480,directories=no,location=no';

		// Checks template image directory for image, if non found default are loaded.
		if ($params->get('show_icons'))
		{
			if ($legacy)
			{
				$text = JHtml::_('image', 'system/printButton.png', JText::_('JGLOBAL_PRINT'), null, true);
			}
			else
			{
				$text = '<span class="icon-print"></span>&#160;' . JText::_('JGLOBAL_PRINT') . '&#160;';
			}
		}
		else
		{
			$text = JText::_('JGLOBAL_PRINT');
		}

		$attribs['title']   = JText::_('JGLOBAL_PRINT');
		$attribs['onclick'] = "window.open(this.href,'win2','" . $status . "'); return false;";
		$attribs['rel']     = 'nofollow';

		return JHtml::_('link', JRoute::_($url), $text, $attribs);
	}

	/**
	 * Method to generate a link to print an dish.
	 *
	 * @param   object     $dish     Not used, @deprecated for 4.0.
	 * @param   JRegistry  $params   The item parameters.
	 * @param   array      $attribs  Not used, @deprecated for 4.0.
	 * @param   boolean    $legacy   True to use legacy images, false to use icomoon based graphic.
	 *
	 * @return  string  The HTML markup for the popup link.
	 *
	 * @since   3.2
	 */
	public static function print_screen($dish, $params, $attribs = array(), $legacy = false)
	{
		// Checks template image directory for image, if none found default are loaded.
		if ($params->get('show_icons'))
		{
			if ($legacy)
			{
				$text = JHtml::_('image', 'system/printButton.png', JText::_('JGLOBAL_PRINT'), null, true);
			}
			else
			{
				$text = '<span class="icon-print"></span>&#160;' . JText::_('JGLOBAL_PRINT') . '&#160;';
			}
		}
		else
		{
			$text = JText::_('JGLOBAL_PRINT');
		}

		return '<a href="#" onclick="window.print();return false;">' . $text . '</a>';
	}
}
