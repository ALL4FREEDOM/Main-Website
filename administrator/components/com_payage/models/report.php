<?php
/********************************************************************
Product		: Payage
Date		: 2 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');
jimport('joomla.html.pagination');

class PayageModelReport extends LAPG_model
{
var $app = null;
var $list_data = null;
var $chart_data = null;
var $pagination = null;
var $date_title = null;

function __construct()
{
	parent::__construct();
	$this->app = JFactory::getApplication();
}

//-------------------------------------------------------------------------------
// Return a pointer to our pagination object
//
function &getPagination()
{
	if ($this->pagination == Null)
		$this->pagination = new JPagination(0,0,0);
	return $this->pagination;
}

//-------------------------------------------------------------------------------
// Get the filter states and construct the where clause
//
function where($min_days=0)
{
// get the filter states

    $today             = date('Y-m-d');
    $one_year_ago      = date('Y-m-d', strtotime('-1 year'));
	$filter_start_date = $this->app->getUserStateFromRequest('com_payage.filter_start_date','filter_start_date',$one_year_ago,'STRING');
	$filter_end_date   = $this->app->getUserStateFromRequest('com_payage.filter_end_date','filter_end_date',$today,'STRING');
	$filter_app        = $this->app->getUserStateFromRequest('com_payage.payment_filter_app','filter_app','0','STRING');
	$filter_currency   = $this->app->getUserStateFromRequest('com_payage.filter_currency','filter_currency','0','string');
	$filter_account    = $this->app->getUserStateFromRequest('com_payage.filter_account','filter_account',0,'int');
    
    if (!LAPG_view::validDate($filter_start_date))
		{
		$this->app->enqueueMessage(JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_FROM_DATE'), 'error');
        return false;
		}
    if (!LAPG_view::validDate($filter_end_date))
		{
		$this->app->enqueueMessage(JText::_('COM_PAYAGE_INVALID').' '.JText::_('COM_PAYAGE_TO_DATE'), 'error');
        return false;
		}
    if ($min_days != 0)
        {
        $start = strtotime($filter_start_date);
        $end = strtotime($filter_end_date);
        $days_between = ceil(abs($end - $start) / 86400);
        if ($days_between < $min_days)
            {
    		$this->app->enqueueMessage(JText::sprintf('COM_PAYAGE_DATE_RANGE_AT_LEAST_X_DAYS',$min_days), 'error');
            return false;
            }
        }

   	$query_where = " WHERE DATE(`date_time_initiated`) >= ".$this->_db->Quote($filter_start_date)." AND DATE(`date_time_initiated`) <= ".$this->_db->Quote($filter_end_date);
	$query_where .= " AND `pg_status_code` IN (".LAPG_STATUS_SUCCESS.", ".LAPG_STATUS_PENDING.") ";

    if ($filter_currency != '0')
    	$query_where .= " AND `currency` = ".$this->_db->Quote($filter_currency);

	if ($filter_app != '0')
		$query_where .= " AND `app_name` = ".$this->_db->Quote($filter_app);

	if ($filter_account != 0)
		$query_where .= " AND `account_id` = ".$filter_account;
        
// check we have some data

    $query = "SELECT count(*) FROM `#__payage_payments` ".$query_where;
	$count = $this->ladb_loadResult($query);
    LAPG_trace::trace($query." returned: $count");
    if ($count == 0)
		{
		$this->app->enqueueMessage(JText::_('COM_PAYAGE_NO_DATA_SELECTION'), 'notice');
		return false;
		}
        
    return $query_where;
}

//-------------------------------------------------------------------------------
// Get the information for the Popular Products Pie Chart and Report
//
function popular_items()
{
// build the query for the chart

    $query_cols = "SELECT `item_name`, COUNT(*) as `number` FROM `#__payage_payments` ";
   	$query_where = self::where();
    if ($query_where === false)
        return false;
    $query_group = " GROUP BY `item_name`";
    $query_order = " ORDER BY `number` DESC";
    $query_limit = " LIMIT 0, 10";
    $query = $query_cols.$query_where.$query_group.$query_order.$query_limit;
    LAPG_trace::trace($query);
		
// Set up the chart information

	require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/plotalot.php';
	$chart_info = new stdclass();
	$chart_info->chart_type = CHART_TYPE_PIE_3D_V;
	$chart_info->x_size = 0;
	$chart_info->y_size = 250;
	$chart_info->num_plots = 1;
	$chart_info->x_format = FORMAT_NUM_UK_0;
	$chart_info->legend_type = LEGEND_RIGHT;
	$chart_info->extra_parms = ",chartArea:{left:0,top:5,width:'100%',height:'95%'}";
	$chart_info->plot_array = array();
	$chart_info->plot_array[0]['query'] = $query;
	$chart_info->plot_array[0]['enable'] = 1;
	$chart_info->plot_array[0]['colour'] = '7C78FF';
	$chart_info->plot_array[0]['style'] = PIE_MULTI_COLOUR;

// call Plotalot to make the chart

	$plotalot = new Plotalot;
	$this->chart_data = $plotalot->drawChart($chart_info);
    if ($this->chart_data == '')
		$this->chart_data = $plotalot->error;
    
// make the full list        

    $query_limit = " LIMIT 0, 100";
    $query = $query_cols.$query_where.$query_group.$query_order.$query_limit;
    LAPG_trace::trace($query);
    
	$this->list_data = $this->ladb_loadObjectList($query);

	if ($this->list_data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return false;
		}

	return true;
}

//-------------------------------------------------------------------------------
// Get the information for the Country Report and Pie Chart
//
function country_sales()
{
// build the query for the chart

    $query_cols = "(SELECT IF(`payer_country`='','".JText::_('COM_PAYAGE_UNKNOWN')."',`payer_country`) AS `payer_country`, count(`id`) as `number`
                    FROM `#__payage_payments`";
   	$query_where = self::where();
    if ($query_where === false)
        return false;
    $query_group = " GROUP BY `payer_country`";
    $query_order = " ORDER BY `number` DESC";
    $query_limit = " LIMIT 0, 10)";
    $query_union = " UNION (SELECT '".JText::_('COM_PAYAGE_ALL_OTHERS')."' AS `payer_country`, SUM(number) as `number` FROM 
            ( SELECT count(`id`) AS number FROM `#__payage_payments` ".$query_where."
                GROUP BY `payer_country` ORDER BY count(`id`) DESC LIMIT 10,18446744073709551615) AS X )";
    $query = $query_cols.$query_where.$query_group.$query_order.$query_limit.$query_union;
    LAPG_trace::trace($query);
		
// Set up the chart information

	require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/plotalot.php';
	$chart_info = new stdclass();
	$chart_info->chart_type = CHART_TYPE_PIE_3D_V;
	$chart_info->x_size = 0;
	$chart_info->y_size = 250;
	$chart_info->num_plots = 1;
	$chart_info->x_format = FORMAT_NUM_UK_0;
	$chart_info->legend_type = LEGEND_RIGHT;
	$chart_info->extra_parms = ",chartArea:{left:0,top:5,width:'100%',height:'95%'}";
	$chart_info->plot_array = array();
	$chart_info->plot_array[0]['query'] = $query;
	$chart_info->plot_array[0]['enable'] = 1;
	$chart_info->plot_array[0]['colour'] = '7C78FF';
	$chart_info->plot_array[0]['style'] = PIE_MULTI_COLOUR;

// call Plotalot to make the chart

	$plotalot = new Plotalot;
	$this->chart_data = $plotalot->drawChart($chart_info);
    if ($this->chart_data == '')
		$this->chart_data = $plotalot->error;
        
// make the full list        

    $query_cols = "SELECT `payer_country`, count(`id`) as `number` FROM `#__payage_payments` ";
    $query = $query_cols.$query_where.$query_group.$query_order;
    LAPG_trace::trace($query);
    
	$this->list_data = $this->ladb_loadObjectList($query);

	if ($this->list_data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return false;
		}

	return true;
}

//-------------------------------------------------------------------------------
// Get the information for the Sales History Line Chart
//
function sales_history()
{
// get the top 8 items

    $where = self::where(30);
    if ($where === false)
        return false;
    $query = "SELECT `item_name`, COUNT(`item_name`) AS `number` FROM `#__payage_payments` ".$where."
        GROUP BY `item_name`
        ORDER BY `number` DESC
        LIMIT 8";
        
    LAPG_trace::trace($query);
	$this->list_data = $this->ladb_loadObjectList($query);
	if ($this->list_data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return false;
		}
    LAPG_trace::trace(print_r($this->list_data,true));
        
    if (count($this->list_data) == 0)
		{
		$this->app->enqueueMessage(JText::_('COM_PLOTALOT_ERROR_NO_ROWS'), 'notice');
		return false;
		}
		
// Set up the chart information

	require_once JPATH_ADMINISTRATOR.'/components/com_payage/helpers/plotalot.php';
	$chart_info = new stdclass();
	$chart_info->chart_type = CHART_TYPE_LINE;
	$chart_info->x_size = 0;
	$chart_info->y_size = 450;
    $chart_info->show_grid = 1;
    $chart_info->x_labels = 10;
	$chart_info->x_format = FORMAT_CUSTOM_DATE;
    $chart_info->custom_x_format = 'MMM yy';
    $chart_info->y_format = FORMAT_NUM_UK_1;
    $chart_info->y_title = JText::_('COM_PAYAGE_AVERAGE_SALES_PER_DAY');
	$chart_info->legend_type = LEGEND_RIGHT;
	$chart_info->extra_parms = ",chartArea:{left:'8%',right:'20%',top:20,height:'75%'}";
    
// build the plot array

	$chart_info->plot_array = array();
    $plot_index = 0;
    foreach ($this->list_data as $data)
        {
        if (empty($data->item_name))
            continue;

        $query_cols = "SELECT UNIX_TIMESTAMP(date_time_initiated) AS `time`, 
            ROUND((COUNT(`gross_amount`)/(IF (MONTH(`date_time_initiated`) = MONTH(CURRENT_DATE()) AND YEAR(`date_time_initiated`) = YEAR(CURRENT_DATE()), 
                DAY(CURRENT_DATE()), DAY(LAST_DAY(`date_time_initiated`))))),2) as average,
            DATE_FORMAT(`date_time_initiated`, '%M %Y') AS `month`                            
            FROM `#__payage_payments` ";
        $query_where = $where." AND `item_name` = ".$this->_db->Quote($data->item_name);
        $query_group = " GROUP BY `month`";
        $query_order = " ORDER BY `date_time_initiated`";
        $query = $query_cols.$query_where.$query_group.$query_order;
        LAPG_trace::trace($query);
        $chart_info->plot_array[$plot_index]['legend'] = $data->item_name;
        $chart_info->plot_array[$plot_index]['query'] = $query;
        $chart_info->plot_array[$plot_index]['enable'] = 1;
        $chart_info->plot_array[$plot_index]['style'] = LINE_THICK_SOLID;
        $plot_index ++;
        }
	$chart_info->num_plots = $plot_index + 1;

// call Plotalot to make the chart

	$plotalot = new Plotalot;
	$this->chart_data = $plotalot->drawChart($chart_info);
    if ($this->chart_data == '')
		$this->chart_data = $plotalot->error;
    
	return true;
}

//-------------------------------------------------------------------------------
// Get the data for the Sales by Month Report
//
function sales_monthly()
{
	$filter_order = $this->app->getUserStateFromRequest('com_payage.report_filter_order', 'filter_order', 'month');
	$filter_order_Dir = $this->app->getUserStateFromRequest('com_payage.report_filter_order_Dir', 'filter_order_Dir', 'DESC');
	
// build the query

	$query_cols  = "SELECT date_format(`date_time_initiated`, '%M %Y') AS `month`, 
                    IF (MONTH(`date_time_initiated`) = MONTH(CURRENT_DATE()) AND YEAR(`date_time_initiated`) = YEAR(CURRENT_DATE()), 
                        DAY(CURRENT_DATE()), DAY(LAST_DAY(`date_time_initiated`))) AS `days`,
                    COUNT(`gross_amount`) AS `number`,
                    SUM(`gross_amount`) AS `gross_amount`, 
                    SUM(`gateway_fee`) AS `gateway_fee`,
                    SUM(`tax_amount`) AS `tax_amount`,
                    `currency` ";

	$query_from = "FROM `#__payage_payments`";

// where

   	$query_where = self::where();
    if ($query_where === false)
        return false;
        
// group by

	$query_group = " GROUP BY `month`, `currency` ";

// order by

	$query_order = " ORDER BY `currency`,`date_time_initiated` ";
	if (strcasecmp($filter_order_Dir,'ASC') != 0)	// validate 'asc' or 'desc'
		$filter_order_Dir = 'DESC';
	$query_order .= $filter_order_Dir;

	$query = $query_cols.$query_from.$query_where.$query_group.$query_order;
    LAPG_trace::trace($query);
    
	$this->list_data = $this->ladb_loadObjectList($query);

	if ($this->list_data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return $this->list_data;
		}

	return $this->list_data;
}

//-------------------------------------------------------------------------------
// Get the data for the Sales by Date Report
//
function sales_item()
{
	$filter_order = $this->app->getUserStateFromRequest('com_payage.report_filter_order', 'filter_order', 'month');
	$filter_order_Dir = $this->app->getUserStateFromRequest('com_payage.report_filter_order_Dir', 'filter_order_Dir', 'DESC');
	
// build the query

	$query_cols  = "SELECT `item_name`, COUNT(*) as `number`, SUM(`gross_amount`) as `gross_amount`, `currency`, `gateway_fee`, `tax_amount`";
	$query_from = " FROM `#__payage_payments` ";

// where

   	$query_where = self::where();
    if ($query_where === false)
        return false;

// group by

	$query_group = " GROUP BY `currency`,`item_name`";

// order by

	$query_order = " ORDER BY `currency`,`item_name` ";
    
	if (strcasecmp($filter_order_Dir,'ASC') != 0)	// validate 'asc' or 'desc'
		$filter_order_Dir = 'DESC';
        
	$query_order .= $filter_order_Dir;

	$query = $query_cols.$query_from.$query_where.$query_group.$query_order;
    LAPG_trace::trace($query);
    
	$this->list_data = $this->ladb_loadObjectList($query);

	if ($this->list_data === false)
		{
		$this->app->enqueueMessage($this->ladb_error_text, 'error');
		return false;
		}

	return true;
}


} // class