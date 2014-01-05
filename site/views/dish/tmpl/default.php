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

// Include the component HTML helpers.
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');

// Create shortcuts to some parameters.
$params  = $this->item->params;
$canEdit = $params->get('access-edit');
$user    = JFactory::getUser();

// Load the tooltip behavior script.
JHtml::_('behavior.caption');
?>
<div class="restaurant dish-item<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading', 1)): ?>
		<div class="page-header">
			<h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			</h1>
		</div>
	<?php endif; ?>

	<div class="page-header">
		<h2>
			<?php echo $this->escape($this->item->title); ?>
			<?php if ($this->item->potluck): ?>
				<i class="icon-star"></i>
			<?php endif; ?>
			<?php if ($price = $this->item->price): ?>
				<div class="pull-right"><?php echo $this->params->get('prefix') . number_format($price, 2, $this->params->get('decimal'), $this->params->get('thousands')); ?></div>
			<?php endif; ?>
		</h2>
	</div>

	<?php if ($this->params->get('show_images_frontend')): ?>
		<figure>
			<?php $image = $this->item->image ? COM_RESTAURANT_BASEURL . '/dishes/' . $this->item->image : 'com_restaurant/no-image.png'; ?>
			<?php echo JHtml::_('image', $image, $this->item->title, array('class' => 'img-rounded'), true); ?>
		</figure>
	<?php endif; ?>

	<dl class="dl-horizontal">
		<?php if ($category_title = $this->item->category_title): ?>
			<dt><?php echo JText::_('JCATEGORY'); ?></dt>
			<dd><a href="<?php echo $this->item->link_cat; ?>"><?php echo $this->escape($category_title); ?></a></dd>
		<?php endif; ?>
		<?php if ($description = $this->item->description): ?>
			<dt><?php echo JText::_('COM_RESTAURANT_HEADING_DESCRIPTION'); ?></dt>
			<dd><?php echo $description; ?></dd>
		<?php endif; ?>
	</dl>
</div>
