<?php
/********************************************************************
Product		: Payage
Date		: 15 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class PayageViewAccount extends LAPG_view
{

//-------------------------------------------------------------------------------
// Show the list of accounts
//
function display($tpl = null)
{
    LAPG_view::viewStart();
	JToolBarHelper::title('Payage: '.JText::_('COM_PAYAGE_ACCOUNTS'), 'payage.png');
	JToolBarHelper::publishList();
	JToolBarHelper::unpublishList();
	JToolBarHelper::deleteList();
	JToolBarHelper::addNew('account_choice');
	JToolBarHelper::preferences('com_payage',350,450);

	if ($this->account_list === false)
		return;	// the db is broken so don't try to do anything

// get the filter states

	$app = JFactory::getApplication();
	$filter_state = $app->getUserStateFromRequest('com_payage.account','filter_state','','word');

	$numrows = count($this->account_list);
    
// Show the list of accounts

	?>
	<form action="index.php" method="get" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="boxchecked" value="0" />
	<input type="hidden" name="controller" value="account" />
    <?php
    
    if ($numrows == 0)
        {
    	$app = JFactory::getApplication();
		$app->enqueueMessage(JText::_('COM_PAYAGE_NO_ACCOUNTS'), 'notice');
        echo '</form>';
        LAPG_view::viewEnd();
        return;
        }

    echo "\n".'<div>&nbsp;<div style="float:left">'; 
	echo '</div>'; 
	echo "\n".'<div style="float:right">'; 
    echo JHtml::_('grid.state', $filter_state);
    echo '&nbsp;';
    echo '<button class="btn" onclick="'."
            document.getElementById('filter_state').value='';
            this.form.submit();".'">'.JText::_('JSEARCH_RESET').'</button>';
	echo '</div></div>';

	?>
	<table class="table table-striped">
	<thead>
	<tr>
		<th style="width:20px; text-align:center;"><input type="checkbox" name="toggle" value="" onclick="Joomla.checkAll(this);" /></th>
		<th style="width:20px; text-align:center;"><?php echo JText::_('JPUBLISHED'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_NAME'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_TYPE'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_GROUP'); ?></th>
		<th style="white-space:nowrap; text-align:center;"><?php echo JText::_('COM_PAYAGE_BUTTON'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_EMAIL'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_LANGUAGE'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_CURRENCY'); ?></th>
		<th style="white-space:nowrap;"><?php echo JText::_('COM_PAYAGE_FEE'); ?></th>
	</tr>
	</thead>

	<tbody>
	<?php

	for ($i=0; $i < $numrows; $i++) 
		{
		$row = $this->account_list[$i];
		$link = LAPG_COMPONENT_LINK.'&task=edit&controller=account&cid[]='.$row->id;
		$img = '<img src="'.JURI::root(true).'/'.$row->button_image.'" alt="" title="'.$row->button_title.'" style="height:16px;" />';
		echo "\n".'<tr>';
		echo '<td style="text-align:center;">'.JHtml::_('grid.id', $i, $row->id).'</td>';
		echo '<td style="text-align:center;">'.JHtml::_('grid.published', $row, $i).'</td>';
		
		if (isset($this->gateway_list[$row->gateway_type]))
			{
			echo '<td>'.JHtml::link($link, $row->account_name).'</td>';
			echo '<td>'.$this->gateway_list[$row->gateway_type]['shortName'].'</td>';
			}
		else
			{
			echo '<td>'.$row->account_name.'</td>';
			$error = '<img src="'.LAPG_ADMIN_ASSETS_URL.'warning.png" title="'.JText::sprintf('COM_PAYAGE_GATEWAY_BAD_INSTALL',$row->gateway_type).'" alt="" />';
			echo '<td>'.$error.' <span class="error_msg">'.$row->gateway_type.' ('.JText::_('COM_PAYAGE_GATEWAY_NOT_INSTALLED').')</span></td>';
			}
		
		echo '<td>'.$row->account_group.'</td>';
		echo '<td style="text-align:center;">'.$img.'</td>';
		echo '<td>'.$row->account_email.'</td>';
		echo '<td>'.$row->account_language.'</td>';
		echo '<td>'.$row->account_currency.'</td>';
		echo '<td>'.self::make_fee_summary($row).'</td>';
		echo "</tr>\n";
		}
        
    echo '</tbody></table></form>';
    LAPG_view::viewEnd();
}

static function make_fee_summary($row)
{
	switch ($row->fee_type)
		{
		case LAPG_FEE_TYPE_NONE:
			return '';
			
		case LAPG_FEE_TYPE_FIXED:
			return PayageHelper::format_amount($row->fee_amount, $row->currency_format, $row->currency_symbol);
			
		case LAPG_FEE_TYPE_PERCENT:
			$fee_summary = '';
			if ($row->fee_min != 0)
				$fee_summary .= PayageHelper::format_amount($row->fee_min, $row->currency_format, $row->currency_symbol).' .. ';
			$fee_summary .= $row->fee_amount.'%';
			if ($row->fee_max != 0)					// 0 means no maximum
				$fee_summary .= ' .. '.PayageHelper::format_amount($row->fee_max, $row->currency_format, $row->currency_symbol);
			return $fee_summary;
		default:
			return '';
		}
}

//-------------------------------------------------------------------------------
// Show the choice of gateway types
//
function choice()
{
    LAPG_view::viewStart();
	JToolBarHelper::title('Payage: '.JText::_('COM_PAYAGE_SELECT_GATEWAY_TYPE'), 'payage.png');
	JToolBarHelper::cancel();

// build the list of supported gateways

	$account_model = $this->getModel('account');
	$gateway_list = $account_model->getGatewayList();
		
// Show the list

	?>
	<form action="index.php" method="get" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="controller" value="account" />
	<?php
    
    if ($gateway_list === false)
        {
        echo '</form>';
        LAPG_view::viewEnd();
        return;
        }

	echo '<table class="table table-striped">';
	echo '<thead><tr>';
	echo '<th style="white-space:nowrap;">'.JText::_('COM_PAYAGE_GATEWAY_TYPE').'</th>';
	echo '<th style="white-space:nowrap;">'.JText::_('COM_PAYAGE_DESCRIPTION').'</th>';
	echo '<th style="white-space:nowrap;">URL</th>';
	echo '<th style="white-space:nowrap;">'.JText::_('JAUTHOR').'</th>';
	echo '</tr></thead>';

	echo '<tbody>';

	foreach ($gateway_list as $gateway_type => $gateway_info)
		{
		$link = LAPG_COMPONENT_LINK.'&task=new_account&controller=account&gateway_type='.$gateway_info['type'];
		echo "\n".'<tr>';
		echo '<td>'.JHTML::link($link, $gateway_info['shortName']).'</td>';
		echo '<td>'.$gateway_info['longName'].'</td>';
		$domain = parse_url($gateway_info['gatewayUrl'],PHP_URL_HOST);
		echo '<td>'.JHTML::link($gateway_info['gatewayUrl'], $domain, 'target="_blank"').'</td>';
		echo '<td>'.$gateway_info['author'].'</td>';
		echo "</tr>\n";
		}

	echo '</tbody></table></form>';
    LAPG_view::viewEnd();
}

//-------------------------------------------------------------------------------
// The account edit screen
//
function edit()
{
    LAPG_view::viewStart();
	$gateway_model = $this->getModel();	
	$gateway_type = $this->gateway_info['type'];
	$title = 'Payage: '.$this->gateway_info['longName'];
	JToolBarHelper::title($title, 'payage.png');

	if ( ($this->common_data->id > 0) and (method_exists($gateway_model,'Gateway_test')) )
		JToolBarHelper::custom('test', 'star.png', 'star.png', 'COM_PAYAGE_TEST',false);

	JToolBarHelper::apply();
	JToolBarHelper::save();
	if ($this->common_data->id > 0)
		JToolBarHelper::save2copy();
	JToolBarHelper::cancel('cancel','JTOOLBAR_CLOSE');
    
// if the site has multiple languages, we show a tab for each language

	$lang = JFactory::getLanguage('JPATH_SITE');
    $languages = $lang->getKnownLanguages();
    $num_languages = count($languages);
	
// load the JForm definition for the current gateway

	JForm::addFieldPath(JPATH_ADMINISTRATOR.'/components/com_payage/forms');
	$form = JForm::getInstance('account_edit', JPATH_ADMINISTRATOR.'/components/com_payage/forms/'.strtolower($gateway_type).'.xml');	
	$field_sets = $form->getFieldsets();
	?>
	<div class="la_form">
	<form action="index.php" method="post" name="adminForm" id="adminForm" class="form-horizontal form-inline">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="id" value="<?php echo $this->common_data->id; ?>" />
	<input type="hidden" name="task" value="" />
	<input type="hidden" name="controller" value="account" />
	<input type="hidden" name="gateway_type" value="<?php echo $this->common_data->gateway_type; ?>" />
	<input type="hidden" name="gateway_shortname" value="<?php echo $this->common_data->gateway_shortname; ?>" />
	<input type="hidden" name="published" value="<?php echo $this->common_data->published; ?>" />
	<?php
    
    if ($num_languages > 1)
        {
        echo JHtml::_('bootstrap.startTabSet','myTab', array('active' => 'tab1'));
        echo JHtml::_('bootstrap.addTab', 'myTab', 'tab1', JText::_('COM_PAYAGE_DETAILS'));
        }

	foreach ($field_sets as $fieldset_name => $fieldset)	
		{
		
// the 'main' fieldset gets all the space it needs
// any other fieldset only gets 300px

		if ($fieldset_name == 'main')
			echo '<div style="display:inline-block; margin-right:20px;">';
		else
			echo '<div style="display:inline-block; max-width:300px; vertical-align:top; margin-right:20px;">';
		echo '<fieldset class="lad-fieldset lad-border width-auto">';
		if (!empty($fieldset->label))
			echo '<legend>'.JText::_($fieldset->label).'</legend>';
		$fields = $form->getFieldset($fieldset_name);
		foreach ($fields as $field)
			{

// get the field name and set the value

			$field_name = $field->name;
			if (isset($this->common_data->$field_name))
				$form->setValue($field_name, null, $this->common_data->$field_name);
			else
				if (isset($this->specific_data->$field_name))
					$form->setValue($field_name, null, $this->specific_data->$field_name);
				
// unless it's a hidden field, draw the label

			echo '<div class="form-group">';
			if (!$field->hidden)
				echo $form->getLabel($field_name);
				
// always draw the field itself

			echo $form->getInput($field_name);
			
// if the field description contains a link, draw an info button next to the field with the link embedded

			$field_description = $field->description;
			if (!empty($field_description))
				{
				$field_description = JText::_($field_description);
				$link = stristr($field_description,'http');
				$linkend = strpos($link,' ');
				$link = substr($link,0,$linkend);
				if (!empty($link))
					echo ' '.$this->make_info($link,$link);
				}

			echo '</div>';
			}
		echo '</fieldset>';
		echo '</div>';
		}
        
// if we have multiple languages, draw the language tabs

    if ($num_languages > 1)
        {
        echo JHtml::_('bootstrap.endTab');
        foreach ($field_sets as $fieldset_name => $fieldset)	
    		if ($fieldset_name == 'main')
        		$fields = $form->getFieldset($fieldset_name);   // get the fields of the 'main' fieldset

        foreach ($languages as $tag => $name)
            {
            echo JHtml::_('bootstrap.addTab', 'myTab', $tag, $tag);
            foreach ($fields as $field)
                {
    			$field_name = $field->name;
                if (in_array($field_name,array('button_title', 'button_image', 'account_description')))
                    {
        			if (isset($this->translations[$tag][$field_name]))
        				$form->setValue($field_name, null, $this->translations[$tag][$field_name]);
        			echo '<div class="form-group">';
    				echo $form->getLabel($field_name);              // draw the label
        			$input_html = $form->getInput($field_name);     // get the field html
                    $lang_html = str_replace($field_name,$tag.'_'.$field_name,$input_html);  // modify the field name and id
                    echo $lang_html;
        			echo '</div>';
                    }
				}
            echo JHtml::_('bootstrap.endTab');
            }
        echo JHtml::_('bootstrap.endTabSet');
        }
        
	echo '</form>';
	echo '</div>';
    LAPG_view::viewEnd();
}

} // class


















