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

// Load the behavior script.
JHtml::_('behavior.formvalidation');
JHtml::_('behavior.keepalive');
JHtml::_('formbehavior.chosen', 'select');

// Initialiase variables.
$this->hiddenFieldsets    = array();
$this->hiddenFieldsets[0] = 'basic-limited';
$this->configFieldsets    = array();
$this->configFieldsets[0] = 'editorConfig';

// Create shortcut to parameters.
$params = $this->state->get('params');

// This checks if the config options have ever been saved. If they haven't they will fall back to the original settings.
$params = json_decode($params);

if (!isset($params->show_publishing_options))
{
	$params->show_publishing_options = '1';
	$params->show_dish_options = '1';
	$params->show_images_backend = '0';
	$params->show_images_frontend = '0';
}

// Check if the dish uses configuration settings besides global. If so, use them.
if (isset($this->item->params['show_publishing_options']) && $this->item->params['show_publishing_options'] != '')
{
	$params->show_publishing_options = $this->item->params['show_publishing_options'];
}

if (isset($this->item->params['show_dish_options']) && $this->item->params['show_dish_options'] != '')
{
	$params->show_dish_options = $this->item->params['show_dish_options'];
}

if (isset($this->item->params['show_images_frontend']) && $this->item->params['show_images_frontend'] != '')
{
	$params->show_images_frontend = $this->item->params['show_images_frontend'];
}

if (isset($this->item->params['show_images_backend']) && $this->item->params['show_images_backend'] != '')
{
	$params->show_images_backend = $this->item->params['show_images_backend'];
}
?>
<script type="text/javascript">
	Joomla.submitbutton = function(task) {
		if (task == 'dish.cancel' || document.formvalidator.isValid(document.id('item-form'))) {
			<?php echo $this->form->getField('description')->save(); ?>

			if (window.opener && (task == 'dish.save' || task == 'dish.cancel')) {
				window.opener.document.closeEditWindow = self;
				window.opener.setTimeout('window.document.closeEditWindow.close()', 1000);
			}

			Joomla.submitform(task, document.getElementById('item-form'));
		}
	}
</script>
<div class="container-popup">
	<div class="pull-right">
		<button class="btn btn-primary" type="button" onclick="Joomla.submitbutton('dish.apply');"><?php echo JText::_('JTOOLBAR_APPLY'); ?></button>
		<button class="btn btn-primary" type="button" onclick="Joomla.submitbutton('dish.save');"><?php echo JText::_('JTOOLBAR_SAVE'); ?></button>
		<button class="btn" type="button" onclick="Joomla.submitbutton('dish.cancel');"><?php echo JText::_('JCANCEL'); ?></button>
	</div>
	<div class="clearfix"></div>
	<hr class="hr-condensed" />
	<form action="<?php echo JRoute::_('index.php?option=com_restaurant&layout=modal&tmpl=component&id=' . (int) $this->item->id); ?>" method="post" name="adminForm" id="item-form" class="form-validate">
		<?php echo JLayoutHelper::render('joomla.edit.title_alias', $this); ?>
		<div class="form-horizontal">
			<?php echo JHtml::_('bootstrap.startTabSet', 'myTab', array('active' => 'general')); ?>
				<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'general', JText::_('COM_RESTAURANT_FIELDSET_DISH_CONTENT', true)); ?>
					<div class="row-fluid">
						<div class="span9">
							<fieldset class="adminform">
								<?php echo $this->form->getInput('description'); ?>
							</fieldset>
						</div>
						<div class="span3">
							<?php echo JLayoutHelper::render('joomla.edit.global', $this); ?>
						</div>
					</div>
				<?php echo JHtml::_('bootstrap.endTab'); ?>

				<?php // Do not show the publishing options if the edit form is configured not to. ?>
				<?php if ($params->show_publishing_options == 1): ?>
					<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'publishing', JText::_('COM_RESTAURANT_FIELDSET_PUBLISHING', true)); ?>
						<div class="row-fluid form-horizontal-desktop">
							<div class="span6">
								<?php echo JLayoutHelper::render('joomla.edit.publishingdata', $this); ?>
							</div>
							<div class="span6">
								<?php echo JLayoutHelper::render('joomla.edit.metadata', $this); ?>
							</div>
						</div>
					<?php echo JHtml::_('bootstrap.endTab'); ?>
				<?php endif; ?>

				<?php if (JLanguageAssociations::isEnabled()): ?>
					<div class="hidden">
						<?php echo JLayoutHelper::render('joomla.edit.associations', $this); ?>
					</div>
				<?php endif; ?>

				<?php $this->show_options = $params->show_dish_options; ?>
				<?php echo JLayoutHelper::render('joomla.edit.params', $this); ?>

				<?php if ($this->canDo->get('core.admin')): ?>
					<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'editor', JText::_('COM_RESTAURANT_FIELDSET_SLIDER_EDITOR_CONFIG', true)); ?>
						<?php foreach ($this->form->getFieldset('editorConfig') as $field): ?>
							<div class="control-group">
								<div class="control-label">
									<?php echo $field->label; ?>
								</div>
								<div class="controls">
									<?php echo $field->input; ?>
								</div>
							</div>
						<?php endforeach; ?>
					<?php echo JHtml::_('bootstrap.endTab'); ?>
				<?php endif; ?>

				<?php if ($this->canDo->get('core.admin')): ?>
					<?php echo JHtml::_('bootstrap.addTab', 'myTab', 'permissions', JText::_('COM_RESTAURANT_FIELDSET_RULES', true)); ?>
						<?php echo $this->form->getInput('rules'); ?>
					<?php echo JHtml::_('bootstrap.endTab'); ?>
				<?php endif; ?>
			<?php echo JHtml::_('bootstrap.endTabSet'); ?>
			<div>
				<input type="hidden" name="task" value="" />
				<input type="hidden" name="return" value="<?php echo JFactory::getApplication()->input->getBase64('return'); ?>" />
				<?php echo JHtml::_('form.token'); ?>
			</div>
		</div>
	</form>
</div>
