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
 * HTML Dish View class for the Restaurant component
 *
 * @package     Restaurant
 * @subpackage  com_restaurant
 * @since       3.2
 */
class RestaurantViewDish extends JViewLegacy
{
	/**
	 * A list of user note objects.
	 *
	 * @var    array
	 * @since  3.2
	 */
	protected $item;

	/**
	 * The model state.
	 *
	 * @var    JUser
	 * @since  3.2
	 */
	protected $params;

	/**
	 * The model state.
	 *
	 * @var    JUser
	 * @since  3.2
	 */
	protected $print;

	/**
	 * The model state.
	 *
	 * @var    JObject
	 * @since  3.2
	 */
	protected $state;

	/**
	 * The model state.
	 *
	 * @var    JUser
	 * @since  3.2
	 */
	protected $user;

	/**
	 * Method to display the view.
	 *
	 * @param   string  $tpl  The template file to include.
	 *
	 * @return  mixed  False on error, null otherwise.
	 *
	 * @since   3.2
	 */
	public function display($tpl = null)
	{
		// Initialiase variables.
		$app         = JFactory::getApplication();
		$user        = JFactory::getUser();
		$dispatcher  = JEventDispatcher::getInstance();

		// Get some data from the models.
		$this->item  = $this->get('Item');
		$this->print = $app->input->getBool('print');
		$this->state = $this->get('State');
		$this->user  = $user;

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseWarning(500, implode("\n", $errors));

			return false;
		}

		// Create a shortcut for $item.
		$item = $this->item;
		$item->tagLayout = new JLayoutFile('joomla.content.tags');

		// Add router helpers.
		$item->slug        = $item->alias ? ($item->id . ':' . $item->alias) : $item->id;
		$item->catslug     = $item->category_alias ? ($item->catid . ':' . $item->category_alias) : $item->catid;
		$item->parent_slug = $item->parent_alias ? ($item->parent_id . ':' . $item->parent_alias) : $item->parent_id;
		$item->link        = JRoute::_(RestaurantHelperRoute::getDishRoute($item->slug, $item->catslug));
		$item->link_cat    = JRoute::_(RestaurantHelperRoute::getCategoryRoute($item->catslug));

		// No link for ROOT category
		if ($item->parent_alias == 'root')
		{
			$item->parent_slug = null;
		}

		// TODO: Change based on shownoauth.
		$item->readmore_link = JRoute::_(RestaurantHelperRoute::getDishRoute($item->slug, $item->catslug));

		// Merge dish params. If this is single-dish view, menu params override dish params.
		// Otherwise, dish params override menu item params.
		$this->params = $this->state->get('params');
		$active = $app->getMenu()->getActive();
		$temp   = clone ($this->params);

		// Check to see which parameters should take priority.
		if ($active)
		{
			$currentLink = $active->link;

			// If the current view is the active item and an dish view for this dish, then the menu item params take priority.
			if (strpos($currentLink, 'view=dish') && (strpos($currentLink, '&id=' . (string) $item->id)))
			{
				// Load layout from active query (in case it is an alternative menu item).
				if (isset($active->query['layout']))
				{
					$this->setLayout($active->query['layout']);
				}
				// Check for alternative layout of dish.
				elseif ($layout = $item->params->get('dish_layout'))
				{
					$this->setLayout($layout);
				}

				// $item->params are the dish params, $temp are the menu item params.
				// Merge so that the menu item params take priority.
				$item->params->merge($temp);
			}
			else
			{
				// Current view is not a single dish, so the dish params take priority here.
				// Merge the menu item params with the dish params so that the dish params take priority.
				$temp->merge($item->params);
				$item->params = $temp;

				// Check for alternative layouts (since we are not in a single-dish menu item).
				// Single-dish menu item layout takes priority over alt layout for an dish.
				if ($layout = $item->params->get('dish_layout'))
				{
					$this->setLayout($layout);
				}
			}
		}
		else
		{
			// Merge so that dish params take priority.
			$temp->merge($item->params);
			$item->params = $temp;

			// Check for alternative layouts (since we are not in a single-dish menu item)
			// Single-dish menu item layout takes priority over alt layout for an dish.
			if ($layout = $item->params->get('dish_layout'))
			{
				$this->setLayout($layout);
			}
		}

		$offset = $this->state->get('list.offset');

		// Check the view access to the dish (the model has already computed the values).
		if ($item->params->get('access-view') != true && (($item->params->get('show_noauth') != true &&  $user->get('guest') )))
		{
			JError::raiseWarning(403, JText::_('JERROR_ALERTNOAUTHOR'));

			return;
		}

		$item->text = $item->description;

		$item->tags = new JHelperTags;
		$item->tags->getItemTags('com_restaurant.dish', $this->item->id);

		// Process the content plugins.
		JPluginHelper::importPlugin('content');
		$dispatcher->trigger('onContentPrepare', array ('com_restaurant.dish', &$item, &$this->params, $offset));

