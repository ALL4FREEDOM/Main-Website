<?php
/********************************************************************
Product		: Payage
Date		: 2 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class PayageControllerAccount extends JControllerLegacy
{

function __construct()
	{
	parent::__construct();
	$this->registerTask('apply', 'save');
	$this->registerTask('save2copy', 'save');
	}

function display($cachable = false, $urlparams = false)
{
    LAPG_view::addSubMenu('account');
	$view = $this->getView('account', 'html');
	$account_model = $this->getModel('account');
	$gateway_list = $account_model->getGatewayList(true);
	$account_list = $account_model->getList();
	$view->gateway_list = $gateway_list;
	$view->account_list = $account_list;
	$view->display();
}

function account_choice()
{
    LAPG_view::addSubMenu('account');
	$view = $this->getView('account', 'html');
	$account_model = $this->getModel('account');
	$view->setModel($account_model);
	$view->choice();
}

function new_account()	// coming back from the account_choice page with a gateway_type
{
	$jinput = JFactory::getApplication()->input;
	$gateway_type = $jinput->get('gateway_type','', 'STRING');
	$account_model = $this->getModel('account');
	$gateway_info = $account_model->getGatewayInfo($gateway_type);
	$gateway_model = PayageHelper::getGatewayInstance($gateway_type);
	if ($gateway_model === false)
		{
		$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account',JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$gateway_name));
		return;
		}
	$view = $this->getView('account', 'html');
	$view->gateway_info = $gateway_info;
	$gateway_model->initData($gateway_info);
	$view->common_data = $gateway_model->common_data;
	$view->specific_data = $gateway_model->specific_data;
	$view->setModel($gateway_model,true);
	$view->edit();
}

function edit()
{
	$account_model = $this->getModel('account');
	$jinput = JFactory::getApplication()->input;
	$cid = $jinput->get('cid',  array(), 'ARRAY');
	$id = (int) $cid[0];
	$account_data = $account_model->getOne($id);
	if ($account_data === false)
		{												// an error has been enqueued
		$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account');
		return;
		}

	$gateway_info = $account_model->getGatewayInfo($account_model->common_data->gateway_type);
	if (empty($gateway_info))
		{
		$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account',JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',''));
		return;
		}
	
	$gateway_model = PayageHelper::getGatewayInstance($account_model->common_data->gateway_type);
	if ($gateway_model === false)
		{
		$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account',JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$gateway_name));
		return;
		}
	$view = $this->getView('account', 'html');
	$view->gateway_info = $gateway_info;
	$view->common_data = $account_model->common_data;
	$view->specific_data = $account_model->specific_data;
	$view->translations = $account_model->translations;
	$view->setModel($gateway_model,true);
	$view->edit();
}

function save()
{
	$jinput = JFactory::getApplication()->input;
	$task = $jinput->get('task', '', 'STRING');					// 'save' or 'apply'
	$gateway_type = $jinput->get('gateway_type', '', 'STRING');
	$gateway_model = PayageHelper::getGatewayInstance($gateway_type);
	if ($gateway_model === false)
		{
		$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account',JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$gateway_name));
		return;
		}
	$gateway_model->getPostData();

	if ($task == 'save2copy')
		$gateway_model->common_data->id = 0;
	
	$valid = $gateway_model->check_post_data();
	if ($valid)
		if ($gateway_model->store()  and ($task == 'save'))
			{
			$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account',JText::_('COM_PAYAGE_SAVED'));
			return;
			}

// task=apply or 'save2copy' or a validation error - re-display the view

	if ($valid)
		JFactory::getApplication()->enqueueMessage(JText::_('COM_PAYAGE_SAVED'));
	$view = $this->getView('account', 'html');
	$account_model = $this->getModel('account');
	$gateway_info = $account_model->getGatewayInfo($gateway_type);
	$view->gateway_info = $gateway_info;
	$view->common_data = $gateway_model->common_data;
	$view->specific_data = $gateway_model->specific_data;
	$view->translations = $gateway_model->translations;
	$view->setModel($gateway_model,true);
	$view->edit();
}

function test()
{
	$jinput = JFactory::getApplication()->input;
	$gateway_type = $jinput->get('gateway_type', '', 'STRING');
	$account_id = $jinput->get('id', '', 'STRING');
	$gateway_model = PayageHelper::getGatewayInstance($gateway_type);
	if ($gateway_model === false)
		{
		$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account',JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$gateway_name));
		return;
		}
	$gateway_model->getPostData();
	$gateway_model->Gateway_test();			// tests communication and enqueues a message

	$view = $this->getView('account', 'html');
	$gateway_info = $gateway_model->getGatewayInfo($gateway_type);
	$view->gateway_info = $gateway_info;
	$view->common_data = $gateway_model->common_data;
	$view->specific_data = $gateway_model->specific_data;
	$view->setModel($gateway_model,true);
	$view->edit();
}

function publish()
{
	$account_model = $this->getModel('account');
	$account_model->publish(1);
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account');
}

function unpublish()
{
	$account_model = $this->getModel('account');
	$account_model->publish(0);
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account');
}

function remove()
{
	$account_model = $this->getModel('account');
	$account_model->delete();
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account');
}
		
function cancel()
{
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=account');
}

} // class