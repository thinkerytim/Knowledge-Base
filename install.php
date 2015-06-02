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

class com_knowledgebaseInstallerScript
{
    private $tmppath;
    private $kbmedia;
    private $installed_mods             = array();
    private $installed_plugs            = array();
    private $release                    = '3.3.2';
    private $minimum_joomla_release     = '3.3';
    private $preflight_message          = null;
    private $install_message            = null;
    private $uninstall_message          = null;
    private $update_message             = null;
    private $db                         = null;
    private $iperror                    = array();

    /*
     * Preflight method-- return false to abort install
     */
    function preflight($action, $parent)
    {
        $jversion = new JVersion();

        // get new version of IP from manifest and define class variables
        $this->release  = $parent->get("manifest")->version;
        $this->tmppath  = JPATH_ROOT.'/media/iptmp';
        $this->kbmedia  = JPATH_ROOT.'/media/com_knowledgebase';
        $this->db       = JFactory::getDBO();

        // Find mimimum required joomla version
        $this->minimum_joomla_release = $parent->get("manifest")->attributes()->version;

        if( version_compare( $jversion->getShortVersion(), $this->minimum_joomla_release, 'lt' ) ) {
            JError::raiseWarning('', 'Cannot install KnowledgeBase '.$this->release.' in a Joomla release prior to '.$this->minimum_joomla_release);
            return false;
        }

        // abort if the component being installed is not newer than the currently installed version
        switch ($action){
            case 'update':
                $oldRelease = $this->getParam('version');
                $rel = $oldRelease . ' to ' . $this->release;
                if ( version_compare( $this->release, $oldRelease, 'lt' ) ) {
                    JError::raiseWarning( '', 'Incorrect version sequence. Cannot upgrade KnowledgeBase ' . $rel );
                    return false;
                }
                $this->installModsPlugs($parent);
                break;
            case 'install':
                $this->installModsPlugs($parent);
                $rel = $this->release;
                break;
        }

        // check for required libraries
        $gd_exists          = (extension_loaded('gd') && function_exists('gd_info')) ? '<span class="label label-success">Enabled</span>' : '<span class="text-error">Disabled</span>';

        // Set preflight message
        $this->preflight_message .=  '
            <h3>Preflight Status: ' . $action . ' - ' . $rel . '</h3>
            <ul>
                <li>Current KB version: <span class="label label-success">'.$this->release.'</span></li>
                <li>GD Support: '.$gd_exists.'</li>
            </ul>';
    }

    function install($parent)
    {
        // Define vars
        $sample_data_file       = JPATH_ADMINISTRATOR.'/components/com_knowledgebase/assets/install.sampledata.sql';
        $sample_data_rslt       = '<span class="label label-success">Sample data installed</span>';

        // Check if sample data file exists and execute query
        if(JFile::exists($sample_data_file)){
            if(!$this->populateDatabase($this->db, $sample_data_file)){
                $sample_data_rslt   = '<span class="text-error">Sample data not installed</span>';
            }
        }else{ // Could not find sample data file
            $sample_data_rslt   = '<span class="text-error">Sample data not installed</span>';
            $this->iperror[]    = 'Could not find sample data file - '.$sample_data_file;
        }

        // Set installation message
        $this->install_message .= '
            <h3>Installation Status:</h3>
            <p>Congratulations on your install of KnowledgeBase by the Thinkery LLC! The first thing to do to get started
            is to go into the settings area and configure your component. When you have your configuration done,
            start by adding a KB category, then start populating KB items! Please post issues to the support forums at
            extensions.thethinkery.net</p>

            <ul>
                <li>Sample data execution: '.$sample_data_rslt.'</li>
            </ul>

            <h3>Media Status:</h3>
            <ul>';
        //create media folders
        $folder_array       = array('', 'categories', 'pictures');
        $default_files      = JFolder::files($this->tmppath);
        foreach($folder_array as $folder){
            if(!JFolder::exists($this->kbmedia.'/'.$folder)){
                if(!JFolder::create($this->kbmedia.'/'.$folder, 0755) ) {
                    $this->iperror[] = 'Could not create the <em>'.$this->kbmedia.'/'.$folder.'</em> folder. Please check your media folder permissions';
                    $this->install_message .= '<li>media/com_knowledgebase/'.$folder.': <span class="text-error">Not created</span></li>';
                }else{
                    $folderpath = $this->kbmedia.'/'.$folder;
                    // copy a nopic.png and index.html into each created directory
                    JFile::copy($this->tmppath.'/nopic.png', $folderpath.'/nopic.png');
                    JFile::copy($this->tmppath.'/index.html', $folderpath.'/index.html');
                    $this->install_message .= '<li>media/com_knowledgebase/'.$folder.': <span class="label label-success">Created</span></li>';
                }
            }else{
                $this->install_message .= '<li>media/com_knowledgebase/'.$folder.': <span class="label label-success">Exists from previous install</span></li>';
            }
        }

        $this->install_message .= '
            </ul>';
    }

