<?php
/**
 * @package     perfectdashboard
 * @version     1.4.0
 *
 * @copyright   Copyright (C) 2016 Perfect Web. All rights reserved. https://www.perfect-web.co
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */
defined('_JEXEC') or die('Restricted access');

class com_perfectdashboardInstallerScript
{

    protected $adapter = null;
    protected $key     = null;
    protected $params  = null;

    /**
     * Called before any type of action
     *
     * @param   string $route Which action is happening (install|uninstall|discover_install|update)
     * @param   JAdapterInstance $adapter The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function preflight($route, JAdapterInstance $adapter)
    {
        $this->adapter = $adapter;

        if (version_compare(PHP_VERSION, '5.3.0') == -1) {
            $app = JFactory::getApplication();

            $app->enqueueMessage('Perfect Dashboard requires at least PHP 5.3.0 version',
                'error');

            return false;
        }
    }

    /**
     * Called after any type of action
     *
     * @param   string $route Which action is happening (install|uninstall|discover_install|update)
     * @param   JAdapterInstance $adapter The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function postflight($route, JAdapterInstance $adapter)
    {
        if ($route == 'install' || $route == 'discover_install') {
            $app = JFactory::getApplication();

            $this->setSecureKey();

            try {
                $this->setWAFExceptions();
            } catch (Exception $ex) {
                
            }

            $app->enqueueMessage(JText::sprintf('COM_PERFECTDASHBOARD_INSTALLATION_SUCCESS',
                    JRoute::_('index.php?option=com_perfectdashboard')), 'message');
        }
        
        if ($route == 'install' || $route == 'update') {

            $session = JFactory::getSession();
            if ($session->get('redirect', null, 'perfectdashboard')) {
                $session->set('redirect', null, 'perfectdashboard');

                $this->referral = $session->get('referral', null, 'perfectdashboard');
                if ($this->referral) {
                    $this->saveConfig(array('referral' => $this->referral));
                }

                $this->addWebsite();
            }
        }

        $this->deleteExternalFiles();

        return true;
    }

    /**
     * Called on installation
     *
     * @param   JAdapterInstance $adapter The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function install(JAdapterInstance $adapter)
    {

    }

    /**
     * Called on uninstallation
     *
     * @param   JAdapterInstance $adapter The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function uninstall(JAdapterInstance $adapter)
    {
        $this->deleteExternalFiles();
        $this->uninstallBackupTool();
    }

    /**
     * Called on update
     *
     * @param   JAdapterInstance $adapter The object responsible for running this script
     *
     * @return  boolean  True on success
     */
    public function update(JAdapterInstance $adapter)
    {
        $this->setBackupToolFolderConfig();
    }

