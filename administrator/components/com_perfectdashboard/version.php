<?php
/**
 * @package     perfectdashboard
 * @version     1.4.0
 *
 * @copyright   Copyright (C) 2016 Perfect Web. All rights reserved. https://www.perfect-web.co
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;

if (!defined('PERFECTDASHBOARD_VERSION')) {
    $perfectdashboard = simplexml_load_file(dirname(__FILE__).'/perfectdashboard.xml');
    define('PERFECTDASHBOARD_VERSION', (string) $perfectdashboard->version);
    unset($perfectdashboard);
}
if (!defined('PERFECTDASHBOARD_ADDWEBSITE_URL')) {
    define('PERFECTDASHBOARD_ADDWEBSITE_URL', 'https://app.perfectdashboard.co/my-websites/site-addchild');
}
