<?php
/********************************************************************
Product		: Payage
Date		: 2 June 2017
Copyright	: Les Arbres Design 2014-2017
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

if (class_exists("LAPG_view"))
	return;

class LAPG_view extends JViewLegacy
{

//-------------------------------------------------------------------------------
// Make a select list
// $name          : Field name
// $current_value : Current value
// $list          : Array of ID => value items
// $first         : ID of first item to be placed in the list
// $extra         : Javascript or styling to be added to <select> tag
// $no_id         : if true, no "id" attribute is added
//
static function make_list($name, $current_value, &$items, $first = 0, $extra='', $no_id=false)
{
	if ($no_id)
		$id_attribute = '';
	else
		$id_attribute = ' id="'.$name.'"';
		
	$html = "\n".'<select name="'.$name.'"'.$id_attribute.' size="1" '.$extra.'>';
	if ($items == null)
		return '';
	foreach ($items as $key => $value)
		{
		if (strncmp($key,"OPTGROUP_START",14) == 0)
			{
			$html .= "\n".'<optgroup label="'.$value.'">';
			continue;
			}
		if (strncmp($key,"OPTGROUP_END",12) == 0)
			{
			$html .= "\n".'</optgroup>';
			continue;
			}
		if ($key < $first)					// skip unwanted entries
			{
			continue;
			}
		$selected = '';

		if ($current_value == $key)
			$selected = ' selected="selected"';
		$html .= "\n".'<option value="'.$key.'"'.$selected.'>'.$value.'</option>';
		}
	$html .= '</select>'."\n";

	return $html;
}

//-------------------------------------------------------------------------------
// Make an info button
//
static function make_info($title, $link='')
{
    if (empty($title))
        return '';
	JHtml::_('bootstrap.tooltip');
	if ($link == '')
		{
		$icon_name = 'info-16.png';
		$html = '';
		}
	else
		{
		$icon_name = 'link-16.png';
		$html = '<a href="'.$link.'" target="_blank">';
		}

	$icon = '<img src="'.LAPG_ADMIN_ASSETS_URL.$icon_name.'" alt="" />';
	$html .= '<span class="hasTooltip" title="'.htmlspecialchars($title, ENT_COMPAT, 'UTF-8').'">'.$icon.'</span>';
		
	if ($link != '')
		$html .= '</a>';
		
	return $html;
}

//---------------------------------------------------------------------------------------------
// Make a date picker field for the back end
//
static function make_date_picker($field_name, $date_value, $format = '%Y-%m-%d' )
{
    $form = JForm::getInstance('a_form', '<form> </form>');
    $element = new SimpleXMLElement('<fieldset name="a_fieldset"><field name="'.$field_name.'" type="calendar" showtime="false" format="'.$format.'" size="12" /></fieldset>');
    $form->setField($element);
    $form->setValue($field_name, null, $date_value);
    $html = '<div class="lad-date">'.$form->getInput($field_name).'</div>';    
    return $html;
}

// -------------------------------------------------------------------------------
// Draw the menu and make the current item active
//
static function addSubMenu($submenu = '')
{
    JHtmlSidebar::addEntry(JText::_('COM_PAYAGE_PAYMENTS'), 'index.php?option=com_payage&controller=payment', $submenu == 'payment');
    JHtmlSidebar::addEntry(JText::_('COM_PAYAGE_ACCOUNTS'), 'index.php?option=com_payage&controller=account', $submenu == 'account');
    JHtmlSidebar::addEntry(JText::_('COM_PAYAGE_REPORTS'), 'index.php?option=com_payage&controller=report', $submenu == 'report');
    JHtmlSidebar::addEntry(JText::_('COM_PAYAGE_ABOUT'), 'index.php?option=com_payage&controller=about', $submenu == 'about');
}

// -------------------------------------------------------------------------------
// Draw the sidebar, if there are any entries for it
// - this must be called at the start of every view
//
static function viewStart()
{
	$entries = JHtmlSidebar::getEntries();
	
	if (empty($entries))
		{
		echo '<div id="j-main-container">';
		return;
		}
		
	$sidebar = JHtmlSidebar::render();
	echo '<div id="j-sidebar-container" class="span2">';
	echo "$sidebar";
	echo "</div>";
	echo '<div id="j-main-container" class="span10">';
}
  
// -------------------------------------------------------------------------------
// This must be called at the end of every view that calls viewStart()
//
static function viewEnd()
{
	echo "</div>";
}

//-------------------------------------------------------------------------------
// Check that a date is valid YYYY-MM-DD
// Returns true if valid, false if not
//
static function validDate($date, $allow_blank = false)
{
	if (($allow_blank) and (($date == '') or ($date == '0000-00-00')))
		return true;
	if (strlen($date) != 10)
		return false;
	if (($date{4} != '-') or ($date{7} != '-'))
		return false;
	if (!is_numeric(substr($date,5,2)))
		return false;
	if (!is_numeric(substr($date,8,2)))
		return false;
	if (!is_numeric(substr($date,0,4)))
		return false;
	$status = checkdate(substr($date,5,2),		// month
				 substr($date,8,2),			// day
				 substr($date,0,4));		// year
	return $status;
}

} // class