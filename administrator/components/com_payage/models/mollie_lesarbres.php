<?php
/********************************************************************
Product     : Mollie Gateway for Payage
Date		: 13 December 2014
Copyright   : Les Arbres Design 2014
Contact     : http://www.lesarbresdesign.info
Licence     : GNU General Public License
*********************************************************************/
defined("_JEXEC") or die("Restricted Access");

class PayageModelMollie_LesArbres extends PayageModelAccount
{
var $app = null;
var $common_data = null;
var $specific_data = null;
var $gateways = null;

function __construct()
{
	parent::__construct();
	require_once(JPATH_ADMINISTRATOR."/components/com_payage/helpers/Mollie/API/Autoloader.php");
}

//-------------------------------------------------------------------------------
// Initialise data items specific to this gateway
// - the account class initialises the common data items
//
public function initData($gateway_info)
{
	parent::initData($gateway_info);
	$this->specific_data = new stdClass();
	$this->specific_data->account_apikey = '';
}

//-------------------------------------------------------------------------------
// Validate the account details
// - the account class checks the common data items
//
public function check_post_data()
{
	$ok = parent::check_post_data();
	
	if ($this->specific_data->account_apikey == '')
		{
		$this->app->enqueueMessage(JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_MOLLIE_APIKEY'), 'error');
		$ok = false;
		}

	return $ok;
}

//-------------------------------------------------------------------------------
// Get the post data specific to this gateway
// - the account class gets the common data items
//
public function &getPostData()
{
	parent::getPostData();
	$this->specific_data = new stdClass();
	$jinput = JFactory::getApplication()->input;
	$this->specific_data->account_apikey = $jinput->get('account_apikey', '', 'STRING');
	$this->specific_data->account_method = $jinput->get('account_method', '', 'STRING');
	return $this->specific_data;
}

//-------------------------------------------------------------------------------
// handle an incoming request from the payment gateway
// - we assume this is a genuine request because the front end found the account and payment records
// our model instance already has $this->common_data and $this->specific_data
//
public function Gateway_handle_request($payment_model)
{
	$jinput = JFactory::getApplication()->input;
	$task = $jinput->get('task','', 'STRING');
	switch ($task)
		{
		case 'redirect':
			$action = $this->handle_redirect($payment_model);
			return $action;

		case 'return':
			$this->handle_return($payment_model);
			return LAPG_CALLBACK_USER;

		case 'cancel':
			return LAPG_CALLBACK_CANCEL;

		default:
			LAPG_trace::trace('Mollie handle_request() unknown task $task');
			return LAPG_CALLBACK_NONE;			// should never happen
		}

}

//-------------------------------------------------------------------------------
// Handle a redirect from a Mollie payment button
//
private function handle_redirect($payment_model)
{
	LAPG_trace::trace('Mollie handle_redirect() for Payage transaction id: '.$payment_model->data->pg_transaction_id);
	$payment_model->data->account_id = $this->common_data->id;

	if (!function_exists('curl_version'))
		{
		LAPG_trace::trace("CURL not installed - cannot use Mollie");
		$payment_model->data->pg_status_code = LAPG_STATUS_FAILED;
		$payment_model->data->pg_status_text = JText::_('COM_PAYAGE_CURL_NOT_INSTALLED');
		$stored = $payment_model->store();
		return LAPG_CALLBACK_USER;			// return to the calling application
		}

// set up the payment in the gateway

	try
		{
		$callbackUrl = htmlentities(JURI::base().'index.php?option=com_payage&task=return&aid='.$this->common_data->id.'&tid='.$payment_model->data->pg_transaction_id);
		$customer_fee = parent::calculate_gateway_fee($this->common_data, $payment_model->data->gross_amount);
		$total_amount = $payment_model->data->gross_amount + $customer_fee;
		
		$params = array(
			"method"      => $this->specific_data->account_method,
			"amount"      => $total_amount,
			"description" => $payment_model->data->item_name,
			"redirectUrl" => $callbackUrl,
			"metadata"    => array("pg_transaction_id" => $payment_model->data->pg_transaction_id),
			);
		if ($this->common_data->account_language != 'AUTO')
			$params['locale'] = $this->common_data->account_language;

		$mollie = new Mollie_API_Client;
		$mollie->setApiKey($this->specific_data->account_apikey);
		$payment = $mollie->payments->create($params);
			


		LAPG_trace::trace("Mollie payment created: ".print_r($payment,true));

// save the payment details so far

		$setup_details = new stdClass();
		foreach ($payment as $key => $value)
			$setup_details->$key = $value;
		$payment_model->data->gw_transaction_details->Setup = $setup_details;
		$payment_model->data->gw_transaction_id = $payment->id;
		$payment_model->data->customer_fee = $customer_fee;
		$stored = $payment_model->store();

// redirect the browser to the Mollie gateway

		header("Location: ".$payment->getPaymentUrl());
		return LAPG_CALLBACK_NONE;			// we are at the gateway - we will go back to the calling application later
		}

	catch (Mollie_API_Exception $e)
		{
		LAPG_trace::trace("Mollie payment creation failed: ".$e->getMessage());
		$payment_model->data->pg_status_code = LAPG_STATUS_FAILED;
		$payment_model->data->pg_status_text = $e->getMessage();
		$payment_model->data->pg_history .= $payment_model->data->now." - ".JText::_('COM_PAYAGE_FAILED');
		$stored = $payment_model->store();
		return LAPG_CALLBACK_USER;			// return to the calling application
		}
}

//-------------------------------------------------------------------------------
// handle a status update from Mollie
// - these arrive immediately after payment or failure, or later, for example after a refund
//
private function handle_return($payment_model)
{
	LAPG_trace::trace('Mollie handle_return() for Payage transaction id: '.$payment_model->data->pg_transaction_id);

	$payment_model->data->account_id = $this->common_data->id;

// get the payment status from the gateway

	try
	{
		$mollie = new Mollie_API_Client;
		$mollie->setApiKey($this->specific_data->account_apikey);
		$payment  = $mollie->payments->get($payment_model->data->gw_transaction_id);
		LAPG_trace::trace(" Mollie status: ".$payment->status);
	
		switch ($payment->status)
			{
			case 'paid':
			case 'paidout':
				$pg_status_code = LAPG_STATUS_SUCCESS;
				break;
			case 'expired':
			case 'cancelled':    
				$pg_status_code = LAPG_STATUS_CANCELLED;
				break;
			case 'failed': 
				$pg_status_code = LAPG_STATUS_FAILED;
				break;		
			case 'pending':
				$pg_status_code = LAPG_STATUS_PENDING;
				break;
			case 'refunded':
				$pg_status_code = LAPG_STATUS_REFUNDED;
				break;
			default:
				$pg_status_code = LAPG_STATUS_FAILED;
			}                    
		
		$payment_model->data->pg_status_code = $pg_status_code;
		$payment_model->data->pg_status_text = $payment->status;
		
// update the history

		$status_description = PayageHelper::getPaymentDescription($payment_model->data->pg_status_code);
		$payment_model->data->pg_history .= $payment_model->data->now." - Mollie: $status_description";

// update the payment record
// we store the latest details in the root of gw_transaction_details so they are easily visible
// and we also store each update separately in case something goes wrong and we need to see the full history

		$update_details = new stdClass();
		foreach ($payment as $key => $value)
			{
			$payment_model->data->gw_transaction_details->$key = $value;
			$update_details->$key = $value;
			}
		$payment_model->add_transaction_details($update_details);
		$stored = $payment_model->store();
		return;
		}

	catch (Mollie_API_Exception $e)
		{
		LAPG_trace::trace("Mollie payment verification failed: ".$e->getMessage());
		$payment_model->data->pg_status_code = LAPG_STATUS_FAILED;
		$payment_model->data->pg_status_text = $e->getMessage();
		$payment_model->data->pg_history .= $payment_model->data->now." - ".JText::_('COM_PAYAGE_FAILED');
		$stored = $payment_model->store();
		return;
		}
}

//-------------------------------------------------------------------------------
// Build a Buy Now button
// Unusually, for this gateway we need to redirect back to Payage
// because Mollie needs some custom html headers that cannot be sent from a form
//
public function Gateway_make_button($payment_data, $call_array, $app_fee)
{
	$process_url = htmlentities(JURI::base().'index.php?option=com_payage&task=redirect&aid='.$this->common_data->id.'&tid='.$payment_data->pg_transaction_id);
	$button_url = JURI::base(true).'/'.$this->common_data->button_image;
	
	$html  = "\n".'<form action="" method="get">';	
	$html .= '<img src="'.$button_url.'" onclick="window.location='."'".$process_url."'".'" alt="Mollie" style="cursor:pointer" title="'.$this->common_data->button_title.'" '.$call_array['button_extra'].' />';
	$html .= "\n</form>\n";
	
	return $html;
}

//-------------------------------------------------------------------------------
// Test a Mollie API call
//
public function Gateway_test()
{
	if (!function_exists('curl_version'))				// make sure CURL is installed
		{
		$html = JText::_('COM_PAYAGE_CURL_NOT_INSTALLED').'<br />'.JText::_('COM_PAYAGE_GATEWAY_TEST_RESPONSE_NOT_OK');
		$this->app->enqueueMessage($html, 'error');
		return;
		}

	try													// try to connect to Mollie
		{
		$mollie = new Mollie_API_Client;
		$mollie->setApiKey($this->specific_data->account_apikey);
		$params = array(
			"amount"      => '10.00',
			"description" => 'Payage_Mollie_LesArbres',
			"redirectUrl" => JURI::base()
			);
		$payment = $mollie->payments->create($params);

// show the payment types supported by the acccount

		$methods = $mollie->methods->all();
		$html = JText::_('COM_PAYAGE_MOLLIE_METHOD').': ';
		$comma = '';
		foreach ($methods as $method)
			{
			$html .= $comma.$method->description;
			$comma = ', ';
			}
		$html .= "<br />".JText::_('COM_PAYAGE_GATEWAY_TEST_RESPONSE_OK');
		$this->app->enqueueMessage($html, 'message');
		return;
		}

	catch (Mollie_API_Exception $e)
		{
		$html = $e->getMessage().'<br />'.JText::_('COM_PAYAGE_GATEWAY_TEST_RESPONSE_NOT_OK');
		$this->app->enqueueMessage($html, 'error');
		return;
		}
}

} // class