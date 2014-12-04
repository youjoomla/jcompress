<?php
/**
 * @package      JCompress
 * @copyright    Copyright(C) since 2007  Youjoomla.com. All Rights Reserved.
 * @author       YouJoomla
 * @license      http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * @websites     http://www.youjoomla.com | http://www.yjsimplegrid.com
 */
// Check to ensure this file is within the rest of the framework
defined('JPATH_BASE') or die();

// Import classes
jimport('joomla.html.html');
jimport('joomla.access.access');
jimport('joomla.form.formfield');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

/**
 * Form Field-class for selecting a component
 */
class JFormFieldYjsgcomponents extends JFormField
{
    /*
     * Form field type
     */
    public $type = 'Yjsgcomponents';


    protected function getInput()
    {
		
		
		$path = JPATH_ROOT . '/components';
		$folders = JFolder::folders($path,'.',false,false, array('com_ajax'));

		
        $name = $this->name.'[]';
        $value = $this->value;


        $options = array();
        foreach ($folders as $folder) {
            $options[] = JHTML::_('select.option', $folder, $folder, 'value', 'text');
        }

        $attribs = 'class="inputbox" multiple="multiple" size="14"';
        return JHTML::_('select.genericlist',  $options, $name, $attribs, 'value', 'text', $value, $name);
    }
}
