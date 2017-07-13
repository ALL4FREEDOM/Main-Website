<?php
/********************************************************************
Product		: Payage
Date		: 2 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

define("LAPG_TRACE_FILE_NAME", 'trace.txt');
define("LAPG_TRACE_FILE_PATH", JPATH_ROOT.'/components/com_payage/trace.txt');
define("LAPG_TRACE_FILE_URL", JURI::root().'components/com_payage/trace.txt');
define("LAPG_MAX_TRACE_SIZE", 2000000);	// about 2Mb
define("LAPG_MAX_TRACE_AGE",   21600);		// maximum trace file age in seconds (6 hours)
define("LAPG_UTF8_HEADER",     "\xEF\xBB\xBF");	// UTF8 file header

if (class_exists("LAPG_trace"))
	return;

class LAPG_trace
{

//-------------------------------------------------------------------------------
// Write an entry to the trace file
// Tracing is ON if the trace file exists
// if $no_time is true, the date time is not added
//
static function trace($data)
{
	if (@!file_exists(LAPG_TRACE_FILE_PATH))
		return;
	if (filesize(LAPG_TRACE_FILE_PATH) > LAPG_MAX_TRACE_SIZE)
		{
		@unlink(LAPG_TRACE_FILE_PATH);
		@file_put_contents(LAPG_TRACE_FILE_PATH, LAPG_UTF8_HEADER.date("d/m/y H:i").' New trace file created'."\n");
		}
	@file_put_contents(LAPG_TRACE_FILE_PATH, $data."\n",FILE_APPEND);
}

//-------------------------------------------------------------------------------
// Start a new trace file
//
static function init_trace($gateway_list)
{
	self::delete_trace_file();
	@file_put_contents(LAPG_TRACE_FILE_PATH, LAPG_UTF8_HEADER.date("d/m/y H:i").' Tracing Initialised'."\n");
	
	$locale = setlocale(LC_ALL,0);
	$locale_string = print_r($locale, true);
	$langObj = JFactory::getLanguage();
	$language = $langObj->get('tag');
	$php_version = phpversion();
	$app = JFactory::getApplication();
    if (function_exists('curl_init'))
		{
        $curl_info = curl_version();
		$curl_version = $curl_info['version']; // curl 7.34.0 was the first to claim support for TLSv1.2
        }
    else
        $curl_version = 'Not installed';

	self::trace('Payage version : '.self::getComponentVersion());
	self::trace("PHP version      : ".PHP_VERSION);
	self::trace("PHP locale       : ".$locale_string);
	self::trace("Server           : ".PHP_OS);
	self::trace("Joomla version   : ".JVERSION);
	self::trace("Open SSL version : ".OPENSSL_VERSION_TEXT);
	self::trace("CURL version     : ".$curl_version);
	self::trace("Joomla language  : ".$language);
	self::trace("JPATH_SITE       : ".JPATH_SITE);
	self::trace("JURI::root()     : ".JURI::root());
	self::trace("Config live_site : ".$app->get('live_site'));
	self::trace("Gateway List: ".print_r($gateway_list,true));
}

//-------------------------------------------------------------------------------
// Trace an entry point
// Tracing is ON if the trace file exists
//
static function trace_entry_point($front=false)
{
	if (@!file_exists(LAPG_TRACE_FILE_PATH))
		return;
		
// if the trace file is more than 6 hours old, delete it, which will switch tracing off
//  - we don't want trace to be left on accidentally

	$filetime = @filectime(LAPG_TRACE_FILE_PATH);
	if (time() > ($filetime + LAPG_MAX_TRACE_AGE))
		{
		self::delete_trace_file();
		return;
		}
		
	$date_time = date("d/m/y H:i").' ';	
	
	if ($front)
		self::trace("\n".$date_time.'================================ [Front Entry Point] ================================');
	else
		self::trace("\n".$date_time.'================================ [Admin Entry Point] ================================');
		
	if ($front)
		{
		if (isset($_SERVER["REMOTE_ADDR"]))
			$ip_address = '('.$_SERVER["REMOTE_ADDR"].')';
		else
			$ip_address = '';

		if (isset($_SERVER["HTTP_USER_AGENT"]))
			$user_agent = $_SERVER["HTTP_USER_AGENT"];
		else
			$user_agent = '';

		if (isset($_SERVER["HTTP_REFERER"]))
			$referer = $_SERVER["HTTP_REFERER"];
		else
			$referer = '';
			
		$method = $_SERVER['REQUEST_METHOD'];

		self::trace("$method from $ip_address $user_agent");
		if ($referer != '')
			self::trace('Referer: '.$referer, true);
		}

	if (!empty($_POST))
		self::trace("Post data: ".print_r($_POST,true));
	if (!empty($_GET))
		self::trace("Get data: ".print_r($_GET,true));
}

//-------------------------------------------------------------------------------
// Delete the trace file
//
static function delete_trace_file()
{
	if (@file_exists(LAPG_TRACE_FILE_PATH))
		@unlink(LAPG_TRACE_FILE_PATH);
}

//-------------------------------------------------------------------------------
// Return true if tracing is currently active
//
static function tracing()
{
	if (@file_exists(LAPG_TRACE_FILE_PATH))
		return true;
	else
		return false;
}

//-------------------------------------------------------------------------------
// Make the html for the help and support page
// The controller must contain the trace_on() and trace_off() functions
//
static function make_trace_controls()
{
	$html = '<div>';
	$html .= 'Diagnostic Trace Mode: ';
    $html .= LAPG_view::make_info('Create a trace file to send to support. Please remember to switch off after use.');
    $onclick = ' onclick="document.adminForm.task.value=\'trace_on\'; document.adminForm.submit();"';
    $html .= ' <button class="btn"'.$onclick.'>On</button>';
	$onclick = ' onclick="document.adminForm.task.value=\'trace_off\'; document.adminForm.submit();"';
    $html .= ' <button class="btn"'.$onclick.'>Off</button>';

	if (file_exists(LAPG_TRACE_FILE_PATH))
		$html .= ' <a href="'.LAPG_TRACE_FILE_URL.'" target="_blank"> Trace File</a>';
	else
		$html .= ' Tracing is currently OFF';

	$html .= '</div>';
	return $html;
}

//-------------------------------------------------------------------------------
// Get the component version from the component manifest XML file
//
static function getComponentVersion()
{
	$xml_array = JInstaller::parseXMLInstallFile(JPATH_ADMINISTRATOR.'/components/com_payage/payage.xml');
	return $xml_array['version'];
}

} // class