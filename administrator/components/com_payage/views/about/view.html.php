<?php
/********************************************************************
Product    : Payage
Date       : 9 December 2016
Copyright  : Les Arbres Design 2014-2016
Contact	   : http://www.lesarbresdesign.info
Licence    : GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted access');

class PayageViewAbout extends LAPG_view
{
function display($tpl = null)
{
    LAPG_view::viewStart();
    JToolBarHelper::title('Payage: '.JText::_('COM_PAYAGE_ABOUT'), 'payage.png');
	JToolBarHelper::cancel();
    
	?>
	<form action="index.php" method="get" name="adminForm" id="adminForm">
	<input type="hidden" name="option" value="com_payage" />
	<input type="hidden" name="controller" value="about" />
	<input type="hidden" name="task" value="" />
	</form>
	<?php
	
// build the help screen

	$about['name'] = 'Payage';
	$about['prefix'] = 'COM_PAYAGE';
	$about['current_version'] = PayageHelper::getComponentVersion();
    $about['latest_version'] = $this->get_version('payage');      // get the latest version
	$about['reference'] = 'payage';
	$about['link_version'] = "http://www.lesarbresdesign.info/version-history/payage";
	$about['link_doc'] = "http://www.lesarbresdesign.info/extensions/payage";
	$about['link_rating'] = "http://extensions.joomla.org/extensions/e-commerce/payment-systems/26898";
    
	$about['extra'][0]['left']  = 'Open SSL version';
	$about['extra'][0]['right'] = OPENSSL_VERSION_TEXT;
    
	$about['extra'][1]['left']  = 'CURL version ';
    if (function_exists('curl_init'))
		{
        $curl_info = curl_version();
		$about['extra'][1]['right'] = $curl_info['version']; // curl 7.34.0 was the first to claim support for TLSv1.2
		}
    else
        $about['extra'][1]['right'] = 'Not installed';
        
	echo '<h3>'.$about['name'].': '.JText::_($about['prefix'].'_HELP_TITLE').'</h3>';
    echo '<fieldset class="lad-fieldset lad-half">';
	$this->draw_about($about);
    echo '</fieldset>';
	
// show the installed gateways and contact details

	if (!empty($this->gateway_list))
		{
        echo '<fieldset class="lad-fieldset lad-half">';
		echo '<h4>'.JText::_('COM_PAYAGE_GATEWAYS_INSTALLED').'</h4>';
		$k = 0;
		echo '<table class="table table-striped table-bordered table-condensed width-auto">';
		foreach ($this->gateway_list as $gateway_info)
			{
			echo '<tr class="row'.$k.'">';
			echo '<td>'.$gateway_info['longName'].'</td>';
			echo '<td>'.$gateway_info['version'].'</td>';
			echo '<td>'.JHtml::link($gateway_info['authorUrl'], $gateway_info['author'],'target="_blank"').'</td>';
			echo '<td>'.JHtml::link($gateway_info['helpUrl'], JText::_('JHELP'), 'target="_blank"').'</td>';
			echo '<td>'.JHtml::link($gateway_info['docUrl'], JText::_('COM_PAYAGE_DOCUMENTATION'), 'target="_blank"').'</td>';
			$k = 1 - $k;
			echo '</tr>';
			}
		echo '</table>';
        echo '</fieldset>';
		}
	else
		echo '<h4>'.JText::_('COM_PAYAGE_NO_GATEWAYS').'</h4>';
		
// show the trace controls

	echo '<p></p>';
	echo LAPG_trace::make_trace_controls();
	echo '<p></p>';				// some blank lines so that we can get to the trace controls
	echo '<p></p>';
	echo '<p></p>';
    LAPG_view::viewEnd();
}

//------------------------------------------------------------------------------
// draw the about screen - this is the same in all our components
// (slightly non-standard, the top title is drawn above)
//
function draw_about($about)
{
	if ($about['link_rating'] != '')
		{
		echo '<p><span style="font-size:120%;font-weight:bold;">'.JText::_($about['prefix'].'_HELP_RATING').' ';
		echo JHTML::link($about['link_rating'], 'Joomla Extensions Directory', 'target="_blank"').'</span></p>';
		}

	echo '<table class="table table-striped table-bordered width-auto">';
	
	echo '<tr><td>'.JText::_($about['prefix'].'_VERSION').'</td>';
	echo '<td>'.$about['current_version'].'</td></tr>';
	
	if ($about['latest_version'] != '')
		echo '<tr><td>'.JText::_($about['prefix'].'_LATEST_VERSION').'</td><td>'.$about['latest_version'].'</td></tr>';

	echo '<tr><td>'.JText::_($about['prefix'].'_HELP_CHECK').'</td>';
	echo '<td>'.JHTML::link($about['link_version'], 'Les Arbres Design - '.$about['name'], 'target="_blank"').'</td></tr>';

	$pdf_icon = JHTML::_('image', JURI::root().'administrator/components/com_'.$about['reference'].'/assets/pdf_16.gif','');
	echo '<tr><td>'.$pdf_icon.' '.JText::_($about['prefix'].'_HELP_DOC').'</td>';
	echo '<td>'.JHTML::link($about['link_doc'], "www.lesarbresdesign.info", 'target="_blank"').'</td></tr>';

	$link_jed = "http://extensions.joomla.org/extensions/owner/chrisguk";
	$link_ext = "http://www.lesarbresdesign.info/";

	echo '<tr><td>'.JText::_($about['prefix'].'_HELP_LES_ARBRES').'</td>';
	echo '<td>'.JHTML::link("http://www.lesarbresdesign.info/", 'Les Arbres Design', 'target="_blank"').'</td></tr>';
		
	if (!empty($about['extra']))
		foreach($about['extra'] as $row)
			echo '<tr><td>'.$row['left'].'</td><td>'.$row['right'].'</td></tr>';

	echo '</table>';
}
	
//------------------------------------------------------------------------------
// get the latest version info
//
function get_version($product)
{
    $url = 'http://www.lesarbresdesign.info/jupdate?product='.$product.'&src=about';
    try
        {
        $http = JHttpFactory::getHttp();
        $response = $http->get($url, array(), 20);
        }
    catch (RuntimeException $e)
        {
        return '';
        }
    $version = self::str_between($response->body,'<version>','</version>');
	return $version;
}
				
function str_between($string, $start, $end)
{
    $string = ' '.$string;
    $pos = strpos($string, $start);
    if ($pos == 0)
        return '';
    $pos += strlen($start);
    $len = strpos($string, $end, $pos) - $pos;
    return substr($string, $pos, $len);
}
				
			
}