		$item->event = new stdClass;
		$results = $dispatcher->trigger('onContentAfterTitle', array('com_restaurant.dish', &$item, &$this->params, $offset));
		$item->event->afterDisplayTitle = trim(implode("\n", $results));

		$results = $dispatcher->trigger('onContentBeforeDisplay', array('com_restaurant.dish', &$item, &$this->params, $offset));
		$item->event->beforeDisplayContent = trim(implode("\n", $results));

		$results = $dispatcher->trigger('onContentAfterDisplay', array('com_restaurant.dish', &$item, &$this->params, $offset));
		$item->event->afterDisplayContent = trim(implode("\n", $results));

		// Increment the hit counter of the dish.
		if (!$this->params->get('intro_only') && $offset == 0)
		{
			$model = $this->getModel();
			$model->hit();
		}

		// Escape strings for HTML output.
		$this->pageclass_sfx = htmlspecialchars($this->item->params->get('pageclass_sfx'));

		$this->_prepareDocument();

		parent::display($tpl);
	}

	/**
	 * Prepares the document.
	 *
	 * @return  void
	 *
	 * @since   3.2
	 */
	protected function _prepareDocument()
	{
		// Initialiase variables.
		$app     = JFactory::getApplication();
		$menus   = $app->getMenu();
		$pathway = $app->getPathway();
		$title   = null;

		// Because the application sets a default page title,
		// we need to get it from the menu item itself.
		$menu = $menus->getActive();

		if ($menu)
		{
			$this->params->def('page_heading', $this->params->get('page_title', $menu->title));
		}
		else
		{
			$this->params->def('page_heading', JText::_('COM_RESTAURANT_DEFAULT_PAGE_TITLE'));
		}

		$title = $this->params->get('page_title', '');

		$id = (int) @$menu->query['id'];

		// If the menu item does not concern this dish.
		if ($menu && ($menu->query['option'] != 'com_restaurant' || $menu->query['view'] != 'dish' || $id != $this->item->id))
		{
			// If this is not a single dish menu item, set the page title to the dish title.
			if ($this->item->title)
			{
				$title = $this->item->title;
			}

			$path = array(array('title' => $this->item->title, 'link' => ''));
			$category = JCategories::getInstance('Restaurant')->get($this->item->catid);

			while ($category && ($menu->query['option'] != 'com_restaurant' || $menu->query['view'] == 'dish' || $id != $category->id) && $category->id > 1)
			{
				$path[] = array('title' => $category->title, 'link' => RestaurantHelperRoute::getCategoryRoute($category->id));
				$category = $category->getParent();
			}

			$path = array_reverse($path);

			foreach ($path as $item)
			{
				$pathway->addItem($item['title'], $item['link']);
			}
		}

		// Check for empty title and add site name if param is set.
		if (empty($title))
		{
			$title = $app->getCfg('sitename');
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 1)
		{
			$title = JText::sprintf('JPAGETITLE', $app->getCfg('sitename'), $title);
		}
		elseif ($app->getCfg('sitename_pagetitles', 0) == 2)
		{
			$title = JText::sprintf('JPAGETITLE', $title, $app->getCfg('sitename'));
		}

		if (empty($title))
		{
			$title = $this->item->title;
		}

		$this->document->setTitle($title);

		// Configure the document meta-description.
		if ($this->item->metadesc)
		{
			$this->document->setDescription($this->item->metadesc);
		}
		elseif (!$this->item->metadesc && $this->params->get('menu-meta_description'))
		{
			$this->document->setDescription($this->params->get('menu-meta_description'));
		}

		// Configure the document meta-keywords.
		if ($this->item->metakey)
		{
			$this->document->setMetadata('keywords', $this->item->metakey);
		}
		elseif (!$this->item->metakey && $this->params->get('menu-meta_keywords'))
		{
			$this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
		}

		// Configure the document robots.
		if ($this->params->get('robots'))
		{
			$this->document->setMetadata('robots', $this->params->get('robots'));
		}

		if ($app->getCfg('MetaAuthor') == '1')
		{
			$this->document->setMetaData('author', $this->item->author);
		}

		$mdata = $this->item->metadata->toArray();

		foreach ($mdata as $k => $v)
		{
			if ($v)
			{
				$this->document->setMetadata($k, $v);
			}
		}

		// If there is a pagebreak heading or title, add it to the page title.
		if (!empty($this->item->page_title))
		{
			$this->item->title = $this->item->title . ' - ' . $this->item->page_title;
			$this->document->setTitle($this->item->page_title . ' - ' . JText::sprintf('PLG_CONTENT_PAGEBREAK_PAGE_NUM', $this->state->get('list.offset') + 1));
		}

		if ($this->print)
		{
			$this->document->setMetaData('robots', 'noindex, nofollow');
		}
	}
}
