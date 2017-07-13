<?php
/********************************************************************
Product		: Payage
Date		: 2 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class com_PayageInstallerScript
{
public function preflight($type, $parent) 
{
	$version = new JVersion();  			// get the Joomla version (JVERSION did not exist before Joomla 2.5)
	$joomla_version = $version->RELEASE.'.'.$version->DEV_LEVEL;
	$app = JFactory::getApplication();

	if (version_compare($joomla_version,"3.4.8","<"))
		{
        $app->enqueueMessage("Payage requires at least Joomla 3.4.8", 'error');
		return false;
		}
		
	if (get_magic_quotes_gpc())
		{
        $app->enqueueMessage("Payage cannot run with PHP Magic Quotes ON. Please switch it off and re-install.", 'error');
		return false;
		}
		
	$app = JFactory::getApplication();
	$dbtype = $app->get('dbtype');
	if (!strstr($dbtype,'mysql'))
		{
        $app->enqueueMessage("Payage currently only supports MYSQL databases. It cannot run with $dbtype", 'error');
		return false;
		}

// if we are upgrading from a version of Payage prior to 1.05, it may have been the consolidated version,
// in which case the Joomla installer will remove the addon xml files....

	$this->previous_payage_version = false;
	if (file_exists(JPATH_ADMINISTRATOR.'/components/com_payage/payage.xml'))
		{
		$xml_array = JInstaller::parseXMLInstallFile(JPATH_ADMINISTRATOR.'/components/com_payage/payage.xml');
		$this->previous_payage_version = $xml_array['version'];
		}
		
	return true;
}

public function uninstall($parent)
{ 
    $app = JFactory::getApplication();
    $app->enqueueMessage("Payage has been uninstalled. The Payage database tables were NOT deleted.", 'message');
}

//-------------------------------------------------------------------------------
// The main install function
//
public function postflight($type, $parent)
{
    $app = JFactory::getApplication();
    
// check the PHP version

	if (version_compare(PHP_VERSION,"5.3.0","<"))
        $app->enqueueMessage("Warning: Payage has not been tested on this old version of PHP (".PHP_VERSION.")", 'notice');
		
// we don't support the Hathor template

	$template = JFactory::getApplication()->getTemplate();
    if ($template == 'hathor')
        $app->enqueueMessage("Payage does not support the Hathor administrative template. Please use a different template", 'error');
	
// check the Joomla version

	if (substr(JVERSION,0,1) > "3")				// if > 3
        $app->enqueueMessage("This version of Payage has not been tested on this version of Joomla", 'notice');
	
// get the component version from the component manifest xml file		

	$component_version = $parent->get('manifest')->version;
    
// delete redundant files from older versions

	@unlink(JPATH_SITE.'/administrator/components/com_payage/controllers/helpcontroller.php');
	@unlink(JPATH_SITE.'/administrator/components/com_payage/assets/eye.png');

    self::deleteViews(array('help','payment_list','payment_detail','account_choice','account_edit','account_list'));
    
// create our database tables - this will display an error if it fails

	$this->_db = JFactory::getDBO();
	$this->ladb_execute("CREATE TABLE IF NOT EXISTS `#__payage_accounts` (
		  `id` int(11) NOT NULL AUTO_INCREMENT,
		  `published` tinyint(4) NOT NULL DEFAULT '1',
		  `gateway_type` varchar(32) NOT NULL DEFAULT '',
		  `gateway_shortname` varchar(20) NOT NULL DEFAULT '',
		  `account_group` int(11) NOT NULL DEFAULT '1',
		  `account_name` varchar(60) NOT NULL DEFAULT '',
		  `account_description` text NOT NULL DEFAULT '',
		  `account_email` varchar(80) NOT NULL DEFAULT '',
		  `account_language` char(7) NOT NULL DEFAULT '',
		  `account_currency` char(3) NOT NULL DEFAULT '',
		  `button_image` varchar(255) NOT NULL DEFAULT '',
		  `button_title` varchar(255) NOT NULL DEFAULT '',
		  `fee_type` smallint(6) NOT NULL DEFAULT 0,
		  `fee_amount` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `fee_min` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `fee_max` decimal(10,2) NOT NULL DEFAULT '0.00',
		  `currency_symbol` char(3) NOT NULL DEFAULT '',
		  `currency_format` smallint(6) NOT NULL,
		  `specific_data` text NOT NULL,
		  `translations` text NOT NULL DEFAULT '',
		  PRIMARY KEY (`id`)
		) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1000 ;");

	$this->ladb_execute("CREATE TABLE IF NOT EXISTS `#__payage_payments` (
		  `id` int(11) NOT NULL auto_increment,
		  `date_time_initiated` timestamp NOT NULL default CURRENT_TIMESTAMP,
		  `date_time_updated` timestamp NOT NULL default 0,
		  `account_id` int(11) NOT NULL,
		  `pg_transaction_id` char(32) NOT NULL,
		  `pg_status_code` smallint(6) NOT NULL,
		  `pg_status_text` varchar(255) NOT NULL,
		  `pg_history` text NOT NULL,
		  `app_name` varchar(255) NOT NULL,
		  `app_return_url` varchar(255) NOT NULL,
		  `app_update_path` varchar(255) NOT NULL,
		  `app_transaction_id` varchar(255) NOT NULL,
		  `app_transaction_details` text NOT NULL,
		  `gw_transaction_id` varchar(255) NOT NULL,
		  `gw_pending_reason` varchar(20) NOT NULL,
		  `gw_transaction_details` text NOT NULL,
		  `item_name` varchar(255) NOT NULL,
		  `currency` char(3) NOT NULL DEFAULT '',
		  `gross_amount` float NOT NULL,
		  `tax_amount` float NOT NULL,
		  `customer_fee` float NOT NULL,
		  `gateway_fee` float NOT NULL,
		  `payer_email` varchar(255) NOT NULL,
		  `payer_first_name` varchar(80) NOT NULL,
		  `payer_last_name` varchar(80) NOT NULL,
		  `payer_address1` varchar(80) NOT NULL,
		  `payer_address2` varchar(80) NOT NULL,
		  `payer_city` varchar(25) NOT NULL,
		  `payer_state` varchar(25) NOT NULL,
		  `payer_zip_code` varchar(25) NOT NULL,
		  `payer_country` char(3) NOT NULL,
		  `client_ip` varchar(32) NOT NULL,
		  `client_ua` varchar(255) NOT NULL,
		  `processed` tinyint(4) NOT NULL,
		  PRIMARY KEY (`id`),
          UNIQUE KEY `pg_transaction_id` (`pg_transaction_id`)
          ) ENGINE=MyISAM  DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=1 ;");

// alter tables

	$this->ladb_execute_ignore("ALTER TABLE `#__payage_accounts` CHANGE  `account_language` `account_language` CHAR(7) NOT NULL DEFAULT ''");
	$this->ladb_execute_ignore("ALTER TABLE  `#__payage_payments` ADD  `client_ip` VARCHAR(32) NOT NULL DEFAULT ''");
	$this->ladb_execute_ignore("ALTER TABLE  `#__payage_payments` ADD  `client_ua` VARCHAR(255) NOT NULL DEFAULT ''");
	$this->ladb_execute_ignore("ALTER TABLE  `#__payage_payments` ADD  `processed` TINYINT(4) NOT NULL DEFAULT '0'");
	$this->ladb_execute_ignore("ALTER TABLE  `#__payage_accounts` ADD  `translations` text NOT NULL DEFAULT ''");   // 2.00

// Install the FaLang translation files

	$this->add_Language();

    $app->enqueueMessage("Payage $component_version installed.", 'message');
    	
// if we are upgrading from a version of Payage prior to 1.05, it may have been the consolidated version,
// in which case the Joomla installer will remove the addon xml files....

	if ( ($this->previous_payage_version !== false) and (version_compare($this->previous_payage_version,"1.6","<")) )
        $app->enqueueMessage("Please re-install your Payage gateway addons", 'message');
    else
        $app->enqueueMessage("Please now install the gateway addons you require.", 'message');

	return true;
}

//-------------------------------------------------------------------------------
// Delete one or more back end views
//
static function deleteViews($views)
{
    foreach ($views as $view)
        {
        @unlink(JPATH_SITE."/administrator/components/com_payage/views/$view/index.html");
        @unlink(JPATH_SITE."/administrator/components/com_payage/views/$view/view.html.php");
        @rmdir (JPATH_SITE."/administrator/components/com_payage/views/$view");
        }
}

//-------------------------------------------------------------------------------
// Install the Falang Language Files
//
function add_Language()
{
	if (file_exists(JPATH_SITE.'/administrator/components/com_falang/falang.php'))
		@rename( JPATH_SITE."/administrator/components/com_payage/falang/payage_accounts.xml", JPATH_SITE."/administrator/components/com_falang/contentelements/payage_accounts.xml"); 
	else
		@unlink( JPATH_SITE."/administrator/components/com_payage/falang/payage_accounts.xml"); 
}

//-------------------------------------------------------------------------------
// Execute a SQL query and return true if it worked, false if it failed
//
function ladb_execute($query)
{
	try
		{
		$this->_db->setQuery($query);
		$this->_db->execute();
		}
	catch (RuntimeException $e)
		{
        $message = $e->getMessage();
    	$app = JFactory::getApplication();
        $app->enqueueMessage($message, 'error');
		return false;
		}
	return true;
}

//-------------------------------------------------------------------------------
// Execute a SQL query ignoring any errors
//
function ladb_execute_ignore($query)
{
	try
		{
		$this->_db->setQuery($query);
		$this->_db->execute();
		}
	catch (RuntimeException $e)
		{
		return;
		}
	return;
}



}
