<?php
/**
 * @version 3.4 2015-05-25
 * @package Joomla
 * @subpackage Knowledge Base
 * @copyright (C) 2015 the Thinkery LLC. All rights reserved.
 * @license GNU/GPL see LICENSE
 */

// no direct access
defined('_JEXEC') or die('Restricted access');
jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');

// no direct access
defined('_JEXEC') or die;

// Load FOF if not already loaded
if (!defined('F0F_INCLUDED'))
{
    $paths = array(
        (defined('JPATH_LIBRARIES') ? JPATH_LIBRARIES : JPATH_ROOT . '/libraries') . '/f0f/include.php',
        __DIR__ . '/fof/include.php',
    );

    foreach ($paths as $filePath)
    {
        if (!defined('F0F_INCLUDED') && file_exists($filePath))
        {
            @include_once $filePath;
        }
    }
}

class Com_KnowledgebaseInstallerScript extends F0FUtilsInstallscript
{
    protected $componentName = 'com_knowledgebase';
    protected $componentTitle = 'KnowledgeBase by The Thinkery LLC';
    protected $minimumPHPVersion = '5.3.4';
    protected $minimumJoomlaVersion = '3.2.1';

    protected function renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent)
    {
        $this->warnAboutJSNPowerAdmin();

        ?>
        <h1>KnowledgeBase by The Thinkery LLC</h1>

        <img src="../media/com_akeebasubs/images/akeebasubs-48.png" width="48" height="48" alt="Akeeba Subscriptions"
             align="left"/>
        <h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">Welcome to KnowledgeBase!</h2>
        <span>A kickass KB system from a kincass company.</span>
        <?php
        parent::renderPostInstallation($status, $fofInstallationStatus, $strapperInstallationStatus, $parent);
    }

    protected function renderPostUninstallation($status, $parent)
    {
        ?>
        <h2 style="font-size: 14pt; font-weight: black; padding: 0; margin: 0 0 0.5em;">&nbsp;Akeeba Subscriptions
            Uninstallation</h2>
        <p>We are sorry that you decided to uninstall KnowledgeBase. Please let us know why by using the Contact
            Us form on our site. We appreciate your feedback.</p>

        <?php
        parent::renderPostUninstallation($status, $parent);
    }
}
