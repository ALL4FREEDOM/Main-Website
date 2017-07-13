<?php
/********************************************************************
Product		: Payage
Date		: 15 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class PayageModelAccount extends LAPG_model
{
var $app = null;
var $common_data = null;
var $specific_data = null;
var $gateways = null;

function __construct()
{
	parent::__construct();
	$this->app = JFactory::getApplication();
}

//-------------------------------------------------------------------------------
// get the list of all the gateway types by reading the xml files
//
function &getGatewayList($show_warnings = false)
{
	$this->gateways = array();
	
	$files = glob(JPATH_ADMINISTRATOR.'/components/com_payage/payage_*.xml');
	
	if (empty($files))
		{
		$this->app->enqueueMessage(JText::_('COM_PAYAGE_NO_GATEWAYS'), 'error');
		$ret = false;
		return $ret;
		}		
		
	$this->gateways = array();
	
	foreach ($files as $xmlfile)
		{
		$xml = JFactory::getXML($xmlfile,true);
		if (!isset($xml->gateway_info))
			continue;
		$gateway_info = $xml->gateway_info;
		$gateway_type = (string) $gateway_info->type;
		if (!strpos($gateway_type,'_') or (strlen($gateway_type) > 32))
			{
			if ($show_warnings)
				$this->app->enqueueMessage(JText::_('COM_PAYAGE_INVALID').' (gateway_info->type) '.basename($xmlfile), 'notice');
			continue;
			}
		$gateway_shortName = (string) $gateway_info->shortName;
		if (empty($gateway_shortName))
			{
			if ($show_warnings)
				$this->app->enqueueMessage(JText::_('COM_PAYAGE_INVALID').' (gateway_info->shortName) '.basename($xmlfile), 'notice');
			continue;
			}
		if (isset($this->gateways[$gateway_type]))
			{
			if ($show_warnings)
				$this->app->enqueueMessage(JText::sprintf('COM_PAYAGE_GATEWAY_DUPLICATE',basename($xmlfile),$this->gateways[$gateway_type]['xmlFile']), 'notice');
			continue;
			}
		$this->gateways[$gateway_type]['xmlFile'] = basename($xmlfile);
		$this->gateways[$gateway_type]['type'] = (string) $gateway_info->type;
		$this->gateways[$gateway_type]['shortName'] = (string) $gateway_info->shortName;
		$this->gateways[$gateway_type]['longName'] = (string) $gateway_info->longName;
		$this->gateways[$gateway_type]['author'] = (string) $xml->author;
		$this->gateways[$gateway_type]['authorUrl'] = (string) $xml->authorUrl;
		$this->gateways[$gateway_type]['version'] = (string) $xml->version;
		$this->gateways[$gateway_type]['defaultButton'] = (string) $gateway_info->defaultButton;
		$this->gateways[$gateway_type]['defaultTitle'] = (string) $gateway_info->defaultTitle;
		$this->gateways[$gateway_type]['gatewayUrl'] = (string) $gateway_info->gatewayUrl;
		$this->gateways[$gateway_type]['helpUrl'] = (string) $gateway_info->helpUrl;
		$this->gateways[$gateway_type]['docUrl'] = (string) $gateway_info->docUrl;
			
		if (!$show_warnings)
			continue;
		$model = JPATH_ADMINISTRATOR.'/components/com_payage/models/'.strtolower($gateway_type).'.php';
		if (!file_exists($model))
			$this->app->enqueueMessage(JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$gateway_type).' - '.JText::_('COM_PAYAGE_MISSING').' '.$model, 'notice');
		$form = JPATH_ADMINISTRATOR.'/components/com_payage/forms/'.strtolower($gateway_type).'.xml';
		if (!file_exists($form))
			$this->app->enqueueMessage(JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$gateway_type).' - '.JText::_('COM_PAYAGE_MISSING').' '.$form, 'notice');
		}
	
	return $this->gateways;
}

//-------------------------------------------------------------------------------
// get the xml data for one specified gateway type
//
function &getGatewayInfo($gateway_type)
{
	$this->getGatewayList();
	if (isset($this->gateways[$gateway_type]))
		return $this->gateways[$gateway_type];
	else
		{
		$ret = array();
		return $ret;
		}
}

//-------------------------------------------------------------------------------
// initialise the common data for a new account
// each gateway model also has its own initData() function
//
function initData($gateway_info)
{
	$this->common_data = new stdClass();
	$this->common_data->id = 0;
	$this->common_data->published = 1;
	$this->common_data->gateway_type = $gateway_info['type'];
	$this->common_data->gateway_shortname = $gateway_info['shortName'];
	$this->common_data->account_group = 1;
	$this->common_data->account_name = '';
	$this->common_data->account_description = '';
	$this->common_data->account_email = '';
	$this->common_data->account_language = '';
	$this->common_data->account_currency = '';
	$this->common_data->button_image = $gateway_info['defaultButton'];
	$this->common_data->button_title = $gateway_info['defaultTitle'];
	$this->common_data->fee_type = 0;
	$this->common_data->fee_amount = 0;
	$this->common_data->fee_min = 0;
	$this->common_data->fee_max = 0;
	$this->common_data->currency_symbol = '';
	$this->common_data->currency_format = 0;
	$this->common_data->specific_data = '';
	$this->common_data->translations = '';
	return $this->common_data;
}

//-------------------------------------------------------------------------------
// validate the data that is common to all gateways
// each gateway model also has its own check_post_data() function
//
function check_post_data()
{
    $errors = array();
	
	if (($this->common_data->account_name == '') or (!PayageHelper::clean_string($this->common_data->account_name)))
		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_ACCOUNT_NAME');
        
    if (!preg_match('/^[A-Z]{3}$/', $this->common_data->account_currency))
		$errors[] = JText::_('COM_PAYAGE_INVALID_CURRENCY');

   	if (stristr($this->common_data->button_image, 'http') !== false)
 		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_BUTTON');
   		
	if (!PayageHelper::clean_string($this->common_data->button_title))
		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_BUTTON_TITLE');

	if (!PayageHelper::is_posint($this->common_data->account_group, false))
		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_GROUP');

	if ((!PayageHelper::is_number($this->common_data->fee_min)) or  ($this->common_data->fee_min < 0))
		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_FEE_MIN');	      

	if ((!PayageHelper::is_number($this->common_data->fee_max)) or  ($this->common_data->fee_max < 0))
		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_FEE_MAX');	      

	if ((!PayageHelper::is_number($this->common_data->fee_amount)) or  ($this->common_data->fee_amount < 0))
		$errors[] = JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_AMOUNT');	      
		
	if (($this->common_data->fee_max > 0) and ($this->common_data->fee_min > $this->common_data->fee_max))
		$errors[] = JText::_('COM_PAYAGE_FEE_MIN_MAX');	      
				
	if (!empty($errors))
		{
		$this->app->enqueueMessage(implode('<br />',$errors), 'error');
		return false;
		}
    return true;
}

//-------------------------------------------------------------------------------
// get an existing row
// return false with an error if we couldn't find it
//
function getOne($id)
{
    if (!is_numeric($id))
        return false;
	$query = "SELECT * FROM `#__payage_accounts` WHERE id = ".$this->_db->Quote($id);
	$this->common_data = $this->ladb_loadObject($query);

	if (empty($this->common_data))
		{
		if ($this->app->isAdmin())
			$this->app->enqueueMessage(JText::_('COM_PAYAGE_ACCOUNT_NO_RECORD'), 'error');
		$this->common_data = false;
		return $this->common_data;
		}
	$this->specific_data = unserialize($this->common_data->specific_data);
	$this->translations = unserialize($this->common_data->translations);
    if ($this->app->isAdmin())
    	return $this->common_data;

// for the front end we overwrite the translatable fields with the data for the current site language

    $lang = JFactory::getLanguage('JPATH_SITE');
    $languages = $lang->getKnownLanguages();
    if (count($languages) <= 1)
    	return $this->common_data;
        
	$tag = $lang->get('tag');              // get current language
    if (!empty($this->translations[$tag]['button_title']))
        $this->common_data->button_title = $this->translations[$tag]['button_title'];
    if (!empty($this->translations[$tag]['button_image']))
        $this->common_data->button_image = $this->translations[$tag]['button_image'];
    if (!empty($this->translations[$tag]['account_description']))
        $this->common_data->account_description = $this->translations[$tag]['account_description'];

	return $this->common_data;
}

//-------------------------------------------------------------------------------
// Get the common post data
// each gateway model also has its own getPostData() function
//
function &getPostData()
{
	$this->common_data = new stdClass();
	$jinput = JFactory::getApplication()->input;
	$this->common_data->id = $jinput->get('id', 0, 'INT');
	$this->common_data->gateway_type = $jinput->get('gateway_type', '', 'STRING');
	$this->common_data->gateway_shortname = $jinput->get('gateway_shortname', '', 'STRING');
	$this->common_data->published = $jinput->get('published', 0, 'INT');
	$this->common_data->gateway_type = $jinput->get('gateway_type', '', 'STRING');
	$this->common_data->account_group = $jinput->get('account_group', 0, 'INT');
	$this->common_data->account_name = $jinput->get('account_name', '', 'STRING');
	$this->common_data->account_description = $jinput->get('account_description', '', 'RAW');   // Allow html
	$this->common_data->account_email = $jinput->get('account_email', '', 'STRING');
	$this->common_data->account_language = $jinput->get('account_language', '', 'STRING');
	$this->common_data->account_currency = $jinput->get('account_currency', '', 'STRING');
	$this->common_data->button_image = $jinput->get('button_image', '', 'STRING');
	$this->common_data->button_title = $jinput->get('button_title', '', 'STRING');
	$this->common_data->fee_min = $jinput->get('fee_min', '0', 'STRING');
	$this->common_data->fee_max = $jinput->get('fee_max', '0', 'STRING');
	$this->common_data->fee_type = $jinput->get('fee_type', '0', 'STRING');
	$this->common_data->fee_amount = $jinput->get('fee_amount', '0', 'STRING');
	$this->common_data->currency_symbol = $jinput->get('currency_symbol', '', 'STRING');
	$this->common_data->currency_format = $jinput->get('currency_format', 0, 'INT');
	$lang = JFactory::getLanguage('JPATH_SITE');
    $languages = $lang->getKnownLanguages();
	foreach ($languages as $tag => $name)
		{
        $this->translations[$tag]['button_title'] = $jinput->get($tag.'_button_title', '', 'STRING');
        $this->translations[$tag]['button_image'] = $jinput->get($tag.'_button_image', '', 'STRING');
        $this->translations[$tag]['account_description'] = $jinput->get($tag.'_account_description', '', 'STRING');
        }

	return $this->common_data;
}

//-------------------------------------------------------------------------------
// Store a record
//
function store()
{
	$this->common_data->specific_data = serialize($this->specific_data);	// the gateway specific data
	$this->common_data->translations = serialize($this->translations);	    // the language translations
    
    $query = $this->ladb_makeQuery($this->common_data, '#__payage_accounts');
			
	LAPG_trace::trace($query);
    
	$result = $this->ladb_execute($query);
	
	if ($result === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return false;
		}

	if ($this->common_data->id == 0)						// if it was an insert
		$this->common_data->id = $this->_db->insertId();	// get the new id

	return true;
}

//-------------------------------------------------------------------------------
// delete one or more accounts
// don't delete accounts with payments
//
function delete()
{
	$message = '';
	$jinput = JFactory::getApplication()->input;
	$cids = $jinput->get( 'cid', array(), 'ARRAY' );
	foreach ($cids as $cid)
		{
		$query = "select count(*) from `#__payage_payments` where account_id = '$cid'";
		$count = $this->ladb_loadResult($query);
		if ($count === false)
			{
			$this->app->enqueueMessage($this->ladb_error_text, 'error');
			return false;
			}
		if ($count != 0)
			{
			$message = JText::_("COM_PAYAGE_ACCOUNT_NO_DELETE_PAYMENT");
			continue;
			}

		$query = "delete from `#__payage_accounts` where id = '$cid'";
		$result = $this->ladb_execute($query);
		if ($result === false)
			{
			$this->app->enqueueMessage($this->ladb_error_text, 'error');
			return false;
			}
		}

	if ($message != '')
		{
		$this->app->enqueueMessage($message);
		return false;
		}

	return true;
}

//-------------------------------------------------------------------------------
// Get the list of accounts for the main account list screen
//
function &getList()
{
// get the filter and order states

	$jinput = JFactory::getApplication()->input;
	$filter_state = $this->app->getUserStateFromRequest('com_payage.account','filter_state','','word');

// build the query

	$query_count = "Select count(*) ";
	$query_cols  = "Select `id`, `published`, `account_name`, `account_group`, `gateway_type`, 
					`account_email`, `account_language`, `account_currency`, `button_image`, `button_title`,
					`fee_type`, `fee_amount`, `fee_min`, `fee_max`, `currency_symbol`, `currency_format` ";
	
	
	$query_from  = "From `#__payage_accounts` ";
	$query_where = "Where 1";

	if ($filter_state == 'P')
		{
		$query_where .= " And `published`=1";
		}

	if ($filter_state == 'U')
		{
		$query_where .= " And `published`=0";
		}

	$query_order = " Order by `account_name`";

// get the data

	$main_query = $query_cols.$query_from.$query_where.$query_order;
	$this->common_data = $this->ladb_loadObjectList($main_query);
	if ($this->common_data === false)
		$this->app->enqueueMessage($this->ladb_error_text, 'error');

	return $this->common_data;
}

//-------------------------------------------------------------------------------
// Get all data for all published accounts that match the group and currency specified
// - if group specified is zero it means get accounts of all groups
//
function &getAccounts($group,$currency)
{
	$query = "Select * From `#__payage_accounts` Where `published` = '1' And `account_currency` = '$currency'";
	if ($group != 0)
		$query .= " And `account_group` = $group";

	$data = $this->ladb_loadObjectList($query);
	
	if ($data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $data;
		}

	return $data;
}

//-------------------------------------------------------------------------------
// Get an array of all accounts for the dropdown account selector
//
function &get_account_array($currency = '0', $all=true)
{
    if ($currency == '0')
        $where = '';
    else
        $where = "Where `account_currency` = '$currency' ";
        
	$query = "Select `id`, `account_name`, `account_currency` From `#__payage_accounts` $where Order by `account_name`";

	$data = $this->ladb_loadObjectList($query);
	
	if ($data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $data;
		}

	$accounts = array();
    if ($all)
    	$accounts[0] = JText::_('COM_PAYAGE_ALL_ACCOUNTS');
	foreach ($data as $row)
		$accounts[$row->id] = $row->account_name.', '.$row->account_currency;

	return $accounts;
}

//-------------------------------------------------------------------------------
// Get a list of all the currencies we have accounts for, to make a select list
//
function &get_currency_array($group)
{
	$query = "Select Distinct(`account_currency`) From `#__payage_accounts` Where `published` = '1' ";
	if ($group != 0)
		$query .= " And `account_group` = $group";

	$data = $this->ladb_loadObjectList($query);
	
	if ($data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $data;
		}

	$accounts = array();
	foreach ($data as $row)
		$accounts[$row->account_currency] = $row->account_currency;

	return $accounts;
}

//-------------------------------------------------------------------------------
// Get a list of all the account groups we have accounts for
//
function &getGroups($include_all)
{
	$query = "Select Distinct(`account_group`) From `#__payage_accounts` Where `published` = '1' ";

	$data = $this->ladb_loadObjectList($query);
	
	if ($data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $data;
		}

	$accounts = array();
	
	if ($include_all)
		$accounts[0] = '0';
	
	foreach ($data as $row)
		$accounts[$row->account_group] = $row->account_group;

	return $accounts;
}

//-------------------------------------------------------------------------------
// $p is 0 if unpublishing, 1 if publishing
//
function publish($p)
{
	$jinput = JFactory::getApplication()->input;
	$cids = $jinput->get( 'cid', array(), 'ARRAY' );
	
	foreach($cids as $cid)
		{
		$result = $this->ladb_execute("UPDATE `#__payage_accounts` SET `published` = $p WHERE `id` = ".$cid);
		if ($result === false)
			{
			$this->app->enqueueMessage($this->ladb_error_text, 'error');
			return false;
			}
		}
	return true;
}

//-------------------------------------------------------------------------------
// Verify the amount, currency and recipient of a payment
// - set the payment status code and text accordingly
//
public function verify_payment(&$payment_data, $customer_fee, $gross_received, $currency_received, $receiver_email)
{
	$expected_payment_amount = $payment_data->gross_amount + $customer_fee;
	$str_expected_payment_amount = number_format($expected_payment_amount,2);
	$str_actual_payment_amount = number_format($gross_received,2);
	LAPG_trace::trace("verify_payment() gross_amount = $payment_data->gross_amount, customer_fee = $customer_fee, expected_payment_amount = $expected_payment_amount, gross_received = $gross_received");

	if ($str_expected_payment_amount != $str_actual_payment_amount)
		{
		$payment_data->pg_status_code = LAPG_STATUS_FAILED;
		$payment_data->pg_status_text = JText::sprintf("COM_PAYAGE_MISMATCH_AMOUNT",$str_expected_payment_amount, $str_actual_payment_amount);
		LAPG_trace::trace($payment_data->pg_status_text);
		}

	if ($this->common_data->account_currency != $currency_received)
		{
		$payment_data->pg_status_code = LAPG_STATUS_FAILED;
		$payment_data->pg_status_text = JText::sprintf("COM_PAYAGE_MISMATCH_CURRENCY",$currency, $currency_received);
		LAPG_trace::trace($payment_data->pg_status_text);
		}

	if ((strcasecmp($this->common_data->account_email, $receiver_email) != 0)
	and (strcasecmp($this->specific_data->account_primary_email, $receiver_email) != 0))
		{
		$payment_data->pg_status_code = LAPG_STATUS_FAILED;
		$payment_data->pg_status_text = JText::sprintf("COM_PAYAGE_MISMATCH_RECIPIENT",
			$account_data->account_email.' + '.$account_data->account_primary_email, $receiver_email);
		LAPG_trace::trace($payment_data->pg_status_text);
		}
}	

//-------------------------------------------------------------------------------
// Calculate the gateway surcharge fee
//
public static function calculate_gateway_fee($account_data, $amount)
{
	LAPG_trace::trace("calculate_gateway_fee: [Type ".$account_data->fee_type.": ".$account_data->fee_min." - ".$account_data->fee_amount." - ".$account_data->fee_max."] ".$amount);
	switch ($account_data->fee_type)
		{
		case LAPG_FEE_TYPE_NONE:
			return 0;
		case LAPG_FEE_TYPE_FIXED:
			return round($account_data->fee_amount,2);
		case LAPG_FEE_TYPE_PERCENT:
			$fee_amount = ($amount * $account_data->fee_amount) / 100;
			if ($fee_amount < $account_data->fee_min)
				return round($account_data->fee_min,2);
			if ($account_data->fee_max == 0)					// 0 means no maximum
				return round($fee_amount,2);
			if ($fee_amount > $account_data->fee_max)
				return round($account_data->fee_max,2);
			return round($fee_amount,2);
		default:
			return 0;
		}
}


}