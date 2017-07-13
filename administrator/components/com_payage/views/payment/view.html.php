<?php
/********************************************************************
Product     : Payage
Date		: 14 March 2017
Copyright   : Les Arbres Design 2014-2017
Contact     : http://www.lesarbresdesign.info
Licence     : GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class PayageViewPayment extends LAPG_view
{

//-------------------------------------------------------------------------------
// Show the list of payments
//
function display($tpl = null)
{
	JToolBarHelper::title('Payage: '.JText::_('COM_PAYAGE_PAYMENTS'), 'payage.png');
	JToolBarHelper::deleteList();
	JToolBarHelper::custom('unconfirmed', 'database.png', '', 'COM_PAYAGE_UNCONFIRMED',false);
	JToolBarHelper::preferences('com_payage',350,450);

	if ($this->payment_list === false)
		return;	// the db is broken so don't try to do anything

    LAPG_view::viewStart();
    
	JHtml::_('bootstrap.tooltip');
	
// get the order states				

	$this->app = JFactory::getApplication();
	$filter_order = $this->app->getUserStateFromRequest('com_payage.payment_filter_order', 'filter_order', 'date_time');
	$filter_order_Dir = $this->app->getUserStateFromRequest('com_payage.payment_filter_order_Dir', 'filter_order_Dir', 'desc');
	$lists['order_Dir'] = $filter_order_Dir;
	$lists['order'] = $filter_order;

// get the current filters	
		
	$search            = $this->app->getUserStateFromRequest('com_payage.payment_search','search','','RAW');
    $today             = date('Y-m-d');
    $one_week_ago      = date('Y-m-d', strtotime('-1 week'));
	$filter_start_date = $this->app->getUserStateFromRequest('com_payage.payment_filter_start_date','filter_start_date',$one_week_ago,'STRING');
	$filter_end_date   = $this->app->getUserStateFromRequest('com_payage.payment_filter_end_date','filter_end_date',$today,'STRING');
	$filter_app        = $this->app->getUserStateFromRequest('com_payage.payment_filter_app','filter_app','0','STRING');
	$filter_currency   = $this->app->getUserStateFromRequest('com_payage.payment_filter_currency','filter_currency','0','string');
	$filter_account    = $this->app->getUserStateFromRequest('com_payage.payment_filter_account','filter_account',0,'int');

// make the filter lists

	$start_date_html = LAPG_view::make_date_picker('filter_start_date', $filter_start_date);
	$end_date_html   = LAPG_view::make_date_picker('filter_end_date', $filter_end_date);

    if (count($this->app_list) > 2)         // 2 items in the list would be "All" and one application - so don't show the selector
        $app_filter_html = LAPG_view::make_list('filter_app', $filter_app, $this->app_list, 0, 'onchange="submitform();"');
    else
        $app_filter_html = '<input type="hidden" name="filter_app" id="filter_app" value="0" />';   // so we don't break the reset javascript

    if (count($this->currency_list) > 2)
    	$currency_filter_html = LAPG_view::make_list('filter_currency', $filter_currency, $this->currency_list, 0, 'onchange="submitform();"');
    else
        $currency_filter_html = '<input type="hidden" name="filter_currency" id="filter_currency" value="0" />';
        
    if (count($this->account_list) > 2)
    	$account_filter_html = LAPG_view::make_list('filter_account', $filter_account, $this->account_list, 0, 'onchange="submitform();"');					
    else
        $account_filter_html = '<input type="hidden" name="filter_account" id="filter_account" value="0" />';

// prepare the status icons

	$tick_icon   = '<img src="'.LAPG_ADMIN_ASSETS_URL.'tick_spot_16.png" alt="" style="vertical-align:text-bottom;" />';
	$yellow_icon = '<img src="'.LAPG_ADMIN_ASSETS_URL.'yellow_spot_16.png" alt="" style="vertical-align:text-bottom;" />';
	$blue_icon   = '<img src="'.LAPG_ADMIN_ASSETS_URL.'blue_spot_16.png" alt="" style="vertical-align:text-bottom;" />';
	$red_icon    = '<img src="'.LAPG_ADMIN_ASSETS_URL.'red_spot_16.png" alt="" style="vertical-align:text-bottom;" />';

	$numrows = count($this->payment_list);

// Show the list of payments

	?>
	<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="controller" value="payment" />
	<input type="hidden" name="filter_order" value="<?php echo $lists['order']; ?>" />
	<input type="hidden" name="filter_order_Dir" value="<?php echo $lists['order_Dir']; ?>" />
    <?php
    
    echo "\n".'<div>&nbsp;<div style="float:left">'; 
    $icon = '<img src="'.LAPG_ADMIN_ASSETS_URL.'search_16.gif" alt="" style="vertical-align:text-bottom;" />';
    echo '<span class="hasTooltip" title="'.JText::_('COM_PAYAGE_SEARCH_DESC').'">'.$icon.'</span>';
    echo ' <input type="text" class="text_area" size="30" name="search" id="search" value="'.$search.'" /> ';
    echo JText::_('COM_PAYAGE_FROM_DATE').' '.$start_date_html.' '.JText::_('COM_PAYAGE_TO_DATE').' '.$end_date_html;
    echo ' <button class="btn" onclick="this.form.submit();">'.JText::_('COM_PAYAGE_GO').'</button>';
	echo '</div>'; 
	echo "\n".'<div style="float:right">'; 
    echo $app_filter_html.' '.$currency_filter_html.' '.$account_filter_html;
    echo ' <button class="btn" onclick="'."
            document.getElementById('search').value='';
            document.getElementById('filter_start_date').value='".$one_week_ago."';
            document.getElementById('filter_end_date').value='".$today."';
            document.getElementById('filter_app').value='0';
            this.form.submit();".'">'.JText::_('JSEARCH_RESET').'</button>';
	echo '</div></div>';
    ?>

	<table class="table table-striped">
	<thead><tr>
    <th style="width:20px; text-align:center;"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" /></th>
    <th style="white-space:nowrap;">
        <?php echo JHTML::_('grid.sort', 'COM_PAYAGE_DATE_TIME', 'date_time_initiated', $lists['order_Dir'], $lists['order']); ?></th>
    <th style="white-space:nowrap;">
        <?php echo JHTML::_('grid.sort', 'COM_PAYAGE_APPLICATION', 'app_name', $lists['order_Dir'], $lists['order']); ?></th>
    <th style="white-space:nowrap;">
        <?php echo JHTML::_('grid.sort', 'COM_PAYAGE_ITEM', 'item_name', $lists['order_Dir'], $lists['order']); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_CURRENCY'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_GROSS'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_TAX'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_TOTAL'); ?></th>
    <th style="white-space:nowrap;">
        <?php echo JHTML::_('grid.sort', 'COM_PAYAGE_ACCOUNT_NAME', 'account_name', $lists['order_Dir'], $lists['order']); ?></th>
    <th style="white-space:nowrap;">
        <?php echo JHTML::_('grid.sort', 'COM_PAYAGE_PAYER_NAME', 'payer_last_name', $lists['order_Dir'], $lists['order']); ?></th>
    <th style="white-space:nowrap;">
        <?php echo JHTML::_('grid.sort', 'COM_PAYAGE_PAYER_EMAIL', 'payer_email', $lists['order_Dir'], $lists['order']); ?></th>
    <th style="white-space:nowrap;">
        <?php echo JText::_('COM_PAYAGE_STATUS'); ?></th>
	</tr></thead>

	<tfoot><tr><td colspan="15"><?php echo $this->pagination->getListFooter(); ?></td></tr></tfoot>
	
	<tbody>
	<?php

	for ($i=0; $i < $numrows; $i++) 
		{
		$row = $this->payment_list[$i];
		$link = LAPG_COMPONENT_LINK.'&task=detail&controller=payment&cid[]='.$row->id;
		$checked = JHTML::_('grid.id', $i, $row->id);
		$date = JHTML::link($link, $row->date_time_initiated);
		$gross_amount = PayageHelper::format_amount($row->gross_amount, $row->currency_format, $row->currency_symbol);
		$tax_amount = PayageHelper::format_amount($row->tax_amount, $row->currency_format, $row->currency_symbol);
		$total_amount = $row->gross_amount + $row->customer_fee;
		$total_amount = PayageHelper::format_amount($total_amount, $row->currency_format, $row->currency_symbol);
		$buyer_name = $row->payer_first_name.' '.$row->payer_last_name;
		$buyer_name = preg_replace('/[^(a-zA-Z \x27)]*/','', $buyer_name);	// remove all except a-z, A-Z, and '

		switch ($row->pg_status_code)
			{
			case LAPG_STATUS_SUCCESS:  $icon = $tick_icon;   break;
			case LAPG_STATUS_PENDING:  $icon = $yellow_icon; break;
			case LAPG_STATUS_REFUNDED: $icon = $blue_icon;   break;
			case LAPG_STATUS_FAILED:   $icon = $red_icon;    break;
			default: $icon = $red_icon;
			}
		$status = $icon.' '.PayageHelper::getPaymentDescription($row->pg_status_code);

		echo "<tr>".
				'<td style="text-align:center;">'.$checked.'</td>'.
				'<td style="white-space:nowrap;">'.$date.'</td>'.
				"<td>$row->app_name</td>
				<td>$row->item_name</td>
				<td>$row->currency</td>
				<td>$gross_amount</td>
				<td>$tax_amount</td>
				<td>$total_amount</td>
				<td>$row->account_name</td>
				<td>$buyer_name</td>
				<td>$row->payer_email</td>
				<td>$status</td>
				</tr>\n";
		}
	echo '</tbody></table></form>';
    LAPG_view::viewEnd();
}

