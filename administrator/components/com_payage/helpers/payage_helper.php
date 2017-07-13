<?php
/********************************************************************
Product		: Payage
Date		: 2 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

define("LAPG_ADMIN_ASSETS_URL", JURI::root(true).'/administrator/components/com_payage/assets/');
define("LAPG_SITE_ASSETS_URL", JURI::root(true).'/components/com_payage/assets/');
define("LAPG_COMPONENT_LINK", "index.php?option=com_payage");

define("LAPG_FEE_TYPE_NONE", 0);
define("LAPG_FEE_TYPE_PERCENT", 1);
define("LAPG_FEE_TYPE_FIXED", 2);

define("LAPG_STATUS_NONE",     0);	// a button has been created but nothing has yet been received from the gateway
define("LAPG_STATUS_SUCCESS",  1);	// payment was made successfully
define("LAPG_STATUS_PENDING",  2);	// the payment was made but is uncleared
define("LAPG_STATUS_FAILED",   3);	// the user failed to pay OR the payment failed to verify
define("LAPG_STATUS_CANCELLED",4);	// the user got to the payment gateway and then hit cancel
define("LAPG_STATUS_REFUNDED", 5);	// the payment has been manually refunded in Payage
define("LAPG_STATUS_MAX",      5);	// the highest status value

define("LAPG_CALLBACK_NONE",   0);	// do not call a callback function
define("LAPG_CALLBACK_CANCEL", 1);	// the user got to the payment gateway and then hit cancel
define("LAPG_CALLBACK_USER",   2);	// the user has completed payment is being redirected back to the host site
define("LAPG_CALLBACK_UPDATE", 3);	// the gateway has provided a later update to a transaction

define("COM_PAYAGE_ERROR_BAD_GATEWAY", 1);

define("LAPG_MAX_LIST_ITEMS", 100);

define("LAPG_PLOTALOT_LINK", "http://www.lesarbresdesign.info/extensions/plotalot");

if (class_exists("PayageHelper"))
	return;

class PayageHelper
{

//-------------------------------------------------------------------------------
// Create an instance of the specific gateway model class
//
static function getGatewayInstance($gateway_type)
{
	$model = JPATH_ADMINISTRATOR.'/components/com_payage/models/'.strtolower($gateway_type).'.php';
	if (!file_exists($model))
		{
		LAPG_trace::trace("No file ".$model);
		return false;
		}
	require_once $model;
	$class_name = 'PayageModel'.$gateway_type;
	if (!class_exists($class_name))
		{
		LAPG_trace::trace("No class $class_name in ".$model);
		return false;
		}
	$gateway_model = new $class_name;
	if (!is_object($gateway_model))
		{
		LAPG_trace::trace("Unable to instantiate gateway model [$gateway_type]");
		return false;
		}
		
// load the gateway specific language file

	$lang = JFactory::getLanguage();
	$lang->load('com_payage_'.strtolower($gateway_type), JPATH_ADMINISTRATOR.'/components/com_payage');
	
	return $gateway_model;
}

//-------------------------------------------------------------------------------
// Return a description for a payment status
//
static function getPaymentDescription($status_code)
{
	switch ($status_code)
		{
		case LAPG_STATUS_NONE:
			return JText::_('JNONE');
		case LAPG_STATUS_SUCCESS:
			return JText::_('COM_PAYAGE_SUCCESS');
		case LAPG_STATUS_PENDING:
			return JText::_('COM_PAYAGE_PENDING');
		case LAPG_STATUS_FAILED:
			return JText::_('COM_PAYAGE_FAILED');
		case LAPG_STATUS_CANCELLED:
			return JText::_('COM_PAYAGE_CANCELLED');
		case LAPG_STATUS_REFUNDED:
			return JText::_('COM_PAYAGE_REFUNDED');
		default:
			return JText::_('COM_PAYAGE_UNKNOWN_STATUS');
		}
}

//-------------------------------------------------------------------------------
// Format a money amount
//
static function format_amount($number, $format = 0, $symbol = '')
{
	if (!is_numeric($number))
		return $number;
    if ($number == 0)
        return '0';
	switch ($format)
		{
		case 0:	 return number_format($number,2,'.','');
		case 1:	 return $symbol.number_format($number,2,'.',',');
		case 2:	 return $symbol.' '.number_format($number,2,'.','');
		case 3:	 return $symbol.number_format($number,2,'.',',');
		case 4:	 return $symbol.' '.number_format($number,2,'.',',');
		case 5:	 return $symbol.number_format($number,2,',','.');
		case 6:	 return $symbol.' '.number_format($number,2,',','.');
		case 7:	 return $symbol.number_format($number,2,',',' ');
		case 8:	 return $symbol.' '.number_format($number,2,',',' ');
		case 9:	 return $symbol.number_format($number,2,'.',' ');
		case 10: return number_format($number,2,'.',',').$symbol;
		case 11: return number_format($number,2,'.',',').' '.$symbol;
		case 12: return number_format($number,2,',','.').$symbol;
		case 13: return number_format($number,2,',','.').' '.$symbol;
		case 14: return number_format($number,0,'.','');
		case 15: return $symbol.number_format($number,0,'.','');
		case 16: return $symbol.' '.number_format($number,0,'.','');
		case 17: return number_format($number,0,'.','').$symbol;
		case 18: return number_format($number,0,'.','').' '.$symbol;
		case 19: return number_format($number,0,',','');
		}
}

//-------------------------------------------------------------------------------
// Get the component version
//
static function getComponentVersion()
{
	$xml_array = JInstaller::parseXMLInstallFile(JPATH_ADMINISTRATOR.'/components/com_payage/payage.xml');
	return $xml_array['version'];
}	

//-------------------------------------------------------------------------------
// Load Payage's main language file from the admin side
//
static function loadLanguageFile()
{
	$lang = JFactory::getLanguage();
	$lang->load('com_payage', JPATH_ADMINISTRATOR.'/components/com_payage/');
}

// -------------------------------------------------------------------------------
// Cleanup a string if we can, reject it if we can't
//
static function clean_string(&$str)
{
$bad_chars = "&|%<>#";								// characters we don't allow

	$str = trim(str_replace('"',"'",$str));			// trim and replace double quotes with single quotes
	if (strpbrk($str, $bad_chars))
    	return false;								// reject if $str contains any $bad_chars
	return true;
}

//-------------------------------------------------------------------------------
// Return true if supplied argument is numeric
//
static function is_number($arg, $allow_blank=true)
{
	if ($arg == '')
		{
		if ($allow_blank)
			return true;
		else
			return false;
		}
	if (is_numeric($arg))
		return true;
	return false;
}

//-------------------------------------------------------------------------------
// Return true if supplied argument is a positive integer, else false
//
static function is_posint($arg, $allow_blank=true)
{
	if ($arg == '')
		{
		if ($allow_blank)
			return true;
		else
			return false;
		}
	if (!is_numeric($arg))
		return false;
	if ((intval($arg) == $arg) and ($arg >= 0))
		return true;
	else
		return false;
}

//-------------------------------------------------------------------------------
// Get client's IP address
//
static function getIPaddress()
{
	if (isset($_SERVER["REMOTE_ADDR"]))
		return $_SERVER["REMOTE_ADDR"];
	if (isset($_SERVER["HTTP_X_FORWARDED_FOR"]))
		return $_SERVER["HTTP_X_FORWARDED_FOR"];
	if (isset($_SERVER["HTTP_CLIENT_IP"]))
		return $_SERVER["HTTP_CLIENT_IP"];
	return '';
} 

//-------------------------------------------------------------------------------
// Send an email
//
static function send_email($email_to, $subject, $body_text)
{
	$app = JFactory::getApplication();
	$mailer = JFactory::getMailer();
	$mailer->IsHTML(true);
	$mailer->setSender(array($app->get('mailfrom'), 'Payage'));
	$mailer->setSubject($subject);
	$mailer->setBody($body_text);
	$mailer->addRecipient($email_to);
	$mailer->Send();
}


}