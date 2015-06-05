<?php
/**
 * Knowledge-Base
 * @copyright (C) 2015 the Thinkery LLC. All rights reserved.
 * info@thethinkery.net
 * www.thethinkery.net
 */

defined( '_JEXEC' ) or die( 'Restricted access' );
jimport('joomla.application.component.model');

class KnowledgebaseModelItem extends JModelForm
{
    protected $view_item    = 'item';
    protected $_item        = null;
    protected $_context     = 'com_knowledgebase.item';

    protected function populateState()
    {
        $app = JFactory::getApplication('site');

        // Load state from the request.
        $pk = $app->input->getUint('id');
        $this->setState('item.id', $pk);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
    }

    public function &getItem($pk = null)
    {
        $pk     = (!empty($pk)) ? $pk : (int) $this->getState('employee.id');
        $type   = $this->getState('layout');
        $data   = false;

        if ($this->_item === null) {
            $this->_item = array();
        }

        if (!isset($this->_item[$pk])) {

            try {
                $data = workforceHTML::buildEmployee($this->getState('employee.id'));

                $data->tags = new JHelperTags;
                $data->tags->getItemTags('com_workforce.employee', $data->id);

                $this->_item[$pk] = $data;
            }
            catch (Exception $e)
            {
                if ($e->getCode() == 404) {
                    // Need to go thru the error handler to allow Redirect to work.
                    JError::raiseError(404, $e->getMessage());
                }
                else {
                    $this->setError($e);
                    $this->_item[$pk] = false;
                }
            }
        }

        return $this->_item[$pk];
    }
}