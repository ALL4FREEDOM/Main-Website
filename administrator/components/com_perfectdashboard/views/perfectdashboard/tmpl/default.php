<?php
/**
 * @package     perfectdashboard
 * @version     1.4.0
 *
 * @copyright   Copyright (C) 2016 Perfect Web. All rights reserved. https://www.perfect-web.co
 * @license     GNU General Public Licence http://www.gnu.org/licenses/gpl-3.0.html
 */

defined('_JEXEC') or die;
?>
<div class="row-fluid perfectdashboard">
    <?php if (empty($this->secure_key)) : ?>
        <h2 class="text-error"><?php echo JText::_('COM_PERFECTDASHBOARD_AUTHENTICATION_INFO'); ?></h2>
    <?php else : ?>

        <?php if( strlen($this->ping) === 19 ) : ?>
            <?php if (empty($this->extensions_view_information)) : ?>
                <div class="perfectdashboard-success-view">
                    <h1><?php echo JText::_('COM_PERFECTDASHBOARD_SUCCESS_TITLE'); ?></h1>
                    <h2><?php echo JText::sprintf('COM_PERFECTDASHBOARD_SUCCESS_SUBTITLE', '<a href="https://app.perfectdashboard.co/?utm_source=backend&utm_medium=installer&utm_campaign=JED" target="_blank">app.perfectdashboard.co</a>'); ?></h2>
        
                    <ul class="unstyled perfectdashboard-list-features">
                        <li><i class="icon-checkmark text-success"></i> <?php echo JText::_('COM_PERFECTDASHBOARD_SUCCESS_LIST_ITEM1'); ?></li>
                        <li><i class="icon-checkmark text-success"></i> <?php echo JText::_('COM_PERFECTDASHBOARD_SUCCESS_LIST_ITEM2'); ?></li>
                        <li><i class="icon-checkmark text-success"></i> <?php echo JText::_('COM_PERFECTDASHBOARD_SUCCESS_LIST_ITEM3'); ?></li>
                    </ul>
        
                    <button type="button" onclick="document.getElementById('perfect-dashboard-install').submit()" class="btn btn-large">
                        <?php echo JText::_('COM_PERFECTDASHBOARD_ADD_WEBSITE_AGAIN_BUTTON'); ?>
                    </button>
                </div>
            <?php else : ?>
                <?php echo $this->extensions_view_information; ?>
            <?php endif; ?>
        <?php else: ?>
            <div class="span6">
                <h1>
                    <?php echo JText::_('COM_PERFECTDASHBOARD_ADD_WEBSITE_INFO'); ?>
                    <span><?php echo JText::_('COM_PERFECTDASHBOARD_ADD_WEBSITE_INFO_BADGE'); ?></span>
                </h1>

                <ul class="unstyled perfectdashboard-list-features">
                    <li><i class="icon-checkmark text-success"></i> <?php echo JText::_('COM_PERFECTDASHBOARD_FEATURE1'); ?></li>
                    <li><i class="icon-checkmark text-success"></i> <?php echo JText::_('COM_PERFECTDASHBOARD_FEATURE2'); ?></li>
                    <li><i class="icon-checkmark text-success"></i> <?php echo JText::_('COM_PERFECTDASHBOARD_FEATURE3'); ?></li>
                </ul>

                <button type="button" onclick="document.getElementById('perfect-dashboard-install').submit()" class="btn btn-large btn-success perfectdashboard-big-btn">
                    <?php echo JText::_('COM_PERFECTDASHBOARD_ADD_WEBSITE_BUTTON'); ?>
                </button>

                <ul class="unstyled perfectdashboard-list-presale">
                    <li><?php echo JText::_('COM_PERFECTDASHBOARD_PRESALE1'); ?></li>
                    <li><?php echo JText::_('COM_PERFECTDASHBOARD_PRESALE2'); ?></li>
                    <li><?php echo JText::_('COM_PERFECTDASHBOARD_PRESALE3'); ?></li>
                </ul>
            </div>

            <div class="span6">
                <div class="perfectdashboard-computer">
                    <img src="<?php echo JURI::root(true) . '/media/com_perfectdashboard/images/laptop.svg'; ?>" class="perfectdashboard-computer-img" alt="">
                    <video src="<?php echo JURI::root(true) . '/media/com_perfectdashboard/images/laptop.mp4'; ?>" class="perfectdashboard-computer-video" autoplay loop poster="<?php echo JURI::root(true) . '/media/com_perfectdashboard/images/laptop_poster.png'; ?>"></video>
                </div>
            </div>
        <?php endif; ?>

        <form action="<?php echo PERFECTDASHBOARD_ADDWEBSITE_URL; ?>?utm_source=backend&amp;utm_medium=installer&amp;utm_campaign=JED" method="post" enctype="multipart/form-data" id="perfect-dashboard-install">
            <input type="hidden" name="secure_key" value="<?php echo $this->secure_key; ?>">
            <input type="hidden" name="user_email" value="<?php echo $this->user_email; ?>">
            <input type="hidden" name="site_frontend_url" value="<?php echo JUri::root(false); ?>">
            <input type="hidden" name="site_backend_url" value="<?php echo JUri::base(false); ?>">
            <input type="hidden" name="cms_type" value="joomla">
            <input type="hidden" name="version" value="<?php echo PERFECTDASHBOARD_VERSION; ?>">
        </form>

    <?php endif; ?>
</div>

