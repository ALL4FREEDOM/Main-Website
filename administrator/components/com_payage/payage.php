<?php
/********************************************************************
Product		: Payage
Date		: 15 June 2017
Copyright	: Les Arbres Design 2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

// Check for ACL access

if (!JFactory::getUser()->authorise('core.manage', 'com_payage'))
    {
	$app = JFactory::getApplication();
    $app->enqueueMessage(JText::_('JERROR_ALERTNOAUTHOR'), 'error');
	return;
    }

require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/payage_helper.php';
require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/view_helper.php';
require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/db_helper.php';
require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/trace_helper.php';
require_once JPATH_ADMINISTRATOR.'/components/com_payage/models/account.php';

// load our css

$document = JFactory::getDocument();
$document->addStyleSheet(LAPG_ADMIN_ASSETS_URL.'payage.css?v=206');

$jinput = JFactory::getApplication()->input;
$controller = $jinput->get('controller','payment', 'STRING');
$task = $jinput->get('task','display', 'STRING');
	
// create an instance of the controller and tell it to execute $task

$classname = 'PayageController'.$controller;
require_once JPATH_ADMINISTRATOR.'/components/com_payage/controllers/'.$controller.'controller.php';

$controller = new $classname();
$controller->execute($task);
$controller->redirect();

