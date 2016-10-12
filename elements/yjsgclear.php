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
/**
 * Renders a spacer element
 *
 * @package 	Joomla.Framework
 * @subpackage		Parameter
 * @since		1.5
 */
 
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.plugin.helper');
jimport('joomla.application.component.helper');

class JFormFieldYjsgclear extends JFormField {
    /**
     * Element name
     *
     * @access	protected
     * @var		string
     */
    
    var $type = 'Yjsgclear';
    
    
    public function getFilesCount($path, $special = array()) {
        
        $size = 0;
        
		if (!JFolder::exists($path)) return $size;
		
        $ignore = array(
            '.',
            '..',
            'cgi-bin',
            '.DS_Store',
            '.db',
            'index.html',
            'index.htm',
            'menuList.php'
        );
        $files  = scandir($path);
        foreach ($files as $t) {
            
            $exclude = false;
            if (!empty($special)) {
                foreach ($special as $name) {
                    if (stripos($t, $name) !== FALSE) {
                        $exclude = true;
                    }
                }
            }
            
            if (in_array($t, $ignore) || $exclude)
                continue;
            if (is_dir(rtrim($path, '/') . '/' . $t)) {
                $size += $this->getFilesCount(rtrim($path, '/') . '/' . $t);
            } else {
                $size++;
            }
        }
        return $size;
    }
    
    
    public function getInput() {
        
        
        $published = JPluginHelper::isEnabled('system', 'jcompress');
        
        if (!$published) return;
        
        $assets_path = str_replace(JPATH_ROOT, rtrim(JURI::root( true ), "/"), dirname(__FILE__));
        $assets_path = str_replace('\\', '/', $assets_path);
        $document    = JFactory::getDocument();
        $document->addStyleSheet($assets_path . '/css/stylesheet.css');
        
        if (intval(JVERSION) < 3) {
            $document->addScript('//ajax.googleapis.com/ajax/libs/jquery/1.11.1/jquery.min.js');
        }
		
        $document->addScript($assets_path . '/src/jcompress.js');
        
        $db    = JFactory::getDbo();
        $query = $db->getQuery(true);
        $query->select("template FROM #__template_styles WHERE client_id =0 AND home = 1");
        $db->setQuery($query);
        $db->query();
        $template = $db->loadResult();
        
        $cache_folder = JPATH_ROOT . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "jcompress" . DIRECTORY_SEPARATOR . $template;
        
        $filescount_css = $this->getFilesCount($cache_folder . DIRECTORY_SEPARATOR . 'css', array(
            '_css',
            '_log'
        ));
        $filescount_js  = $this->getFilesCount($cache_folder . DIRECTORY_SEPARATOR . 'js', array(
            '_js',
            '_log'
        ));
        $filescount_all = $filescount_css + $filescount_js;
        
        $output = '<div class="clearcache">';
        $output .= '<a id="clearall" class="clearcache_button" href="#"> ' . JText::_('PLG_JCOMPRESS_CLEAR') . ' ' . JText::_('PLG_JCOMPRESS_CACHE') . ' <span class="filescount">' . $filescount_all . '</span></a>';
        $output .= '<a id="clearcss" class="clearcache_button" href="#"> ' . JText::_('PLG_JCOMPRESS_CLEAR') . ' CSS ' . JText::_('PLG_JCOMPRESS_CACHE') . ' <span class="filesount_css">' . $filescount_css . '</span></a>';
        $output .= '<a id="clearjs" class="clearcache_button" href="#"> ' . JText::_('PLG_JCOMPRESS_CLEAR') . ' JS ' . JText::_('PLG_JCOMPRESS_CACHE') . ' <span class="filesount_js">' . $filescount_js . '</span></a>';
        $output .= '<a id="scanpages" class="clearcache_button" href="#"> ' . JText::_('PLG_JCOMPRESS_SCAN_PAGES') . ' </a>';
        $output .= '<input type="hidden" name="compress_ajax_path" id="compress_ajax_path" value="' . $assets_path . '" />';
        $output .= '<div class="ajaxresponse" data-scannning="' . JText::_('PLG_JCOMPRESS_SCANNING') . '" data-resetting="' . JText::_('PLG_JCOMPRESS_RESETTING') . '"></div>';
        $output .= '</div>';
        
        return $output;
    }
    public function getLabel() {
        return false;
    }
}