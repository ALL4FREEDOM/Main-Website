<?php
/**
 * @package     perfectdashboard
 * @version     1.2.0
 *
 * @copyright   Copyright (C) 2015 Perfect Web. All rights reserved. http://www.perfect-web.co
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die;

require_once JPATH_ROOT.'/components/com_perfectdashboard/helpers/helper.php';
/**
 * View class for perfectdashboard.
 */
class PerfectdashboardViewPerfectdashboard extends JViewLegacy
{
    public $secure_key;
    public $user_email;
    /**
     * Display the view.
     *
     * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
     *
     * @return  mixed  A string if successful, otherwise an Error object.
     */
    public function display($tpl = null)
    {
        $user = JFactory::getUser();
        $com_params = PerfectDashboardHelper::getChildParams();

        $this->secure_key                   = $com_params->get('key');  
        $this->user_email                   = $user->get('email');
        $this->ping                         = $com_params->get('ping');
        $this->extensions_view_information  = $com_params->get('extensions_view_information');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode("\n", $errors));

            return false;
        }

        $this->addToolbar();
        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar()
    {
        $user = JFactory::getUser();

        // Get the toolbar object instance
        $bar = JToolBar::getInstance('toolbar');

        JToolbarHelper::title(JText::_('COM_PREFECTDASHBOARD_HEADER'),
            'address contact');

        if ($user->authorise('core.admin', 'com_perfectdashboard')) {
            JToolbarHelper::preferences('com_perfectdashboard');
        }
    }
}