<?php
/********************************************************************
Product		: Payage
Date		: 15 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');
jimport('joomla.html.pagination');

class PayageModelPayment extends LAPG_model
{
var $app = null;
var $data = null;
var $pagination = null;

function __construct()
{
	parent::__construct();
	$this->app = JFactory::getApplication();
}

//-------------------------------------------------------------------------------
// initialise the data for a new transaction
//
function initData()
{
	$this->data = new stdClass();
	$this->data->id = 0;
	$this->data->date_time_initiated = 0;
	$this->data->date_time_updated = 0;
	$this->data->account_id = 0;
	$this->data->pg_transaction_id = 0;
	$this->data->pg_status_code = 0;		// a Payage status code, e.g. LAPG_STATUS_SUCCESS
	$this->data->pg_status_text = '';
	$this->data->pg_history = '';
	$this->data->app_name = '';
	$this->data->app_return_url = '';
	$this->data->app_update_path = '';
	$this->data->app_transaction_id = 0;
	$this->data->app_transaction_details = '';
	$this->data->gw_transaction_id = 0;
	$this->data->gw_pending_reason = '';
	$this->data->gw_transaction_details = '';
	$this->data->item_name = '';
	$this->data->currency = '';
	$this->data->gross_amount = 0;
	$this->data->tax_amount = 0;
	$this->data->customer_fee = 0;
	$this->data->gateway_fee = 0;
	$this->data->payer_email = '';
	$this->data->payer_first_name = '';
	$this->data->payer_last_name = '';
	$this->data->payer_address1 = '';
	$this->data->payer_address2 = '';
	$this->data->payer_city = '';
	$this->data->payer_state = '';
	$this->data->payer_zip_code = '';
	$this->data->payer_country = '';
	$this->data->client_ip = PayageHelper::getIPaddress();
    if (isset($_SERVER["HTTP_USER_AGENT"]))
	    $this->data->client_ua = $_SERVER["HTTP_USER_AGENT"];
    else
        $this->data->client_ua = '';
	$this->data->processed = 0;
	return $this->data;
}

//-------------------------------------------------------------------------------
// get an existing row
// - this can be called by the API so cannot enqueue GUI messages
//
function &getOne($index, $field='id')
{
	$query = "SELECT *, NOW() AS now FROM `#__payage_payments` WHERE $field = ".$this->_db->Quote($index);
	$this->data = $this->ladb_loadObject($query);
	if (empty($this->data))
		{
		LAPG_trace::trace("getOne did not find payment $index ($field)");
		$this->data = false;
		return $this->data;
		}
	$this->data->app_transaction_details = unserialize($this->data->app_transaction_details);
	$this->data->gw_transaction_details = unserialize($this->data->gw_transaction_details);
	return $this->data;
}

//-------------------------------------------------------------------------------
// get an existing row with some account data
// - this can be called by the API so cannot enqueue GUI messages
//
function &getOnePlus($index, $field='id')
{
	$query = "SELECT P.*, NOW() AS now, A.`account_name`, A.`gateway_shortname` as gateway_name 
		FROM `#__payage_payments` AS P
		LEFT OUTER JOIN #__payage_accounts AS A 
						ON P.account_id = A.id
		WHERE $field = ".$this->_db->Quote($index);
	$this->data = $this->ladb_loadObject($query);
	if (empty($this->data))
		{
		LAPG_trace::trace("getOnePlus did not find payment $index ($field)");
		$this->data = false;
		return $this->data;
		}
	$this->data->app_transaction_details = unserialize($this->data->app_transaction_details);
	$this->data->gw_transaction_details = unserialize($this->data->gw_transaction_details);
	return $this->data;
}

//-------------------------------------------------------------------------------
// Store a record
// - this can be called by the API so cannot enqueue GUI messages
//
function store()
{
	$this->data->app_transaction_details = serialize($this->data->app_transaction_details);
	$this->data->gw_transaction_details = serialize($this->data->gw_transaction_details);

    unset($this->data->date_time_initiated);    // defaults to CURRENT_TIMESTAMP, never updated
    unset($this->data->now);                    // not a real column in the table
    $this->data->date_time_updated = 'CURRENT_TIMESTAMP';
    
    $query = $this->ladb_makeQuery($this->data, '#__payage_payments', array('date_time_updated'));
    				
	LAPG_trace::trace($query);
    
	$result = $this->ladb_execute($query);
	
	if ($result === false)
		{
		LAPG_trace::trace($this->ladb_error_text);
		return false;
		}

	if ($this->data->id == 0)						// if it was an insert
		$this->data->id = $this->_db->insertId();	// get the new id

	return true;
}

//-------------------------------------------------------------------------------
// delete one or more payments
//
function delete()
{
	$message = '';
	$jinput = JFactory::getApplication()->input;
	$cids = $jinput->get( 'cid', array(), 'ARRAY' );
	foreach ($cids as $cid)
		{
		$query = "delete from `#__payage_payments` where id = '$cid'";
		$result = $this->ladb_execute($query);
		if ($result === false)
			{
			LAPG_trace::trace($this->ladb_error_text);
			return false;
			}
		}

	return true;
}

//-------------------------------------------------------------------------------
// change the status of the current payment
// - this can be called by the API so cannot enqueue GUI messages
//
function change_status($new_status, $extra='')
{	
	$old_status_description = PayageHelper::getPaymentDescription($this->data->pg_status_code);
	$new_status_description = PayageHelper::getPaymentDescription($new_status);
	
	$result = $this->ladb_execute("UPDATE `#__payage_payments` 
		SET `pg_status_code` = '$new_status',
			`pg_status_text` = '',
			`gw_pending_reason` = '',
		    `pg_history` = ".$this->_db->Quote($this->data->pg_history."\n".$this->data->now." - ".$extra.$old_status_description." -> ".$new_status_description).
		" WHERE `id` = ".$this->_db->Quote($this->data->id));
		
	if ($result === false)
		{
		LAPG_trace::trace($this->ladb_error_text);
		return false;
		}

	return true;
}

//-------------------------------------------------------------------------------
// set the current payment to processed by the application
// - this can be called by the API so cannot enqueue GUI messages
//
function set_processed($value)
{	
	$result = $this->ladb_execute("UPDATE `#__payage_payments` SET `processed` = '$value',
		    `pg_history` = ".$this->_db->Quote($this->data->pg_history."\n".$this->data->now." - ".JText::_('COM_PAYAGE_PROCESSED_BY').' '.$this->data->app_name).
		" WHERE `id` = ".$this->_db->Quote($this->data->id));
		
	if ($result === false)
		{
		LAPG_trace::trace($this->ladb_error_text);
		return false;
		}

	return true;
}

//-------------------------------------------------------------------------------
// Return a pointer to our pagination object
// This should normally be called after getList()
//
function &getPagination()
{
	if ($this->pagination == Null)
		$this->pagination = new JPagination(0,0,0);
	return $this->pagination;
}

//-------------------------------------------------------------------------------
// Get the list of payments for the payments list screen
// - we don't show payments with status zero on the payments list screen
//
function &getList()
{
    $component = JComponentHelper::getComponent('com_installer');
    $params = $component->params;
    $cache_timeout = $params->get('cachetimeout', 6, 'int');
    if ($cache_timeout == 0)
        $this->ladb_execute("update `#__update_sites` set `enabled` = 0 where `name` like '%Payage%'");
        
// get the filter states and the pagination variables

	$limit             = $this->app->getUserStateFromRequest('global.list.limit', 'limit', $this->app->get('list_limit'),'int');
	$limitstart        = $this->app->getUserStateFromRequest('com_payage.payment', 'limitstart', 0, 'int');
	$limitstart        = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0); // In case limit has been changed
	$filter_order      = $this->app->getUserStateFromRequest('com_payage.payment_filter_order', 'filter_order', 'date_time');
	$filter_order_Dir  = $this->app->getUserStateFromRequest('com_payage.payment_filter_order_Dir', 'filter_order_Dir', 'desc');
	$search            = $this->app->getUserStateFromRequest('com_payage.payment_search','search','','RAW');
    $today = date('Y-m-d');
    $one_week_ago = date('Y-m-d', strtotime('-1 week'));
	$filter_start_date = $this->app->getUserStateFromRequest('com_payage.payment_filter_start_date','filter_start_date',$one_week_ago,'STRING');
	$filter_end_date   = $this->app->getUserStateFromRequest('com_payage.payment_filter_end_date','filter_end_date',$today,'STRING');
	$filter_app        = $this->app->getUserStateFromRequest('com_payage.payment_filter_app','filter_app',0,'STRING');
	$filter_currency   = $this->app->getUserStateFromRequest('com_payage.payment_filter_currency','filter_currency','0','string');
	$filter_account    = $this->app->getUserStateFromRequest('com_payage.payment_filter_account','filter_account',0,'int');

// build the query

	$query_count = "Select count(*) ";
	$query_cols  = "Select P.`id`, `date_time_initiated`, P.`pg_status_code`, P.`app_name`, P.`item_name`,
					P.`payer_first_name`, P.`payer_last_name`, P.`payer_email`, P.`currency`, P.`gross_amount`, P.`tax_amount`, 
					P.`customer_fee`, A.`account_name`, A.`currency_format`, A.`currency_symbol` ";
	$query_from  = "From `#__payage_payments` AS P
					LEFT OUTER JOIN `#__payage_accounts` as A ON P.`account_id` = A.`id` ";
	$query_where = "Where P.`pg_status_code` != 0 ";
	$query_order = " Order by `date_time_initiated` Desc";

// search
// only include other filters if search is blank

	if ($search != '')
		$query_where .= $this->make_search($search);
    else
        {
        $query_where .= " AND DATE(`date_time_initiated`) >= ".$this->_db->Quote($filter_start_date)." AND DATE(`date_time_initiated`) <= ".$this->_db->Quote($filter_end_date);
        if ($filter_currency != '0')
            $query_where .= " AND `currency` = ".$this->_db->Quote($filter_currency);
    
        if ($filter_app != '0')
            $query_where .= " AND `app_name` = ".$this->_db->Quote($filter_app);
    
        if ($filter_account != 0)
            $query_where .= " AND `account_id` = ".$this->_db->Quote($filter_account);
        }

// order by

	switch ($filter_order)							// validate column name
		{
		case 'app_name':
		case 'item_name':
		case 'account_name':
		case 'payer_last_name':
		case 'payer_email':
			break;
		default:
			$filter_order = 'date_time_initiated';
		}

	if (strcasecmp($filter_order_Dir,'ASC') != 0)	// validate 'asc' or 'desc'
		$filter_order_Dir = 'DESC';

	$query_order = " ORDER BY `".$filter_order.'` '.$filter_order_Dir;

// get the total row count and initialise pagination

	$count_query = $query_count.$query_from.$query_where;
    LAPG_trace::trace($count_query);
	$total = $this->ladb_loadResult($count_query);
	if ($total === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text,'error');
		return $total;
		}

	if ($limitstart > $total)
		$limitstart = 0;

	$this->pagination = new JPagination($total, $limitstart, $limit);

// Get the data, within the limits required

	$main_query = $query_cols.$query_from.$query_where.$query_order;
    LAPG_trace::trace($main_query);
	$this->data = $this->ladb_loadObjectList($main_query, $limitstart, $limit);
	if ($this->data === false)
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
	return $this->data;
}

//-------------------------------------------------------------------------------
// Get the list of pending payments, i.e with status zero
//
function &getPendingList()
{
    $this->purge();     // purge expired pending payments
    
// get the pagination variables

	$limit      = $this->app->getUserStateFromRequest('global.list.limit', 'limit', $this->app->get('list_limit'),'int');
	$limitstart = $this->app->getUserStateFromRequest('com_payage.pending', 'limitstart', 0, 'int');
	$limitstart = ($limit != 0 ? (floor($limitstart / $limit) * $limit) : 0); // In case limit has been changed

// build the query

	$query_count = "Select count(*) ";
	$query_cols  = "Select `id`, `date_time_initiated`, `app_name`, `item_name`,
					`client_ip`, `client_ua`, `currency`, `gross_amount`, `tax_amount` ";
	$query_from  = "From `#__payage_payments` ";
	$query_where = "Where `pg_status_code` = 0 ";
	$query_order = "Order By `date_time_initiated` Desc";

// get the total row count and initialise pagination

	$count_query = $query_count.$query_from.$query_where;
	$total = $this->ladb_loadResult($count_query);
	if ($total === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text,'error');
		return $total;
		}

	if ($limitstart > $total)
		$limitstart = 0;

	$this->pagination = new JPagination($total, $limitstart, $limit);

// Get the data, within the limits required

	$main_query = $query_cols.$query_from.$query_where.$query_order;
	$this->data = $this->ladb_loadObjectList($main_query, $limitstart, $limit);
	if ($this->data === false)
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
	return $this->data;
}

//-------------------------------------------------------------------------------
// Make the where clause for the search string
//
function make_search($search)
{
// if it's exactly 32 characters, assume it's our transaction ID

	if (strlen($search) == 32)
		{
		$tid = $this->_db->Quote('%'.$this->_db->escape($search,true).'%',false);
		return " AND LOWER(P.`pg_transaction_id`) LIKE LOWER($tid)";
		}
		
// any other string searches first name, last name, and email address

	$search_like = $this->_db->Quote('%'.$this->_db->escape($search,true).'%',false);
	
	return " AND LOWER(P.`payer_last_name`) LIKE LOWER($search_like)
				OR LOWER(P.`payer_first_name`) LIKE LOWER($search_like)
				OR LOWER(P.`payer_email`) LIKE LOWER($search_like)";
}

//-------------------------------------------------------------------------------
// Get a list of all the applications that we have payments for
//
function &get_app_array()
{
	$query = "Select Distinct(`app_name`) From `#__payage_payments` Where `pg_status_code` != ".LAPG_STATUS_NONE;

	$data = $this->ladb_loadObjectList($query);
    	
	if ($data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $data;
		}

	$apps = array();
	$apps[0] = JText::_('COM_PAYAGE_ALL_APPLICATIONS');
    
    if (count($data) > LAPG_MAX_LIST_ITEMS)
        return $apps;
        
	foreach ($data as $row)
		$apps[$row->app_name] = $row->app_name;

	return $apps;
}

//-------------------------------------------------------------------------------
// Get currencies that we have payments for
//
function get_currency_array($all=true)
{
    $query = "SELECT DISTINCT(`currency`) FROM `#__payage_payments`
        WHERE `pg_status_code` IN (".LAPG_STATUS_SUCCESS.", ".LAPG_STATUS_PENDING.")"."
        ORDER BY `currency`";
	$data = $this->ladb_loadObjectList($query);
	
	if ($data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $data;
		}

	$currencies = array();
    if ($all)
    	$currencies[0] = JText::_('COM_PAYAGE_ALL_CURRENCIES');
	foreach ($data as $row)
		$currencies[$row->currency] = $row->currency;

	return $currencies;
}

//-------------------------------------------------------------------------------
// Delete unwanted payment records
//
function purge()
{
	$params = JComponentHelper::getParams('com_payage');		// get component parameters
	
	$time_to_keep_unconfirmed = $params->get('time_to_keep_unconfirmed',20);
	$query = "DELETE FROM `#__payage_payments` WHERE `pg_status_code` IN (".LAPG_STATUS_NONE.",".LAPG_STATUS_CANCELLED.") AND TIMESTAMPDIFF(MINUTE,`date_time_initiated`,CURRENT_TIMESTAMP) > $time_to_keep_unconfirmed";
	LAPG_trace::trace($query);
	$result = $this->ladb_execute($query);
	if ($result === false)
		LAPG_trace::trace($this->ladb_error_text);

	$time_to_keep_confirmed = $params->get('time_to_keep_confirmed',0);
	if ($time_to_keep_confirmed != 0)
		{
		$query = "DELETE FROM `#__payage_payments` WHERE `pg_status_code` NOT IN (".LAPG_STATUS_NONE.",".LAPG_STATUS_CANCELLED.") AND TIMESTAMPDIFF(DAY,`date_time_initiated`,CURRENT_TIMESTAMP) > $time_to_keep_confirmed";
		LAPG_trace::trace($query);
		$result = $this->ladb_execute($query);
		if ($result === false)
			LAPG_trace::trace($this->ladb_error_text);
		}
}

//-------------------------------------------------------------------------------
// add a new set of gateway transaction details to a payment record
// the first set of details is simply added as an object. The store() function will serialize it.
// subsequent sets of data are appended as "Update 1", "Update2", etc.
//
function add_transaction_details($obj)
{
	if (empty($this->data->gw_transaction_details))
		{
		$this->data->gw_transaction_details = $obj;
		return;
		}
	for ($i=1; $i<99; $i++)
		{
		$varname = "Update".$i;
		if (!isset($this->data->gw_transaction_details->$varname))
			break;
		}
	if ($i >= 99)
		{
		LAPG_trace::trace("Too many transaction details");
		return;
		}
	$this->data->gw_transaction_details->$varname = $obj;
}


} // class