//-------------------------------------------------------------------------------
// Show the list of pending payments
//
function unconfirmed()
{
	JToolBarHelper::title('Payage: '.JText::_('COM_PAYAGE_PAYMENTS').' '.JText::_('COM_PAYAGE_UNCONFIRMED'), 'payage.png');
	JToolBarHelper::custom('unconfirmed', 'refresh.png', '', 'COM_PAYAGE_REFRESH',false);
	JToolBarHelper::cancel('cancel','JTOOLBAR_CLOSE');

	if ($this->payment_list === false)
		return;	// the db is broken so don't try to do anything
	
    LAPG_view::viewStart();
    
	$numrows = count($this->payment_list);

// Show the list of pending payments

	?>
	<form action="index.php" method="get" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="task" value="unconfirmed" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="controller" value="payment" />

	<table class="table table-striped">
	<thead><tr>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_DATE_TIME'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_APPLICATION'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_ITEM'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_CURRENCY'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_GROSS'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_TAX'); ?></th>
    <th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_IP_ADDRESS'); ?></th>
    <th style="white-space:nowrap;" colspan="2"><?php echo JText::_('COM_PAYAGE_BROWSER'); ?></th>
	</tr></thead>

	<tfoot><tr><td colspan="9"><?php echo $this->pagination->getListFooter(); ?></td></tr></tfoot>
	
	<tbody>
	<?php

	for ($i=0; $i < $numrows; $i++) 
		{
		$row = $this->payment_list[$i];
		$gross_amount = PayageHelper::format_amount($row->gross_amount);
		$tax_amount = PayageHelper::format_amount($row->tax_amount);
        $browser = self::getBrowser($row->client_ua);
        $browser_info = LAPG_view::make_info($row->client_ua);

		echo "<tr>".
				'<td style="white-space:nowrap;">'.$row->date_time_initiated.'</td>'.
				"<td>$row->app_name</td>
				<td>$row->item_name</td>
				<td>$row->currency</td>
				<td>$gross_amount</td>
				<td>$tax_amount</td>
				<td>$row->client_ip</td>
				<td>$browser</td>
				<td>$browser_info</td>
				</tr>\n";
		}
	echo '</tbody></table></form>';
    LAPG_view::viewEnd();
}

