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

jimport('joomla.application.component.controlleradmin');

/**
 * List Controller
 */
class SnippetsControllerList extends JControllerAdmin
{
	/**
	 * @var        string    The prefix to use with controller messages.
	 */
	protected $text_prefix = 'RL';

	/**
	 * Method to update a record.
	 */
	public function activate()
	{
		// Check for request forgeries.
		JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

		// Initialise variables.
		$ids  = JFactory::getApplication()->input->get('cid', [], 'array');
		$name = JFactory::getApplication()->input->getString('name');

		if (empty($ids))
		{
			JError::raiseWarning(500, JText::_('COM_REDIRECT_NO_ITEM_SELECTED'));
		}
		else
		{
			// Get the model.
			$model = $this->getModel();

			JArrayHelper::toInteger($ids);

			// Remove the list.
			if (!$model->activate($ids, $name))
			{
				JError::raiseWarning(500, $model->getError());
			}
			else
			{
				$this->setMessage(JText::plural('RL_N_LINKS_UPDATED', count($ids)));
			}
		}

		$this->setRedirect('index.php?option=com_snippets&view=list');
	}

	/**
	 * Proxy for getModel.
	 */
	public function getModel($name = 'Item', $prefix = 'SnippetsModel', $config = ['ignore_request' => true])
	{
		$model = parent::getModel($name, $prefix, $config);

		return $model;
	}

	/**
	 * Import Method
	 * Set layout to import
	 */
	public function import()
	{
		$file = JRequest::getVar('file', '', 'files');

		if (!empty($file))
		{
			if (isset($file['name']))
			{
				// Get the model.
				$model      = $this->getModel('List');
				$model_item = $this->getModel('Item');
				$model->import($model_item);
			}
			else
			{
				$msg = JText::_('SNP_PLEASE_CHOOSE_A_VALID_FILE');
				$this->setRedirect('index.php?option=com_snippets&view=list&layout=import', $msg);
			}
		}
		else
		{
			$this->setRedirect('index.php?option=com_snippets&view=list&layout=import');
		}
	}

	/**
	 * Export Method
	 * Export the selected items specified by id
	 */
	public function export()
	{
		$ids = JFactory::getApplication()->input->get('cid', [], 'array');

		// Get the model.
		$model = $this->getModel('List');

		$model->export($ids);
	}

	/**
	 * Copy Method
	 * Copy all items specified by array cid
	 * and set Redirection to the list of items
	 */
	public function copy()
	{
		$ids = JFactory::getApplication()->input->get('cid', [], 'array');

		// Get the model.
		$model      = $this->getModel('List');
		$model_item = $this->getModel('Item');

		$model->copy($ids, $model_item);
	}

	/**
	 * Method to save the submitted ordering values for records via AJAX.
	 *
	 * @return    void
	 *
	 * @since   3.0
	 */
	public function saveOrderAjax()
	{
		$pks   = $this->input->post->get('cid', [], 'array');
		$order = $this->input->post->get('order', [], 'array');

		// Sanitize the input
		JArrayHelper::toInteger($pks);
		JArrayHelper::toInteger($order);

		// Get the model
		$model = $this->getModel();

		// Save the ordering
		$return = $model->saveorder($pks, $order);

		if ($return)
		{
			echo "1";
		}

		// Close the application
		JFactory::getApplication()->close();
	}
}
