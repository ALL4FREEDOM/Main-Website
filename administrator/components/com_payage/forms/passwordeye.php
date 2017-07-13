<?php
/********************************************************************
Product		: Payage
Date		: 7 October 2016
Copyright	: Les Arbres Design 2014-2016
Contact		: http://www.lesarbresdesign.info
Licence		: GNU General Public License
*********************************************************************/
defined('_JEXEC') or die('Restricted Access');

class JFormFieldPasswordEye extends JFormField
{
protected $type = 'PasswordEye';

public function getInput()
{
	$name     = ' name="'.$this->name.'"';
	$id       = ' id="'.$this->name.'"';
	$size     = !empty($this->size) ? ' size="' . $this->size . '"' : '';
	$required = $this->required ? ' required aria-required="true"' : '';
	$value    = ' value="'.htmlspecialchars($this->value, ENT_COMPAT, 'UTF-8').'"';
	$onclick  = "if (document.getElementById('".$this->name."').type == 'text') 
			document.getElementById('".$this->name."').type = 'password'; 
		else 
			document.getElementById('".$this->name."').type = 'text';";
    $eye  = ' <span class="icon-eye" onclick="'.$onclick.'" style="cursor:pointer;"></span>';

	return '<input type="password"'.$name.$id.$value.$size.$required.'" /> '.$eye;
}

}