//-------------------------------------------------------------------------------
// View a single payment
//
function edit()
{
    LAPG_view::viewStart();
	JToolBarHelper::title('Payage: '.JText::_('COM_PAYAGE_PAYMENT_DETAILS'), 'payage.png');
	
	if ($this->payment_data->pg_status_code != LAPG_STATUS_REFUNDED)
		JToolBarHelper::custom('status_refund', 'undo.png', 'undo_f2.png', 'COM_PAYAGE_REFUND', false);
		
	if ($this->payment_data->pg_status_code != LAPG_STATUS_SUCCESS)
		JToolBarHelper::custom('status_success', 'publish.png', 'publish_f2.png', 'COM_PAYAGE_SUCCESS', false);
		
	if ($this->payment_data->pg_status_code != LAPG_STATUS_PENDING)
		JToolBarHelper::custom('status_pending', 'pin.png', 'pin_f2.png', 'COM_PAYAGE_PENDING', false);
		
	if ($this->payment_data->pg_status_code != LAPG_STATUS_FAILED)
		JToolBarHelper::custom('status_failed', 'unpublish.png', 'unpublish_f2.png', 'COM_PAYAGE_FAILED', false);
		
	JToolBarHelper::custom('download', 'download.png', 'download.png', JText::_('COM_PAYAGE_DOWNLOAD'), false);

	JToolBarHelper::cancel('cancel','JTOOLBAR_CLOSE');

// the gateway may have been deleted since the payment was made

	if (!empty($this->gateway_info))
		$gateway_shortName = $this->gateway_info['shortName'];
	else
		$gateway_shortName = '<span class="error_msg">'.JText::_('COM_PAYAGE_GATEWAY_NOT_INSTALLED').'</span>';

	?>
	<form action="index.php" method="post" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="controller" value="payment" />
	<input type="hidden" name="id" value="<?php echo $this->payment_data->id; ?>" />
	<?php

// common data

	echo '<fieldset class="lad-fieldset lad-border width-auto">';
	echo '<legend>'.JText::_('COM_PAYAGE_PAYMENT_DETAILS').'</legend>';

	echo "\n<table>";
		echo '<tr><td>'.JText::_('COM_PAYAGE_DATE_TIME').'</td><td>'.$this->payment_data->date_time_initiated.'</td></tr>';

		if ($this->payment_data->date_time_updated == '0000-00-00 00:00:00')
			$date_time_updated = '';
		else
			$date_time_updated = $this->payment_data->date_time_updated;
		echo '<tr><td>'.JText::_('COM_PAYAGE_UPDATED').'</td><td>'.$date_time_updated.'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_ACCOUNT_NAME').'</td><td>'.$this->account_data->account_name.'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_OUR_TRANSACTION_ID').'</td><td>'.$this->payment_data->pg_transaction_id.'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_GATEWAY_TYPE').'</td><td>'.$gateway_shortName.'</td></tr>';
		
		if ($this->payment_data->pg_status_code == LAPG_STATUS_FAILED)
			$css_class = ' class="error_msg"';
		else
			$css_class = '';
		echo '<tr><td>'.JText::_('COM_PAYAGE_STATUS')."</td><td $css_class>".PayageHelper::getPaymentDescription($this->payment_data->pg_status_code).'</td></tr>';
		
		if (!empty($this->payment_data->pg_status_text))
			echo '<tr><td style="vertical-align:top">'.JText::_('COM_PAYAGE_STATUS_DETAILS')."</td><td $css_class>".wordwrap($this->payment_data->pg_status_text,50,'<br>',true).'</td></tr>';

		if (($this->payment_data->pg_status_code == LAPG_STATUS_PENDING) and (!empty($this->payment_data->gw_pending_reason)))
			echo '<tr><td>'.JText::_('COM_PAYAGE_PENDING_REASON').'</td><td>'.$this->payment_data->gw_pending_reason.'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_APPLICATION').'</td><td>'.$this->payment_data->app_name.'</td></tr>';

		if (!empty($this->payment_data->item_name))
			echo '<tr><td>'.JText::_('COM_PAYAGE_ITEM').'</td><td>'.$this->payment_data->item_name.'</td></tr>';

		if (!empty($this->payment_data->app_transaction_id))
			echo '<tr><td>'.JText::_('COM_PAYAGE_APP_TRANSACTION_ID').'</td><td>'.$this->payment_data->app_transaction_id.'</td></tr>';

		if (!empty($this->payment_data->gw_transaction_id))
			echo '<tr><td>'.JText::_('COM_PAYAGE_GATEWAY_TRANSACTION_ID').'</td><td>'.$this->payment_data->gw_transaction_id.'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_CURRENCY').'</td><td>'.$this->payment_data->currency.'</td></tr>';

		$total_amount = $this->payment_data->gross_amount + $this->payment_data->customer_fee;
		echo '<tr><td>'.JText::_('COM_PAYAGE_TOTAL').'</td><td>'.PayageHelper::format_amount($total_amount, $this->account_data->currency_format, $this->account_data->currency_symbol).'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_GROSS').'</td><td>'.PayageHelper::format_amount($this->payment_data->gross_amount, $this->account_data->currency_format, $this->account_data->currency_symbol).'</td></tr>';

		if (!empty($this->payment_data->tax_amount))
			echo '<tr><td>'.JText::_('COM_PAYAGE_TAX').'</td><td>'.PayageHelper::format_amount($this->payment_data->tax_amount, $this->account_data->currency_format, $this->account_data->currency_symbol).'</td></tr>';

		if (!empty($this->payment_data->customer_fee))
			echo '<tr><td>'.JText::_('COM_PAYAGE_FEE_CUSTOMER').'</td><td>'.PayageHelper::format_amount($this->payment_data->customer_fee, $this->account_data->currency_format, $this->account_data->currency_symbol).'</td></tr>';

		if (!empty($this->payment_data->gateway_fee))
			echo '<tr><td>'.JText::_('COM_PAYAGE_FEE_GATEWAY').'</td><td>'.PayageHelper::format_amount($this->payment_data->gateway_fee, $this->account_data->currency_format, $this->account_data->currency_symbol).'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_PAYER_EMAIL').'</td><td>'.$this->payment_data->payer_email.'</td></tr>';

		echo '<tr><td>'.JText::_('COM_PAYAGE_PAYER_NAME').'</td><td>'.$this->payment_data->payer_first_name.' '.$this->payment_data->payer_last_name.'</td></tr>';

		if (!empty($this->payment_data->payer_address1))
			echo '<tr><td>'.JText::_('COM_PAYAGE_PAYER_ADDRESS').'</td><td>'.$this->payment_data->payer_address1.'</td></tr>';

		if (!empty($this->payment_data->payer_address2))
			echo '<tr><td></td><td>'.$this->payment_data->payer_address2.'</td></tr>';

		if (!empty($this->payment_data->payer_city))
			echo '<tr><td></td><td>'.$this->payment_data->payer_city.'</td></tr>';

		if (!empty($this->payment_data->payer_state))
			echo '<tr><td></td><td>'.$this->payment_data->payer_state.'</td></tr>';

		if (!empty($this->payment_data->payer_zip_code))
			echo '<tr><td></td><td>'.$this->payment_data->payer_zip_code.'</td></tr>';

		if (!empty($this->payment_data->payer_country))
			echo '<tr><td>'.JText::_('COM_PAYAGE_PAYER_COUNTRY').'</td><td>'.$this->payment_data->payer_country.'</td></tr>';

		if (!empty($this->payment_data->client_ip))
			echo '<tr><td>'.JText::_('COM_PAYAGE_IP_ADDRESS').'</td><td>'.$this->payment_data->client_ip.'</td></tr>';
            
		if (!empty($this->payment_data->client_ua))
            {
            $browser = self::getBrowser($this->payment_data->client_ua);
            $browser_info = LAPG_view::make_info($this->payment_data->client_ua);
			echo '<tr><td>'.JText::_('COM_PAYAGE_BROWSER').'</td><td>'.$browser.' '.$browser_info.'</td></tr>';
            }            

	echo "\n</table>";
	echo '</fieldset>';

