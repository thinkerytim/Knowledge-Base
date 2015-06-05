<?php
/**
 * @version 3.4.1 2015-05-26
 * @package Joomla
 * @subpackage KnowledgeBase
 * @copyright (C) 2015 the Thinkery LLC. All rights reserved.
 * @license GNU/GPL see LICENSE
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.error.log');

class KnowledgebaseController extends JControllerLegacy
{
    var $log;
    var $debug;

    public function __construct()
    {
        $this->debug = false;
        if($this->debug) $this->log = JLog::getInstance('knowledgebase.log.php'); // create the logfile TODO: maybe add a debug switch to the backend to turn this off or on
        if($this->debug) $this->log->addEntry(array('COMMENT' => 'Constructing KnowledgeBase'));

        parent::__construct();
    }

    public function display($cachable = false, $urlparams = false)
    {
        $app        = JFactory::getApplication();
        $params     = JComponentHelper::getParams( 'com_knowledgebase' );
        $offline    = $params->get('offline');
        $document   = JFactory::getDocument();

        $document->addStyleSheet(JURI::root(true).'/components/com_knowledgebase/assets/css/knowledgebase.css');

        if( $offline == 1 ){
            echo '
                <div align="center" class="kb-offline">
                    '.JHtml::_('image', 'components/com_knowledgebase/assets/images/knowledgebase.png', JText::_('COM_WORKFORCE_NO_ACCESS')).'
                    <div>' . $params->get('offmessage') . '</div>
                </div>';
        }else{
            $accent_color           = $params->get('accent', '#777');
            $secondary_accent       = $params->get('secondary_accent', '#f7f7f7');

            $accentstyle    = '.wfrow1{background: '.$secondary_accent.';}
                               .kb_wrapper{border-top: solid 2px '.$accent_color.' !important;}
                               .kb_header{background-color: '.$accent_color.';}
                               .kb_container{border-color: '.$accent_color.';}';
            $document->addStyleDeclaration($accentstyle);

            $cachable       = true;
            $editid         = JRequest::getInt('id');
            $vName          = JRequest::getCmd('view', 'allemployees');

            JRequest::setVar('view', $vName);

            // We don't want to cache if user is logged in and managing own profile, etc OR when a search is submitted (alpha or search form)
            if (JFactory::getUser()->get('id') || $this->input->get('kb_search') || $this->input->get('alpha_search'))
            {
                $cachable = false;
            }

            $safeurlparams = array('cat'=>'INT','id'=>'INT','cid'=>'ARRAY','limit'=>'INT','limitstart'=>'INT',
                'showall'=>'INT','return'=>'BASE64','search'=>'STRING','filter_order'=>'CMD','filter_order_dir'=>'CMD',
                'print'=>'BOOLEAN','lang'=>'CMD');

            if ($vName !== 'feed') echo '<!-- KnowledgeBase v'.knowledgebaseAdmin::_getversion().' by The Thinkery LLC. http://thethinkery.net -->';
            parent::display($cachable, $safeurlparams);

            return $this;
        }
    }
}