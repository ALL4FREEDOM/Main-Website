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
 * List View
 */
class SnippetsViewList extends JViewLegacy
{
	protected $enabled;
	protected $list;
	protected $pagination;
	protected $state;
	protected $config;
	protected $parameters;

	/**
	 * Display the view
	 *
	 */
	public function display($tpl = null)
	{
		$this->enabled       = SnippetsHelper::isEnabled();
		$this->list          = $this->get('Items');
		$this->pagination    = $this->get('Pagination');
		$this->state         = $this->get('State');
		$this->config        = RL_Parameters::getInstance()->getComponentParams('snippets');
		$this->filterForm    = $this->get('FilterForm');
		$this->activeFilters = $this->get('ActiveFilters');
		$this->hasCategories = $this->get('HasCategories');

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

		$state = $this->get('State');
		$canDo = SnippetsHelper::getActions();

		$viewLayout = JFactory::getApplication()->input->get('layout', 'default');

		RL_Document::style('regularlabs/style.min.css');
		RL_Document::style('snippets/style.min.css', '6.2.0');

		if ($viewLayout == 'import')
		{
			// Set document title
			JFactory::getDocument()->setTitle(JText::_('SNIPPETS') . ': ' . JText::_('RL_IMPORT_ITEMS'));
			// Set ToolBar title
			JToolbarHelper::title(JText::_('SNIPPETS') . ': ' . JText::_('RL_IMPORT_ITEMS'), 'snippets icon-reglab');
			// Set toolbar items for the page
			JToolBarHelper::back('JTOOLBAR_BACK', 'index.php?option=com_snippets');

			return;
		}

		// Set document title
		JFactory::getDocument()->setTitle(JText::_('SNIPPETS') . ': ' . JText::_('RL_LIST'));
		// Set ToolBar title
		JToolbarHelper::title(JText::_('SNIPPETS') . ': ' . JText::_('RL_LIST'), 'snippets icon-reglab');
		// Set toolbar items for the page
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::addNew('item.add');
		}
		if ($canDo->get('core.edit'))
		{
			JToolbarHelper::editList('item.edit');
		}
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::custom('list.copy', 'copy', 'copy', 'JTOOLBAR_DUPLICATE', true);
		}
		if ($canDo->get('core.edit.state') && $state->get('filter.state') != 2)
		{
			JToolbarHelper::publish('list.publish', 'JTOOLBAR_PUBLISH', true);
			JToolbarHelper::unpublish('list.unpublish', 'JTOOLBAR_UNPUBLISH', true);
		}
		if ($canDo->get('core.delete') && $state->get('filter.state') == -2)
		{
			JToolbarHelper::deleteList('', 'list.delete', 'JTOOLBAR_EMPTY_TRASH');
		}
		else if ($canDo->get('core.edit.state'))
		{
			JToolbarHelper::trash('list.trash');
		}
		if ($canDo->get('core.create'))
		{
			JToolbarHelper::custom('list.export', 'box-remove', 'box-remove', 'RL_EXPORT');
			JToolbarHelper::custom('list.import', 'box-add', 'box-add', 'RL_IMPORT', false);
		}
		if ($canDo->get('core.admin'))
		{
			JToolbarHelper::preferences('com_snippets');
		}
	}
}