// gateway data

	$full_details = false;
	if (!empty($this->payment_data->gw_transaction_details))
		{
		echo '<fieldset class="lad-fieldset lad-border width-auto">';
		echo '<legend>'.JText::_('COM_PAYAGE_GATEWAY_TRANSACTION_DETAILS').'</legend>';
		echo "\n<table>";
		foreach ($this->payment_data->gw_transaction_details as $key => $value)
			if (!empty($key) and !empty($value))
					if (is_object($value) or is_array($value))
						{
						$full_details = true;							// if there are complex structures, show the full details link
						echo '<tr><td>'.$key.'</td><td>{..}</td></tr>';
						}
					else
						echo '<tr><td>'.$key.'</td><td>'.wordwrap($value,35,'<br>',true).'</td></tr>';
			if ($full_details)
				{
				$popup_src = "index.php?option=com_payage&controller=payment&task=full_details&column=gw_transaction_details&id=".$this->payment_data->id;
				$popup = 'onclick="window.open('."'".$popup_src."', 'app_details', 'width=640,height=480,scrollbars=1,location=0,menubar=0,resizable=1'); return false;".'"';
				echo '<tr><td>'.JHTML::link('#', JText::_('COM_PAYAGE_MORE'), 'target="_blank" '.$popup).'</td><td></td></tr>';
				}
		echo "\n</table>";
		echo '</fieldset>';
		}

