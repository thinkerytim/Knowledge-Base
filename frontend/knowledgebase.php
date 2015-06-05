<?php
/**
 * @version 3.4.1 2015-05-26
 * @package Joomla
 * @subpackage KnowledgeBase
 * @copyright (C) 2015 the Thinkery LLC. All rights reserved.
 * @license GNU/GPL see LICENSE
 */

defined( '_JEXEC' ) or die( 'Restricted access');
jimport('joomla.application.component.controller');

require_once(JPATH_COMPONENT.'/helpers/route.php');

$controller = JControllerLegacy::getInstance('Knowledgebase');
$controller->execute(JFactory::getApplication()->input->get('task'));
$controller->redirect();