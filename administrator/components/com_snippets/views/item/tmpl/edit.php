<?php
/**
 * @package         Snippets
 * @version         6.2.0
 * 
 * @author          Peter van Westen <info@regularlabs.com>
 * @link            http://www.regularlabs.com
 * @copyright       Copyright © 2017 Regular Labs All Rights Reserved
 * @license         http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
 */

defined('_JEXEC') or die;

use RegularLabs\Library\Document as RL_Document;
use RegularLabs\Library\Language as RL_Language;

JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidator');
JHtml::_('formbehavior.chosen', 'select');

RL_Language::load('com_content', JPATH_ADMINISTRATOR);

RL_Document::script('regularlabs/script.min.js');
RL_Document::style('regularlabs/style.min.css');
?>

<form action="<?php echo JRoute::_('index.php?option=com_snippets&id=' . ( int ) $this->item->id); ?>" method="post"
      name="adminForm" id="item-form" class="form-validate form-horizontal">

	<div class="row-fluid">
		<div class="span9 span-md-8">
			<?php echo $this->render($this->item->form, '-content', JText::_('RL_CONTENT')); ?>
		</div>
		<div class="span3 span-md-4 form-vertical">
			<?php echo $this->render($this->item->form, 'details', JText::_('JDETAILS')); ?>
		</div>
	</div>

	<input type="hidden" name="task" value="">
	<?php echo JHtml::_('form.token'); ?>
</form>

<script language="javascript" type="text/javascript">
	Joomla.submitbutton = function(task) {
		var f = document.getElementById('item-form');
		if (task == 'item.cancel') {
			Joomla.submitform(task, f);
			return;
		}

		// do field validation
		if (f['jform[name]'].value.trim() == "") {
			alert("<?php echo JText::_('SNP_THE_ITEM_MUST_HAVE_A_NAME', true); ?>");
		} else if (f['jform[alias]'].value.trim() == "") {
			alert("<?php echo JText::_('SNP_THE_ITEM_MUST_HAVE_AN_ID', true); ?>");
		} else {
			Joomla.submitform(task, f);
		}
	}
</script>