// history

	if (!empty($this->payment_data->pg_history))
		{
		echo '<fieldset class="lad-fieldset lad-border width-auto">';
		echo '<legend>'.JText::_('COM_PAYAGE_HISTORY').'</legend>';
		echo self::_wrap($this->payment_data->pg_history, 50);
		echo '</fieldset>';
		}

// application data

	$full_details = false;
	if (!empty($this->payment_data->app_transaction_details))
		{
		echo '<fieldset class="lad-fieldset lad-border width-auto">';
		echo '<legend>'.JText::_('COM_PAYAGE_APPLICATION_TRANSACTION_DETAILS').'</legend>';
		if (is_object($this->payment_data->app_transaction_details) or is_array($this->payment_data->app_transaction_details))
			{
			echo "\n<table>";
			foreach ($this->payment_data->app_transaction_details as $key => $value)
				if (!empty($key) and !empty($value))
					if (is_object($value) or is_array($value))
						{
						$full_details = true;							// if there are complex structures, show the full details link
						echo '<tr><td>'.$key.'</td><td>{..}</td></tr>';
						}
					else
						echo '<tr><td>'.$key.'</td><td>'.$value.'</td></tr>';
			if ($full_details)
				{
				$popup_src = "index.php?option=com_payage&controller=payment&task=full_details&column=app_transaction_details&id=".$this->payment_data->id;
				$popup = 'onclick="window.open('."'".$popup_src."', 'app_details', 'width=640,height=480,scrollbars=1,location=0,menubar=0,resizable=1'); return false;".'"';
				echo '<tr><td>'.JHTML::link('#', JText::_('COM_PAYAGE_MORE'), 'target="_blank" '.$popup).'</td><td></td></tr>';
				}
			echo "\n</table>";
			}
		else
			echo $this->payment_data->app_transaction_details;	
		echo '</fieldset>';
		}

	echo '</form>';
    LAPG_view::viewEnd();
}