    /**
     * method to update the component
     *
     * @return void
     */
    function update($parent)
    {
        // Set update message
        $this->update_message .=  '
            <h3>Update Status</h3>
            <p>Congratulations on your update of KnowledgeBase! Please take a look at the changelog to the right
            to see what\'s new! Please post issues to the support forums at extensions.thethinkery.net</p>';
    }

    function uninstall($parent)
    {
        $this->db       = JFactory::getDBO();
        $drop_results   = array();
        $ip_uninstall_error = 0;

        $drop_array = array('ipcategories'=>'iproperty_categories',
            'ipproperties'=>'iproperty',
            'ipimages'=>'iproperty_images',
            'ipcompanies'=>'iproperty_companies',
            'ipagents'=>'iproperty_agents',
            'ipamenities'=>'iproperty_amenities',
            'ipcountries'=>'iproperty_countries',
            'ipstates'=>'iproperty_states',
            'ipopenhouses'=>'iproperty_openhouses',
            'ipsettings'=>'iproperty_settings',
            'ipsaved'=>'iproperty_saved',
            'ipcurrency'=>'iproperty_currency',
            'ipagentmid'=>'iproperty_agentmid',
            'ippropmid'=>'iproperty_propmid',
            'ipstypes'=>'iproperty_stypes');

        foreach($drop_array AS $key => $value)
        {
            $this->db->setQuery("DROP TABLE IF EXISTS #__".$value);
            if($this->db->execute()){
                $drop_results[$key] = '<span class="label label-success">Removed Successfully</span>';
            }else{
                $drop_results[$key] = '<span class="text-error">Not Removed</span>';
                $ip_uninstall_error++;
            }
        }

        echo '
        <div class="row-fluid">
            <div class="span5">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Table</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tfoot>
                        <tr>
                            <td colspan="2">&nbsp;</td>
                        </tr>
                    </tfoot>
                    <tbody>
                        <tr><td class="key">Categories Table</td><td style="text-align: center !important;">'.$drop_results['ipcategories'].'</td></tr>
                        <tr><td class="key">Properties Table</td><td style="text-align: center !important;">'.$drop_results['ipproperties'].'</td></tr>
                        <tr><td class="key">Images Table</td><td style="text-align: center !important;">'.$drop_results['ipimages'].'</td></tr>
                        <tr><td class="key">Companies Table</td><td style="text-align: center !important;">'.$drop_results['ipcompanies'].'</td></tr>
                        <tr><td class="key">Agents Table</td><td style="text-align: center !important;">'.$drop_results['ipagents'].'</td></tr>
                        <tr><td class="key">Amenities Table</td><td style="text-align: center !important;">'.$drop_results['ipamenities'].'</td></tr>
                        <tr><td class="key">Countries Table</td><td style="text-align: center !important;">'.$drop_results['ipcountries'].'</td></tr>
                        <tr><td class="key">States Table</td><td style="text-align: center !important;">'.$drop_results['ipstates'].'</td></tr>
                        <tr><td class="key">Open Houses Table</td><td style="text-align: center !important;">'.$drop_results['ipopenhouses'].'</td></tr>
                        <tr><td class="key">Settings Table</td><td style="text-align: center !important;">'.$drop_results['ipsettings'].'</td></tr>
                        <tr><td class="key">Saved Properties Table</td><td style="text-align: center !important;">'.$drop_results['ipsaved'].'</td></tr>
                        <tr><td class="key">Currencies Table</td><td style="text-align: center !important;">'.$drop_results['ipcurrency'].'</td></tr>
                        <tr><td class="key">Agents Mid Table</td><td style="text-align: center !important;">'.$drop_results['ipagentmid'].'</td></tr>
                        <tr><td class="key">Properties Mid Table</td><td style="text-align: center !important;">'.$drop_results['ippropmid'].'</td></tr>
                        <tr><td class="key">Sale Types Table</td><td style="text-align: center !important;">'.$drop_results['ipstypes'].'</td></tr>
                    </tbody>
                </table>
            </div>
            <div class="span5">
                <table class="table table-striped">
                    <tr><td valign="top"><h3>Thank you for using IProperty!</h3></td></tr>
                    <tr>
                        <td valign="top">
                            <p>Thank you for using KnowledgeBase. If you have any new feature requests we would love to hear
                            them! Please post requests in the forums at <a href="http://extensions.thethinkery.net" target="_blank">http://extensions.thethinkery.net</a>. Ideas for
                            new component features, modules, and plugins are welcome. If you have questions please post to the support forum or email
                            us at <a href="mailto:iproperty@thethinkery.net">iproperty@thethinkery.net</a>.</p>

                            <h4>Upgrade Instructions:</h4>
                            <p>If you are upgrading to a newer version of KnowledgeBase, please visit <a href="http://extensions.thethinkery.net" target="_blank">http://extensions.thethinkery.net</a>
                            to review upgrade instructions. All media folders and files have been preserved for use in future upgrades and can be located in your site/media/com_knowledgebase folder.</p>
                        </td>
                    </tr>
                </table>
            </div>
        </div>';
    }

