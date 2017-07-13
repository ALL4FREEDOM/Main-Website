<?php
/**
 * @package     perfectdashboard
 * @version     1.2.0
 *
 * @copyright   Copyright (C) 2015 Perfect Web. All rights reserved. http://www.perfect-web.co
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

if (!JFactory::getUser()->authorise('core.manage', 'com_perfectdashboard'))
{
	return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

require_once JPATH_ADMINISTRATOR.'/components/com_perfectdashboard/version.php';

$controller = JControllerLegacy::getInstance('perfectdashboard');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();