static function _wrap($txt, $length)
{
	$txt = nl2br($txt);
	$a = explode('<br />',$txt);
	$new_text = '';
	foreach ($a as $line)
		$new_text .= wordwrap($line,$length,'<br>',true).'<br />';
	return $new_text;
}

//-------------------------------------------------------------------------------
// Get a short browser name from a UA string
//
function getBrowser($u_agent)
{
    if (empty($u_agent))
        return '';
    if (strstr($u_agent, 'Edge'))       // must test for this first
        return 'Edge';
    if (strstr($u_agent, 'MSIE') && !strstr($u_agent, 'Opera')) 
        return 'MSIE'; 
    if (strstr($u_agent, 'Trident')) 
        return 'MSIE'; 
    if (strstr($u_agent, 'Firefox')) 
        return 'Firefox'; 
    if (strstr($u_agent, 'Chrome')) 	 // must test for Chrome before Safari
        return 'Chrome'; 
    if (strstr($u_agent, 'Safari')) 
        return 'Safari'; 
    if (strstr($u_agent, 'Opera')) 
        return 'Opera'; 
    if (strstr($u_agent, 'Netscape')) 
        return 'Netscape'; 
    if (strstr($u_agent, 'Konqueror')) 
        return 'Konqueror'; 
    return 'Unknown';
} 



} // class


