    /**
     * method to run after an install/update/uninstall method
     *
     * @return void
     */
    function postflight($action, $parent)
    {
        echo '
        <style type="text/css">
            .iplogoheader{border-bottom: solid 1px #ccc; margin-bottom: 8px;}
            .ipleftcol{color: #808080; padding: 0px 10px;}
            .iplogfile{background: #ffffff !important; border: solid 1px #cccccc; padding: 5px 10px; height: 500px; overflow: auto;}
        </style>

        <div class="row-fluid iplogoheader">
            '.JHTML::_('image', 'administrator/components/com_knowledgebase/assets/images/iproperty_admin_logo.gif', 'KnowledgeBase :: By The Thinkery' ).'
        </div>
        <div class="row-fluid">
            <div class="span5">
                '.$this->preflight_message;

        switch ($action){
            case "install":
                echo $this->install_message;
                $this->addContentTypes();
                break;
            case "update":
                echo $this->update_message;
                break;
            case "uninstall":
                echo $this->uninstall_message;
                break;
        }

        if(count($this->iperror))
        {
            JError::raiseWarning( 123, 'Component was installed but some errors occurred. Please check install status below for details' );
            echo '
                        <h3>Error Status</h3>
                        <ul>';
            foreach($this->iperror as $error){
                echo '<li><span class="text-error">'.$error.'</span></li>';
            }
            echo '
                        </ul>';
        }
        echo '
            </div>
            <div class="span7">
                <ul class="nav nav-tabs">
                    <li class="active"><a href="#kbchangelog" data-toggle="tab">'.JText::_('Change Log').'</a></li>';

        if (count($this->installed_plugs))
        {
            echo '<li><a href="#kbplugins" data-toggle="tab">'.JText::_('Plugins').'</a></li>';
        }
        if (count($this->installed_mods))
        {
            echo '<li><a href="#kbmodules" data-toggle="tab">'.JText::_('Modules').'</a></li>';
        }
        echo '
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="kbchangelog">';
        $logfile            = JPATH_ADMINISTRATOR.'/components/com_knowledgebase/assets/CHANGELOG.TXT';
        if(JFile::exists($logfile))
        {
            $logcontent     = JFile::read($logfile);
            $logcontent     = htmlspecialchars($logcontent, ENT_COMPAT, 'UTF-8');
            echo '<pre style="font-size: 11px !important; color: #666; height: 600px; overflow: auto;">'.$logcontent.'</pre>';
        }else{
            echo 'Could not find changelog content - '.$logfile;
        }
        echo '
                    </div>';

        if (count($this->installed_plugs))
        {
            echo '
                            <div class="tab-pane" id="kbplugins">
                                <div>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>'.JText::_('Plugin').'</th>
                                                <th>'.JText::_('Group').'</th>
                                                <th>'.JText::_('Status').'</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <td colspan="3">&nbsp;</td>
                                            </tr>
                                        </tfoot>
                                        <tbody>';
            foreach ($this->installed_plugs as $plugin) :
                $pstatus    = ($plugin['upgrade']) ? '<button class="btn btn-mini btn-success disabled"><i class="icon-thumbs-up"></i></button>' : '<button class="btn btn-mini btn-danger disabled"><i class="icon-thumbs-down"></i></button>';
                echo '<tr>
                                                    <td>'.$plugin['plugin'].'</td>
                                                    <td>'.$plugin['group'].'</td>
                                                    <td style="text-align: center;">'.$pstatus.'</td>
                                                  </tr>';
            endforeach;
            echo '
                                        </tbody>
                                    </table>
                                </div>
                           </div>';
        }

        if (count($this->installed_mods))
        {
            echo '
                            <div class="tab-pane" id="kbmodules">
                                <div>
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>'.JText::_('Module').'</th>
                                                <th>'.JText::_('Status').'</th>
                                            </tr>
                                        </thead>
                                        <tfoot>
                                            <tr>
                                                <td colspan="2">&nbsp;</td>
                                            </tr>
                                        </tfoot>
                                        <tbody>';
            foreach ($this->installed_mods as $module) :
                $mstatus    = ($module['upgrade']) ? '<button class="btn btn-mini btn-success disabled"><i class="icon-thumbs-up"></i></button>' : '<button class="btn btn-mini btn-danger disabled"><i class="icon-thumbs-down"></i></button>';
                echo '<tr>
                                                    <td>'.$module['module'].'</td>
                                                    <td style="text-align: center;">'.$mstatus.'</td>
                                                  </tr>';
            endforeach;
            echo '
                                        </tbody>
                                    </table>
                                </div>
                           </div>';
        }
        echo '
            </div>
        </div>';
    }

    function getParam( $name )
    {
        $this->db = JFactory::getDbo();
        $this->db->setQuery('SELECT manifest_cache FROM #__extensions WHERE name = "com_knowledgebase" AND type="component"');
        $manifest = json_decode( $this->db->loadResult(), true );
        return $manifest[$name];
    }

    function installModsPlugs($parent)
    {
        $manifest       = $parent->get("manifest");
        $parent         = $parent->getParent();
        $source         = $parent->getPath("source");

        // install plugins and modules
        $installer = new JInstaller();

        // Install plugins
        foreach($manifest->plugins->plugin as $plugin)
        {
            $attributes                 = $plugin->attributes();
            $plg                        = $source .'/'. $attributes['folder'].'/'.$attributes['plugin'];
            $new                        = ($attributes['new']) ? '&nbsp;(<span class="label label-success">New in v.'.$attributes['new'].'!</span>)' : '';
            if($installer->install($plg))
            {
                $this->installed_plugs[]    = array('plugin' => $attributes['plugin'].$new, 'group'=> $attributes['group'], 'upgrade' => true);
            }else{
                $this->installed_plugs[]    = array('plugin' => $attributes['plugin'], 'group'=> $attributes['group'], 'upgrade' => false);
                $this->iperror[] = JText::_('Error installing plugin').': '.$attributes['plugin'];
            }
        }

        // Install modules
        foreach($manifest->modules->module as $module)
        {
            $attributes             = $module->attributes();
            $mod                    = $source .'/'. $attributes['folder'].'/'.$attributes['module'];
            $new                    = ($attributes['new']) ? '&nbsp;(<span class="label label-success">New in v.'.$attributes['new'].'!</span>)' : '';
            if($installer->install($mod)){
                $this->installed_mods[] = array('module' => $attributes['module'].$new, 'upgrade' => true);
            }else{
                $this->installed_mods[] = array('module' => $attributes['module'], 'upgrade' => false);
                $this->iperror[] = JText::_('Error installing module').': '.$attributes['module'];
            }
        }
    }

    public function populateDatabase($db, $schema)
    {
        $return = true;

        // Get the contents of the schema file.
        if (!($buffer = file_get_contents($schema)))
        {
            $this->iperror[] = $db->getErrorMsg();
            return false;
        }

        // Get an array of queries from the schema and process them.
        $queries = $this->_splitQueries($buffer);
        foreach ($queries as $query)
        {
            // Trim any whitespace.
            $query = trim($query);

            // If the query isn't empty and is not a MySQL or PostgreSQL comment, execute it.
            if (!empty($query) && ($query{0} != '#') && ($query{0} != '-'))
            {
                // Execute the query.
                $db->setQuery($query);

                try
                {
                    $db->execute();
                }
                catch (RuntimeException $e)
                {
                    $this->iperror[] = $e->getMessage();
                    $return = false;
                }
            }
        }

        return $return;
    }

    protected function _splitQueries($sql)
    {
        $buffer    = array();
        $queries   = array();
        $in_string = false;

        // Trim any whitespace.
        $sql = trim($sql);

        // Remove comment lines.
        $sql = preg_replace("/\n\#[^\n]*/", '', "\n" . $sql);

        // Remove PostgreSQL comment lines.
        $sql = preg_replace("/\n\--[^\n]*/", '', "\n" . $sql);

        // find function
        $funct = explode('CREATE OR REPLACE FUNCTION', $sql);
        // save sql before function and parse it
        $sql = $funct[0];

        // Parse the schema file to break up queries.
        for ($i = 0; $i < strlen($sql) - 1; $i++)
        {
            if ($sql[$i] == ";" && !$in_string)
            {
                $queries[] = substr($sql, 0, $i);
                $sql = substr($sql, $i + 1);
                $i = 0;
            }

            if ($in_string && ($sql[$i] == $in_string) && $buffer[1] != "\\")
            {
                $in_string = false;
            }
            elseif (!$in_string && ($sql[$i] == '"' || $sql[$i] == "'") && (!isset ($buffer[0]) || $buffer[0] != "\\"))
            {
                $in_string = $sql[$i];
            }
            if (isset ($buffer[1]))
            {
                $buffer[0] = $buffer[1];
            }
            $buffer[1] = $sql[$i];
        }

        // If the is anything left over, add it to the queries.
        if (!empty($sql))
        {
            $queries[] = $sql;
        }

        // add function part as is
        for ($f = 1; $f < count($funct); $f++)
        {
            $queries[] = 'CREATE OR REPLACE FUNCTION ' . $funct[$f];
        }

        return $queries;
    }

    // add tag items 
    function addContentTypes(){
        // get the JTable instance
        $table = JTable::getInstance('Contenttype', 'JTable');
        $ip_ctypes = array();
        // create any content types we want to use
        $ip_ctypes[] = array(
            'type_id' => 0,
            'type_title' => 'Property',
            'type_alias' => 'com_knowledgebase.property',
            'table' => '#__iproperty',
            'rules' => '',
            'router' => 'IpropertyHelperRoute::getPropertyRoute',
            'field_mappings' => '{"common":[{"core_content_item_id":"id","core_title":"title","core_state":"published","core_alias":"alias","core_created_time":"created","core_modified_time":"modified","core_body":"description", "core_hits":"hits","core_publish_up":"publish_up","core_publish_down":"publish_down","core_access":"access", "core_params":"null", "core_featured":"featured", "core_metadata":"null", "core_language":"null", "core_images":"null", "core_urls":"null", "core_version":"null", "core_ordering":"ordering", "core_metakey":"metakey", "core_metadesc":"metadesc", "core_catid":"null", "core_xreference":"null", "asset_id":"null"}], "special": [{"parent_id":"null","lft":"null","rgt":"null","level":"null","path":"null","extension":"null","note":"null"}]}'
        );
        
        // store the content types
        foreach ($ip_ctypes as $ctype){
            $table->save($ctype);
        }
    }
}
