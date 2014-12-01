<?php
/*======================================================================*\
|| #################################################################### ||
|| # Package - YJ YJCompression                							||
|| # Copyright (C) since 2007  Youjoomla LLC. All Rights Reserved.      ||
|| # license - PHP files are licensed under  GNU/GPL V2                 ||
|| # license - CSS  - JS - IMAGE files  are Copyrighted material        ||
|| # bound by Proprietary License of Youjoomla LLC                      ||
|| # for more information visit http://www.youjoomla.com/license.html   ||
|| # Redistribution and  modification of this software                  ||
|| # is bounded by its licenses                                         || 
|| # websites - http://www.youjoomla.com | http://www.yjsimplegrid.com  ||
|| #################################################################### || 
\*======================================================================*/

// no direct access 
if((int)JVERSION == 3){
	defined('JPATH_PLATFORM') or die; 
}else{ 
	defined('_JEXEC') or die ('Restricted access');
}

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class plgSystemJCompressInstallerScript{
 
    public function postflight($type, $parent){


        
        $db = JFactory::getDbo();
        
        try {
            
            $q = $db->getQuery(true);
            
            $q->update('#__extensions');
            $q->set(array(
                'ordering = 100000'
            ));
            $q->where("element = 'jcompress'");
            $q->where("type = 'plugin'", 'AND');
            $q->where("folder = 'system'", 'AND');
            
            $db->setQuery($q);
            
            method_exists($db, 'execute') ? $db->execute() : $db->query();
        }
        catch (Exception $e) {
            throw $e;
        }


    }
}