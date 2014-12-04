<?php
/*======================================================================*\
|| #################################################################### ||
|| # Package - Joomla Template element based on YJSimpleGrid Framework  ||
|| # Copyright (C) 2010  Youjoomla LLC. All Rights Reserved.            ||
|| # license - PHP files are licensed under  GNU/GPL V2                 ||
|| # license - CSS  - JS - IMAGE files  are Copyrighted material        ||
|| # bound by Proprietary License of Youjoomla LLC                      ||
|| # for more information visit http://www.youjoomla.com/license.html   ||
|| # Redistribution and  modification of this software                  ||
|| # is bounded by its licenses                                         ||
|| # websites - http://www.youjoomla.com | http://www.yjsimplegrid.com  ||
|| #################################################################### ||
\*======================================================================*/
define('_JEXEC', 1);
header("Content-Type: application/json");
$jpath = dirname(dirname(dirname(dirname(dirname(__FILE__)))));
define('JPATH_BASE', $jpath);
define('DS', DIRECTORY_SEPARATOR);
require_once(JPATH_BASE . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'defines.php');
require_once(JPATH_BASE . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'framework.php');

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');
jimport('joomla.plugin.helper');
jimport('joomla.application.component.helper');

if (isset($_POST['task'])) {
	
	
	$published = JPluginHelper::isEnabled('system', 'jcompress');
	if(!$published) return;
	
	require('yjsgjson.php');
	
	// get few params
	$post = JRequest::get('post');
	$task = $post['task'];
	
	//load the language files
	$language = JFactory::getLanguage();
	$language->load('plg_system_jcompress', JPATH_ADMINISTRATOR);
	
	
	
	if ($task == 'clearCache' || $task == 'clearCacheCss' || $task == 'clearCacheJs' || $task == 'resetCounters') {
		
		$mainframe = JFactory::getApplication('administrator');
		$mainframe->initialise();
		JPluginHelper::importPlugin('system');
		$site_link = str_replace('plugins/system/' . basename(dirname(dirname(__FILE__))) . '/elements/', '', JURI::root());
		
		
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);
		$query->select("template FROM #__template_styles WHERE client_id =0 AND home = 1");
		$db->setQuery($query);
		$db->query();
		$template = $db->loadResult();
		
		
		// Make sure there aren't any errors
		if ($db->getErrorNum()) {
			$response = array(
				'message' => $db->getErrorMsg()
			);
			$json     = new JSON($response);
			echo $json->result;
			exit;
		}
		
		function getFileCount($path,$special=array()) {
			$size = 0;
			if(!JFolder::exists($path))return $size;
			$ignore = array('.','..','cgi-bin','.DS_Store','.db','index.html','index.htm','menuList.php');
			$files = scandir($path);
			foreach($files as $t) {
				
				$exclude = false;
				if(!empty($special)){
					foreach ($special as $name) {
						if (stripos($t, $name) !== FALSE) {
							$exclude = true;
						}
					}						
				}
				
				if(in_array($t, $ignore) || $exclude) continue;
				if (is_dir(rtrim($path, '/') . '/' . $t)) {
					$size += getFileCount(rtrim($path, '/') . '/' . $t);
				} else {
					$size++;
				}   
			}
			return $size;
		}
		
		$cache_root = JPATH_ROOT . DIRECTORY_SEPARATOR . "cache" . DIRECTORY_SEPARATOR . "jcompress" .  DIRECTORY_SEPARATOR . $template;
		
		$cache_folder = $cache_root;
		
		$cache_for    = '';
	}
	
	if ($task == 'clearCache' || $task == 'clearCacheCss' || $task == 'clearCacheJs') {
		
	
		
		if ($task == 'clearCacheCss') {
			
			$cache_folder = $cache_folder . DIRECTORY_SEPARATOR . 'css';
			$cache_for    = 'CSS ';
		}
		
		if ($task == 'clearCacheJs') {
			
			$cache_folder = $cache_folder . DIRECTORY_SEPARATOR . 'js';
			$cache_for    = 'JS ';
		}
		
		
		if (JFolder::exists($cache_folder)) {
			
			JFolder::delete($cache_folder);
			
			
			
			$filescount_css = getFileCount($cache_root . DIRECTORY_SEPARATOR . 'css',array('_css','_log'));
			$filescount_js  = getFileCount($cache_root . DIRECTORY_SEPARATOR . 'js',array('_js','_log'));
			$filescount_all = $filescount_css + $filescount_js;
			
			$hide_scann = 0;
			
			if($filescount_all == 0){
				
				$hide_scann = 1;
			}
			
			
			$response = array(
				'message' => JText::_($cache_for . JText::_('PLG_JCOMPRESS_CACHE_CL_FOR_TEMPLATE') . $template),
				'filesount' => $filescount_all,
				'filesount_css' => $filescount_css,
				'filesount_js' => $filescount_js,
				'hide_scann' => $hide_scann
				
			);
			
			$json = new JSON($response);
			echo $json->result;
			exit();
		} else {
			$response = array(
				'message' => JText::_($template .' '. $cache_for . JText::_('PLG_JCOMPRESS_CACHE_NO_FOLDER'))
			);
			$json     = new JSON($response);
			echo $json->result;
			exit();
		}

	}
	
	
	
	if ($task == 'resetCounters') {
		
		sleep(10);
		
		$filescount_css = getFileCount($cache_root . DIRECTORY_SEPARATOR . 'css',array('_css','_log'));
		$filescount_js  = getFileCount($cache_root . DIRECTORY_SEPARATOR . 'js',array('_js','_log'));	
		$filescount_all = $filescount_css + $filescount_js;
		
		$response = array(
			'message' => JText::_('PLG_JCOMPRESS_DONE'),
			'filesount' => $filescount_all,
			'filesount_css' => $filescount_css,
			'filesount_js' => $filescount_js,
			'finished' => 1
			
		);
		
		$json = new JSON($response);
		echo $json->result;
		exit();			
	}
	
	if ($task == 'buildMenu') {
		
		$mainframe = JFactory::getApplication('site');
		
		$mainframe->initialise();
		$site_base = str_replace('plugins/system/' . basename(dirname(dirname(__FILE__))) . '/elements/', '', JURI::root());
		
		JPluginHelper::importPlugin('system');
		JPluginHelper::importPlugin('jcompress');
		$dispatcher =JDispatcher::getInstance();
		$menu = $dispatcher->trigger('menuList', array('',$site_base,true,true));

		$json_menu = json_encode($menu[0]);
		
		$response = array(
			'message' => 'Menu list built',
			'menuarray' =>$json_menu
		);
		$json     = new JSON($response);
		echo $json->result;
		exit();

		
	}
		
} else {
	echo 'Restricted acsess';
}