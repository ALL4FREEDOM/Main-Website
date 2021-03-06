<?php
/**
 * @package      ITPMeta
 * @subpackage   Component
 * @author       Todor Iliev
 * @copyright    Copyright (C) 2017 Todor Iliev <todor@itprism.com>. All rights reserved.
 * @license      GNU General Public License version 3 or later; see LICENSE.txt
 */

// no direct access
defined('_JEXEC') or die;

/**
 * It is the component helper class.
 */
class ItpmetaHelper
{
    private static $extension = 'com_itpmeta';

    /**
     * Configure the Linkbar.
     *
     * @param    string    $vName The name of the active view.
     *
     * @since    1.6
     */
    public static function addSubmenu($vName = 'dashboard')
    {
        JHtmlSidebar::addEntry(
            JText::_('COM_ITPMETA_DASHBOARD'),
            'index.php?option=' . self::$extension . '&view=dashboard',
            $vName === 'dashboard'
        );

        JHtmlSidebar::addEntry(
            JText::_('COM_ITPMETA_GLOBALS_TAGS'),
            'index.php?option=' . self::$extension . '&view=globals',
            $vName === 'globals'
        );

        JHtmlSidebar::addEntry(
            JText::_('COM_ITPMETA_URLS_MANAGER'),
            'index.php?option=' . self::$extension . '&view=urls',
            $vName === 'urls'
        );

        JHtmlSidebar::addEntry(
            JText::_('COM_ITPMETA_PLUGINS'),
            'index.php?option=com_plugins&view=plugins&filter_search=itpmeta',
            $vName === 'plugins'
        );
    }
}
