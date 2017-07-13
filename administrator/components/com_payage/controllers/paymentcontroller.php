<?php
/********************************************************************
Product		: Payage
Date		: 14 March 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class PayageControllerPayment extends JControllerLegacy
{

function display($cachable = false, $urlparams = false)
{
    LAPG_view::addSubMenu('payment');
	$payment_model = $this->getModel('payment');
    $account_model = $this->getModel('account');
	$view = $this->getView('payment', 'html');
	$view->payment_list  = $payment_model->getList();;
	$view->pagination    = $payment_model->getPagination();;
    $view->app_list      = $payment_model->get_app_array();
    $view->currency_list = $payment_model->get_currency_array();
    $view->account_list  = $account_model->get_account_array();
	$view->display();
}

function unconfirmed()
{
    LAPG_view::addSubMenu('pending');
	$payment_model = $this->getModel('payment');
	$view = $this->getView('payment', 'html');
	$view->payment_list  = $payment_model->getPendingList();;
	$view->pagination    = $payment_model->getPagination();;
	$view->unconfirmed();
}

function detail()
{
	$jinput = JFactory::getApplication()->input;
	$cid = $jinput->get('cid',  array(), 'ARRAY');
	$id = (int) $cid[0];
	$payment_model = $this->getModel('payment');
	$payment_data = $payment_model->getOne($id);
	$account_model = $this->getModel('account');
	$account_data = $account_model->getOne($payment_model->data->account_id);
	$gateway_info = $account_model->getGatewayInfo($account_model->common_data->gateway_type);
	
	$view = $this->getView('payment', 'html');
	$view->payment_data = $payment_data;
	$view->account_data = $account_data;
	$view->gateway_info = $gateway_info;
	$view->edit();
}

// returns full details of a specified field
function full_details()
{
	$jinput = JFactory::getApplication()->input;
	$id = $jinput->get('id', 0, 'INT');
	$column = $jinput->get('column', '', 'STRING');
	$payment_model = $this->getModel('payment');
	$payment_data = $payment_model->getOne($id);
	echo '<pre>'.print_r($payment_data->$column,true).'</pre>';
}

function remove()
{
	$payment_model = $this->getModel('payment');
	$payment_model->delete();
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=payment');
}

function cancel()
{
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=payment');
}

function status_refund()
{
	$this->change_status(LAPG_STATUS_REFUNDED);
}

function status_success()
{
	$this->change_status(LAPG_STATUS_SUCCESS);
}

function status_pending()
{
	$this->change_status(LAPG_STATUS_PENDING);
}

function status_failed()
{
	$this->change_status(LAPG_STATUS_FAILED);
}

function change_status($new_status)
{
	$jinput = JFactory::getApplication()->input;
	$id = $jinput->get('id', 0, 'INT');
	$payment_model = $this->getModel('payment');
	$payment_data = $payment_model->getOne($id);
	$payment_model->change_status($new_status);
	$this->setRedirect(LAPG_COMPONENT_LINK.'&controller=payment&task=detail&cid[]='.$id);
	
// call the application, if it supplied an app_update_path

	if (empty($payment_data->app_update_path))
		return;
		
	LAPG_trace::trace("User initiated update. Calling payment_update() for tid ".$payment_data->pg_transaction_id.' '.$payment_data->app_update_path);
	require_once $payment_data->app_update_path;
	payment_update($payment_data->pg_transaction_id);
}

function download()
{
	$jinput = JFactory::getApplication()->input;
	$id = $jinput->get('id', 0, 'INT');
	$payment_model = $this->getModel('payment');
	$payment_data = $payment_model->getOne($id);
	$output = print_r($payment_data,true);
	$output_length = strlen($output);
	while (@ob_end_clean());
	Header("Content-Description: File Transfer");
	Header("Content-Transfer-Encoding: binary\n");
	Header("Content-Type: text/plain; charset=utf-8");
	Header("Content-Disposition: attachment; filename=payment_".$id.".txt");
	header("Content-Length: ".strlen($output));
	header("Content-Range: bytes 0-" .$output_length.'/'.$output_length);
	Header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
	Header("Pragma: no-cache");
	Header("Expires: 0");
	@flush();
	echo $output;
	flush();
	exit;
	return;				// we cannot send a page now because we just sent a file

}


} // class