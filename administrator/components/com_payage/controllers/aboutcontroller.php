<?php
/********************************************************************
Product		: Payage
Date		: 16 November 2016
Copyright	: Les Arbres Design 2014-2016
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted access');

class PayageControllerAbout extends JControllerLegacy
{

function display($cachable = false, $urlparams = false)
{
    LAPG_view::addSubMenu('about');
	$view = $this->getView('about', 'html');
	$account_model = $this->getModel('account');
	$gateway_list = $account_model->getGatewayList();
	$view->gateway_list = $gateway_list;
	$view->display();
}

function trace_on()
{
	$account_model = $this->getModel('account');
	$gateway_list = $account_model->getGatewayList();
	LAPG_trace::init_trace($gateway_list);
	$this->setRedirect('index.php?option=com_payage&controller=about');
}

function trace_off()
{
	LAPG_trace::delete_trace_file();
	$this->setRedirect('index.php?option=com_payage&controller=about');
}

function cancel()
{
	$this->setRedirect('index.php?option=com_payage');
	return;
}

} // class





