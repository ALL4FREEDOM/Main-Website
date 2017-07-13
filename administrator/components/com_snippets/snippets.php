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

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_snippets'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

jimport('joomla.filesystem.file');

// return if Regular Labs Library plugin is not installed
if (
	!is_file(JPATH_PLUGINS . '/system/regularlabs/regularlabs.xml')
	|| !is_file(JPATH_LIBRARIES . '/regularlabs/autoload.php')
)
{
	$msg = JText::_('SNP_REGULAR_LABS_LIBRARY_NOT_INSTALLED')
		. ' ' . JText::sprintf('SNP_EXTENSION_CAN_NOT_FUNCTION', JText::_('COM_SNIPPETS'));
	JFactory::getApplication()->enqueueMessage($msg, 'error');

	return;
}

// give notice if Regular Labs Library plugin is not enabled
if (!JPluginHelper::isEnabled('system', 'regularlabs'))
{
	$msg = JText::_('SNP_REGULAR_LABS_LIBRARY_NOT_ENABLED')
		. ' ' . JText::sprintf('SNP_EXTENSION_CAN_NOT_FUNCTION', JText::_('COM_SNIPPETS'));
	JFactory::getApplication()->enqueueMessage($msg, 'notice');
}

require_once JPATH_LIBRARIES . '/regularlabs/autoload.php';

use RegularLabs\Library\Language as RL_Language;

// load the Regular Labs Library language file
RL_Language::load('plg_system_regularlabs');

// Dependency
require_once JPATH_LIBRARIES . '/regularlabs/fields/dependency.php';
RLFieldDependency::setMessage('/plugins/editors-xtd/snippets/snippets.php', 'SNP_THE_EDITOR_BUTTON_PLUGIN');
RLFieldDependency::setMessage('/plugins/system/snippets/snippets.php', 'SNP_THE_SYSTEM_PLUGIN');

$controller = JControllerLegacy::getInstance('Snippets');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
