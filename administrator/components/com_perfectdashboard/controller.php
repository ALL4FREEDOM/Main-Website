<?php
/**
 * @package     perfectdashboard
 * @version     1.2.0
 *
 * @copyright   Copyright (C) 2015 Perfect Web. All rights reserved. http://www.perfect-web.co
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

/**
 * Component Controller
 */
class PerfectdashboardController extends JControllerLegacy
{
	/**
	 * @var		string	The default view.
	 */
	protected $default_view = 'perfectdashboard';

	/**
	 * Method to display a view.
	 *
	 * @param   boolean  $cachable   If true, the view output will be cached
	 * @param   array    $urlparams  An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
	 *
	 * @return  JController		This object to support chaining.
	 */
	public function display($cachable = false, $urlparams = false)
	{
		$doc = JFactory::getDocument();
        $doc->addStyleSheet(JURI::root(true) . '/media/com_perfectdashboard/css/admin.css');

		parent::display($cachable, $urlparams);

		return $this;
	}
}