    public function uninstallBackupTool()
    {
        jimport('joomla.filesystem.folder');

        $ak_access = $this->getConfig('ak_access');

        // For childs installed before version 1.1
        if (!empty($ak_access)) {
            $ak_access     = unserialize(call_user_func('ba'.'se'.'64'.'_decode', $ak_access));
            $perfix_db     = $ak_access['ak_prefix_db'];
            $perfix_folder = $ak_access['ak_prefix_folder'];

            $akeeba_folder = $perfix_folder.'perfectdashboard_akeeba/';
            $akeeba_path   = JPATH_ROOT.'/'.$akeeba_folder;

            // Delete folder.
            if (JFolder::exists($akeeba_path)) {
                JFolder::delete($akeeba_path);
            }

            $db = JFactory::getDbo();

            $db->setQuery("DROP TABLE IF EXISTS `".$perfix_db."perfectdashboard_akeeba_akeeba_common` ,
                `".$perfix_db."perfectdashboard_akeeba_ak_params` ,
                `".$perfix_db."perfectdashboard_akeeba_ak_profiles` ,
                `".$perfix_db."perfectdashboard_akeeba_ak_stats` ,
                `".$perfix_db."perfectdashboard_akeeba_ak_storage` ,
                `".$perfix_db."perfectdashboard_akeeba_ak_users` ;");

            try {
                $db->execute();
            } catch (Exception $ex) {

            }
        } elseif (($backup_dir = $this->getConfig('backup_dir'))) {
            // For childs version >=1.1
            $backup_path   = JPATH_ROOT.'/'.$backup_dir;
            
            // Get db prefix from akeeba config file.
            $config_file = $backup_path.'/Solo/assets/private/config.php';

            jimport('joomla.filesystem.file');

            if (JFile::exists($config_file)) {
                $config = @file_get_contents($config_file);

                if ($config !== false) {
                    $config = explode("\n", $config, 2);

                    if (count($config) >= 2) {
                        $config = new JRegistry($config[1]);
                        $prefix_db = $config->get('prefix');

                        if ($prefix_db) {
                            $db = JFactory::getDbo();

                            $db->setQuery("DROP TABLE IF EXISTS `".$prefix_db."akeeba_common` ,
                                `".$prefix_db."ak_params` ,
                                `".$prefix_db."ak_profiles` ,
                                `".$prefix_db."ak_stats` ,
                                `".$prefix_db."ak_storage` ,
                                `".$prefix_db."ak_users` ;");

                            try {
                                $db->execute();
                            } catch (Exception $ex) {

                            }
                        }
                    }

                }
            }

            // Delete folder.
            if (JFolder::exists($backup_path)) {
                JFolder::delete($backup_path);
            }
        }

        return true;
    }

    protected function saveConfig($config = array())
    {
        $params = $this->getParams();
        $params->loadArray($config, true);

        // Save the parameters
        $table = JTable::getInstance('extension');
        try {
            $table->load(array('type' => 'component', 'element' => 'com_perfectdashboard'));
            $table->bind(array('params' => $params->toString()));

            $table->check();
            return $table->store();
        } catch (Exception $e) {
            return false;
        }
    }

    protected function getParams()
    {
        if (empty($this->params)) {
            $db = JFactory::getDbo();
            $query = $db->getQuery(true);

            $query->select($db->quoteName('params'))
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('type') . '=' . $db->quote('component'))
                ->where($db->quoteName('element') . '=' . $db->quote('com_perfectdashboard'));

            $db->setQuery($query);

            try {
                $result = $db->loadResult();
            } catch (Exception $e) {
                return;
            }

            $this->params = new JRegistry($result);
        }

        return $this->params;
    }

    protected function getConfig($key, $default = null)
    {
        $params = $this->getParams();

        return $params->get($key, $default);
    }

    protected function setBackupToolFolderConfig()
    {
        $ak_access     = $this->getConfig('ak_access');

        // For childs installed before version 1.1
        if (!empty($ak_access)) {
            $ak_access     = unserialize(call_user_func('ba'.'se'.'64'.'_decode', $ak_access));
            $perfix_folder = $ak_access['ak_prefix_folder'];

            $backup_dir = $perfix_folder.'perfectdashboard_akeeba/';
        } else {
            $backup_dir = $this->getConfig('backup_dir');
        }

        if ($backup_dir) {
            $backup_path = JPATH_ROOT.'/'.$backup_dir;
            
            jimport('joomla.filesystem.file');
            if (JFile::exists($backup_path.'/backups/.htaccess')) {
                JFile::delete($backup_path.'/backups/.htaccess');
            }
            if (JFile::exists($backup_path.'/backups/.htpasswd')) {
                JFile::delete($backup_path.'/backups/.htpasswd');
            }
            if (JFile::exists($backup_path.'/tmp/.htaccess')) {
                JFile::copy('tmp/.htaccess', 'backups/.htaccess',$backup_path);
            }
        }
    }

    protected function setSecureKey()
    {
        $this->key = md5(uniqid('perfectsecurekey'));
        return $this->saveConfig(array('key' => $this->key));
    }

    protected function deleteExternalFiles()
    {
        // Remove external files.
        $dir_external_files = JPATH_ROOT.'/external_files';

        jimport('joomla.filesystem.folder');
        jimport('joomla.filesystem.file');

        if (JFolder::exists($dir_external_files)) {
            // Remove README.txt file.
            $file_readme = $dir_external_files.'/README.txt';
            if (JFile::exists($file_readme)) {
                JFile::delete($file_readme);
            }

            // Remove solo directory.
            $dir_solo = $dir_external_files.'/solo';
            if (JFolder::exists($dir_solo)) {
                JFolder::delete($dir_solo);
            }

            // If there aren't any files left in external_files, then remove also the folder.
            $files_rest = JFolder::files($dir_external_files);
            if (empty($files_rest)) {
                JFolder::delete($dir_external_files);
            }
        }
    }

    /**
     * Set Web Application Firewall exceptions.
     */
    private function setWAFExceptions()
    {
        jimport('joomla.filesystem.folder');

        // Set Web Application Firewall exceptions for Admin Tools.
        if (JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_admintools')) {
            $pdash_address_1 = '46.101.68.150';
            $pdash_address_2 = '46.101.7.25';
            // First - add dashboard ips to admin tools ip white list.
            $db = JFactory::getDbo();

            // Check if addresses aren't already added.
            $query = $db->getQuery(true);

            $query->select($db->quoteName('ip'))
                ->from($db->quoteName('#__admintools_adminiplist'))
                ->where($db->quoteName('ip').' IN ('.$db->quote($pdash_address_1).','.$db->quote($pdash_address_2).')');

            $db->setQuery($query);

            $added_pdash_addresses = $db->loadColumn();

            // If address doesn't exist, then add it.
            if (empty($added_pdash_addresses) || !in_array($pdash_address_1, $added_pdash_addresses)) {
                $address_1 = new stdClass();
                $address_1->ip = $pdash_address_1;
                $address_1->description = 'Perfect Dashboard';
                $db->insertObject('#__admintools_adminiplist', $address_1);
            }

            // If address doesn't exist, then add it.
            if (empty($added_pdash_addresses) || !in_array($pdash_address_2, $added_pdash_addresses)) {
                $address_2 = new stdClass();
                $address_2->ip = $pdash_address_2;
                $address_2->description = 'Perfect Dashboard';
                $db->insertObject('#__admintools_adminiplist', $address_2);
            }

            // Second - add dashboard ips to admin tools neverblockips cparams (_admintools_storage table in db).
            $query = $db->getQuery(true);

            $query->select($db->quoteName('value'))
                ->from($db->quoteName('#__admintools_storage'))
                ->where($db->quoteName('key').' = '.$db->quote('cparams'));

            $db->setQuery($query);

            $res = $db->loadResult();

            $config   = new JRegistry($res);
            $neverblockips = $config->get('neverblockips');

            if (empty($neverblockips)) {
                $neverblockips = $pdash_address_1.','.$pdash_address_2;
            } else {
                if (strpos($neverblockips, $pdash_address_1) === false) {
                    $neverblockips .= ','.$pdash_address_1;
                }
                if (strpos($neverblockips, $pdash_address_2) === false) {
                    $neverblockips .= ','.$pdash_address_2;
                }
            }

            $config->set('neverblockips', $neverblockips);

            $query = $db->getQuery(true);

            $query->update($db->quoteName('#__admintools_storage'))
                ->set($db->quoteName('value').' = '.$db->quote($config->toString()))
                ->where($db->quoteName('key').' = '.$db->quote('cparams'));

            $db->setQuery($query);

            $db->execute();  
        }

        // Handle sh404sef
        if (JFolder::exists(JPATH_ADMINISTRATOR.'/components/com_sh404sef') && !empty(JComponentHelper::getComponent('com_sh404sef')->id)) {
            $com_params = JComponentHelper::getParams('com_sh404sef');
            $com_params->set('com_perfectdashboard___manageURL', '3');
            $com_params->set('com_perfectdashboard___translateURL', '1');
            $com_params->set('com_perfectdashboard___insertIsoCode', '1');
            $com_params->set('com_perfectdashboard___shDoNotOverrideOwnSef', '1');
            $com_params->set('com_perfectdashboard___compEnablePageId', '0');
            $com_params->set('com_perfectdashboard___defaultComponentString', '');

            $com_id = JComponentHelper::getComponent('com_sh404sef')->id;
            $table  = JTable::getInstance('extension');
            $table->load($com_id);
            $table->save(array('params' => $com_params->toString()));
        }
    }

    protected function cleanupInstall()
    {
        $parent     = $this->adapter->getParent();
        $extractdir = $parent->getPath('source');
        if (is_dir($extractdir)) {
            JFolder::delete($extractdir);
        }

        $files = JFolder::files(dirname($extractdir), 'perfect.*dashboard.*\.zip', false, true);
        if (is_array($files) && count($files)) {
            JFile::delete($files);
        }
    }

    protected function addWebsite()
    {
        $parent = $this->adapter->getParent();
        $path   = $parent->getPath('extension_administrator');

        @include_once $path.'/version.php';

        $user = JFactory::getUser();

        if (empty($this->key)) {
            $this->key = $this->getConfig('key');
        }

        echo '<!DOCTYPE html>'."\r\n"
        .'<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr">'
        .'<head>'
        .'<meta http-equiv="content-type" content="text/html; charset=utf-8">'
        .'<title>Perfect Dashboard</title>'
        .'</head>'
        .'<body>'
        .'<form action="'.PERFECTDASHBOARD_ADDWEBSITE_URL.'?utm_source=backend&utm_medium=updater&utm_campaign='
        .($this->referral ? $this->referral : 'developer').'"'
        .' method="post" enctype="multipart/form-data" name="pd_installer">'
        .'<input type="hidden" name="referral_key" value="'.$this->referral.'">'
        .'<input type="hidden" name="secure_key" value="'.$this->key.'">'
        .'<input type="hidden" name="user_email" value="'.$user->email.'">'
        .'<input type="hidden" name="site_frontend_url" value="'.JUri::root(false).'">'
        .'<input type="hidden" name="site_backend_url" value="'.JUri::base(false).'">'
        .'<input type="hidden" name="cms_type" value="joomla">'
        .'<input type="hidden" name="version" value="'.PERFECTDASHBOARD_VERSION.'">'
        .'Redirecting to Perfect Dashboard... '
        .'<button type="submit" id="pd_submit">Click to continue</button>'
        .'</form>'
        .'<script type="text/javascript">'
        .'var pd_submit=document.getElementById("pd_submit");'
        .'pd_submit.style.display="none";'
        .'document.pd_installer.submit();'
        .'setTimeout(function(){pd_submit.style.display=""},1000);'
        .'</script>'
        .'</body>'
        .'</html>';

        $this->cleanupInstall();

        die();
    }
}