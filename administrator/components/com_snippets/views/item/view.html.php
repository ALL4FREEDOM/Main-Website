<?php
/**
 * @package         Snippets
 * @version         6.2.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2017 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Parameters as RL_Parameters;

jimport('joomla.application.component.view');

/**
 * Item View
 */
class SnippetsViewItem extends JViewLegacy
{
	protected $item;
	protected $form;
	protected $state;
	protected $config;
	protected $parameters;

	/**
	 * Display the view
	 *
	 */
	public function display($tpl = null)
	{
		$this->form   = $this->get('Form');
		$this->item   = $this->_models['item']->getItem(null, 1);
		$this->state  = $this->get('State');
		$this->config = RL_Parameters::getInstance()->getComponentParams('snippets', $this->state->params);

		// Check for errors.
		if (count($errors = $this->get('Errors')))
		{
			JError::raiseError(500, implode("\n", $errors));

			return false;
		}

		$this->addToolbar();
		parent::display($tpl);
	}

	/**
	 * Add the page title and toolbar.
	 *
	 */
	protected function addToolbar()
	{

		$isNew = ($this->item->id == 0);
		$canDo = SnippetsHelper::getActions();

		RL_Document::style('regularlabs/style.min.css');
		RL_Document::style('snippets/style.min.css', '6.2.0');

		JFactory::getApplication()->input->set('hidemainmenu', true);

		// Set document title
		JFactory::getDocument()->setTitle(JText::_('SNIPPETS') . ': ' . JText::_('RL_ITEM'));
		// Set ToolBar title
		JToolbarHelper::title(JText::_('SNIPPETS') . ': ' . JText::_('RL_ITEM'), 'snippets icon-reglab');

		// If not checked out, can save the item.
		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::apply('item.apply');
			JToolbarHelper::save('item.save');
		}

		if ($canDo->get('core.edit') && $canDo->get('core.create'))
		{
			JToolbarHelper::save2new('item.save2new');
		}
		if (!$isNew && $canDo->get('core.create'))
		{
			JToolbarHelper::save2copy('item.save2copy');
		}

		if (empty($this->item->id))
		{
			JToolbarHelper::cancel('item.cancel');
		}
		else
		{
			JToolbarHelper::cancel('item.cancel', 'JTOOLBAR_CLOSE');
		}
	}

	protected function render(&$form, $name = '', $title = '')
	{
		$items = [];
		foreach ($form->getFieldset($name) as $field)
		{
			$items[] = '<div class="control-group"><div class="control-label">'
				. $field->label
				. '</div><div class="controls">'
				. $field->input
				. '</div></div>';
		}
		if (empty ($items))
		{
			return '';
		}

		return implode('', $items);
	}
}
