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

?>
<div class="restaurant dish-list<?php echo $this->pageclass_sfx; ?>">
	<?php if ($this->params->get('show_page_heading')): ?>
		<div class="page-header">
			<h1>
				<?php echo $this->escape($this->params->get('page_heading')); ?>
			</h1>
		</div>
	<?php endif; ?>

	<table class="table table-striped table-hover">
		<thead>
			<tr>
				<th width="5%" class="nowrap hidden-phone">
					<?php echo JText::_('COM_RESTAURANT_HEADING_IMAGE'); ?>
				</th>
				<th class="title">
					<?php echo JText::_('COM_RESTAURANT_HEADING_TITLE'); ?>
				</th>
				<th width="5%" class="nowrap hidden-phone">
					<?php echo JText::_('COM_RESTAURANT_HEADING_POTLUCK'); ?>
				</th>
				<th width="5%" class="nowrap hidden-phone">
					<?php echo JText::_('COM_RESTAURANT_HEADING_PRICE'); ?>
				</th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ($this->items as $i => $item): ?>
				<tr>
					<?php if ($this->params->get('show_images_frontend')): ?>
						<td class="small nowrap hidden-phone">
							<figure>
								<?php $image = $item->image ? COM_RESTAURANT_BASEURL . '/dishes/thumbnails/' . $item->image : 'com_restaurant/no-image-thumb.png'; ?>
								<?php echo JHtml::_('image', $image, $item->title, array('class' => 'img-rounded'), true); ?>
							</figure>
						</td>
					<?php endif; ?>
					<td class="title">
						<a href="<?php echo $item->link; ?>"><?php echo $this->escape($item->title); ?></a>
					</td>
					<td class="small nowrap hidden-phone">
						<?php if ($item->potluck): ?>
							<i class="icon-star"></i>
						<?php endif ?>
					</td>
					<td class="small nowrap hidden-phone">
						<?php echo $this->params->get('prefix') . number_format($item->price, 2, $this->params->get('decimal'), $this->params->get('thousands')); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<?php if ($this->params->get('show_pagination', 1)): ?>
		<div class="pagination">
			<?php if ($this->params->def('show_pagination_results', 1)): ?>
				<p class="counter">
					<?php echo $this->pagination->getPagesCounter(); ?>
				</p>
			<?php endif; ?>
			<?php echo $this->pagination->getPagesLinks(); ?>
		</div>
	<?php endif; ?>
</div>
