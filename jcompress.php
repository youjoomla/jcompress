<?php
/**
 * @package      JCompress
 * @copyright    Copyright(C) since 2007  Youjoomla.com. All Rights Reserved.
 * @author       YouJoomla
 * @license      http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * @websites     http://www.youjoomla.com | http://www.yjsimplegrid.com
 */
// no direct access 
defined('_JEXEC') or die('Restricted access');

jimport('joomla.plugin.plugin');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

class plgSystemJCompress extends JPlugin{

		
	public $app;
	public $doc;
	public $input;
	public $option;
	public $task;
	public $view;
	public $layout;
	public $cssfiles;
	public $jsfiles;
	public $inlinecss;
	public $inlinejs;
	public $css_file;
	public $js_file;
	public $cached_css;
	public $cached_js;
	public $css_is_cached = false;
	public $js_is_cached = false;
	public $cache_path;
	public $cache_url;
	public $new_css_file;
	public $new_js_file;
	public $menu_list;
	public $externalApp = false;
	public $css_log_content = array();
	public $js_log_content = array();
	public $headData = array();

	public function __construct(& $subject, $config){
			
			
			parent::__construct($subject, $config);
			$this->loadLanguage();
			$this->app     = JFactory::getApplication(); 
			JLoader::register('JcompressUtil', dirname(__FILE__) . '/lib/JcompressUtil.php', true);
			$this->util = new JcompressUtil;
	}
	
	
	
	protected function pluginOptions(){
		
		// params
		$this->caching_on 			= $this->params->get('caching_on', 0);
		$this->cache_css			= $this->params->get('cache_css', 1);
		$this->cache_js				= $this->params->get('cache_js', 1);
		
		$this->compress_css 		= $this->params->get('compress_css', 1);
		$this->compress_js  		= $this->params->get('compress_js', 1);
		$this->compress_html		= $this->params->get('compress_html', 0);

		$this->gzip_css  			= $this->params->get('gzip_css', 1);
		$this->gzip_js  			= $this->params->get('gzip_js',1);
		
		$this->compress_inline_css 	= $this->params->get('compress_inline_css', 1);
		$this->compress_inline_js 	= $this->params->get('compress_inline_js', 1);
		
		$this->cache_expire  		= $this->params->get('cache_expire',24);
		$this->cache_expire			= strtotime('+'.$this->cache_expire.' hours');
		
		$this->place_excluded_css	= $this->params->get('place_excluded_css', 'before');
		$this->place_excluded_js	= $this->params->get('place_excluded_js', 'after');
		
		
		$this->excluded_menuitems	= $this->params->get('excluded_menuitems', array());
		$this->excluded_components	= $this->params->get('excluded_components', array());
		
		$this->place_external_css	= $this->params->get('place_external_css', 'before');
		$this->place_external_js	= $this->params->get('place_external_js', 'after');
		
		$this->exclude_css			= $this->params->get('exclude_css',"");
		if(!empty($this->exclude_css)){
			$this->exclude_css 		= explode(PHP_EOL,$this->params->get('exclude_css',""));
		}
		
		$this->exclude_js 			= $this->params->get('exclude_js',"");
		if(!empty($this->exclude_js)){
			$this->exclude_js 		= explode(PHP_EOL,$this->params->get('exclude_js',""));
		}
		
					
		$this->in_background		= $this->params->get('in_background', 1);
		$this->enable_versions		= $this->params->get('enable_versions', 1);
		
		$this->datauri_images		= $this->params->get('datauri_images', 0);
		$this->datauri_fonts		= $this->params->get('datauri_fonts', 0);
		$this->datauri_size			= $this->params->get('datauri_size', 25000);
		
		$this->js_tobottom			= $this->params->get('js_tobottom', 0);
		$this->ex_jsb_menuitems		= $this->params->get('ex_jsb_menuitems', array());
		$this->ex_jsb_components	= $this->params->get('ex_jsb_components', array());

		
	}
	
	
	public function onAfterRoute() {
		
		
		 $this->pluginOptions();
		 
		 if ($this->app->isAdmin() || $this->caching_on == 0) return;
	
		 $this->input 			= $this->app->input;
		 $this->option 			= $this->input->get('option');
		 $this->task 			= $this->input->get('task');
		 $this->view 			= $this->input->get('view');
		 $this->layout 			= $this->input->get('layout');
		 $this->itemid 			= $this->input->get('Itemid');
		 $this->doc 			= JFactory::getDocument();	
	 
	}
	
	public function onAfterRender(){


		if($this->app->isSite() && $this->caching_on == 1){
			
			// exclude js_tobottom from menu items
			if(!empty($this->ex_jsb_menuitems) && in_array($this->itemid ,$this->ex_jsb_menuitems)){
				
				$this->js_tobottom	= 0;
			}
			// exclude js_tobottom from components
			if(!empty($this->ex_jsb_components) && in_array($this->option,$this->ex_jsb_components)){
				
				$this->js_tobottom = 0;
				
			}
			
			// exclude js_tobottom from external apps
			if (defined('YJEXTERNAL')) {
				
				$this->js_tobottom = 0;
				
			}
				
			// exclude from menu items
			if(!empty($this->excluded_menuitems) && in_array($this->itemid ,$this->excluded_menuitems)){
				$this->caching_on = 0;
			}
			
			// exclude from components
			if(!empty($this->excluded_components) && in_array($this->option,$this->excluded_components)){
				$this->caching_on = 0;
			}
			
			// exclude from edit pages
			if(
					$this->input->get('layout') == 'itemform' || 
					$this->input->get('layout') == 'edit' || 
					$this->input->get('task') == 'add' ||
					$this->input->get('task') == 'edit' ||
					$this->input->get('task') == 'article.edit'
			
			){
				$this->caching_on = 0;
				
			}
		}
		
		if ($this->app->isAdmin() || $this->caching_on == 0) return;
		
		$this->body				= $this->getBody();
		
		$this->headData			= $this->util->getHeadData($this->body);
		
		$this->headTagContent	= $this->headData['headTagContent'];
		
		$head					= $this->headTagContent;
		
		$this->cssfiles 		= $this->headData['styleSheets'];
		$this->inline_css_head	= $this->headData['inline_css_head'];
		
		
		$this->jsfiles 			= $this->headData['scripts'];
		$this->inline_js_head	= $this->headData['inline_js_head'];
		
		
		$this->site_url  		= rtrim(JURI::root(), "/");	
		$this->site_path		= rtrim(JPATH_SITE, "/");
			
		
		$hook_css_name 			= strlen(utf8_decode(implode('',$this->cssfiles)));
		$hook_js_name 			= strlen(utf8_decode(implode('',$this->jsfiles)));

		$this->css_file 		= hash("crc32b", 'css'.$hook_css_name);
		$this->js_file 			= hash("crc32b", 'js'.$hook_js_name ); 	
		

		
		$this->cache_path		= JPATH_ROOT."/cache/jcompress/".$this->doc->template;
		$this->cache_url		= JURI::base(true)."/cache/jcompress/".$this->doc->template;
		$serve_css 				='.css';
		$serve_js 				='.js';
		
		if($this->gzip_css == 1){
			$serve_css ='.php';
		}
		if($this->gzip_js == 1){
			$serve_js ='.php';
		}
		
		$this->cached_css		= $this->cache_path."/css/".$this->css_file.$serve_css;
		$this->cached_css_gz	= $this->cache_path."/css/".$this->css_file.'_css.php';
		$this->cached_css_log	= $this->cache_path."/css/".$this->css_file."_log.php";
		
		$this->cached_js		= $this->cache_path."/js/".$this->js_file.$serve_js;
		$this->cached_js_gz		= $this->cache_path."/js/".$this->js_file.'_js.php';
		$this->cached_js_log	= $this->cache_path."/js/".$this->js_file."_log.php";
		
		$this->menu_list		= JPATH_ROOT."/cache/jcompress/menuList.php";
		
		$this->new_css_file		= $this->cache_url."/css/".$this->css_file.$serve_css;
		$this->new_js_file 		= $this->cache_url."/js/".$this->js_file.$serve_js;

		
		if($this->compress_inline_css == 1){
			
			$head 	= $this->util->processInlineCss($this->inline_css_head,$head);
		}
		
		if($this->compress_inline_js == 1){
			$head = $this->util->processInlineJs($this->inline_js_head,$head);
		}
	
		if(JFile::exists($this->cached_css) && $this->cache_css == 1 && !empty($this->headData['styleSheetsLines'])){

			$this->css_is_cached 	= true;
			$cached_css_file 		= $this->new_css_file.$this->versionFiles($this->cached_css);
			$head 					= $this->replaceFiles($this->headData['styleSheetsLines'],$cached_css_file,$head,'css');
		}
		
		if(JFile::exists($this->cached_js) && $this->cache_js == 1 && !empty($this->headData['scriptsLines'])){
			
			$this->js_is_cached = true;
			$cached_js_file 	= $this->new_js_file.$this->versionFiles($this->cached_js);
			$head 				= $this->replaceFiles($this->headData['scriptsLines'],$cached_js_file,$head,'js');
		}
		

		if($this->cache_css == 1){
			$this->checkLog($this->cached_css_log,'css');
			if(!$this->css_is_cached){
				$this->processCssFiles($this->cssfiles);
			}
		}
		
		if($this->cache_js == 1){
			$this->checkLog($this->cached_js_log,'js');
			if(!$this->js_is_cached){
				$this->processJsFiles($this->jsfiles);
			}
		}
		
		if($this->caching_on == 1){
			
			$body = $this->util->setHead($this->body,$head);
			if($this->compress_html == 1){
				
				$body = $this->util->htmlCleanup($body,true);
			}
			
			if($this->js_tobottom == 1){
				$body = $this->util->jsToBottom($body);
			}
			
			$this->setBody($body);
			
		}
		
		$this->processviaAjax();

		return true;
	}

	// Process CSS files
	private function processCssFiles($cssFiles){

		$this->processBg();

		if($this->externalApp) return;
		
		$css_file_content 	= array();
		
		if(!empty($this->exclude_css)){
			$cssFiles = $this->excludeFiles($cssFiles,$this->exclude_css);
		}
		
		foreach($cssFiles as $cssfile){

			if($this->externalFile($cssfile,$this->site_url)) continue;

			$fileurl 	= $this->fileLink($cssfile);
			$filepath 	= $this->fileLink($cssfile,true);	
			
			$this->css_log_content [$filepath]['filetime']= filemtime($filepath);
		
		
			if( JFile::exists($filepath) ){	
				
				$get_content 			= JFile::read($filepath);
				$css_file_content[]		= $this->cssRelToAbs($fileurl,$get_content);
				
			}
		}

		$cached_css_file_content = implode('',$css_file_content);

		$this->addIndexes();

		if($this->compress_css == 1){
			
			$cached_css_file_content = $this->util->removeComments($cached_css_file_content);
			$cached_css_file_content = $this->util->removeEmptyLines($cached_css_file_content);
			
		}
		
		if(JFile::exists($this->cached_css)) return;

		if($this->gzip_css == 1){
			
			
			$gzip_css_content ='<?php ';
			$gzip_css_content .='header ("content-type: text/css; charset: UTF-8");'."\r\n";
			$gzip_css_content .='header ("cache-control: public");'."\r\n";
			$gzip_css_content .='header("X-Content-Encoded-By: JCompress");'."\r\n";
			$gzip_css_content .='$expire = "Expires: " . gmdate ("D, d M Y H:i:s",'.$this->cache_expire.') . " GMT";'."\r\n";
			$gzip_css_content .='header ($expire);'."\r\n";
			$gzip_css_content .='if (isset($_SERVER[\'HTTP_IF_MODIFIED_SINCE\'])'."\r\n";
			$gzip_css_content .=' && (strtotime($_SERVER[\'HTTP_IF_MODIFIED_SINCE\']) == filemtime(__FILE__))){'."\r\n";
			$gzip_css_content .='header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\', filemtime(__FILE__)).\' GMT\', true, 304);'."\r\n";
			$gzip_css_content .='exit;'."\r\n";
			$gzip_css_content .='}else{'."\r\n";
			$gzip_css_content .='header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\', filemtime(__FILE__)).\' GMT\', true, 200);'."\r\n";
			$gzip_css_content .='}'."\r\n";
			$gzip_css_content .='if ( extension_loaded( \'zlib\' ) AND (strpos($_SERVER[\'HTTP_ACCEPT_ENCODING\'], \'gzip\') !== FALSE) ) {'."\r\n";
			$gzip_css_content .='ob_start("ob_gzhandler");'."\r\n";
			$gzip_css_content .='}else{'."\r\n";
			$gzip_css_content .='ob_start();'."\r\n";
			$gzip_css_content .='}'."\r\n";
			$gzip_css_content .='readfile("'.$this->css_file.'_css.php");';
		
			
			JFile::write($this->cached_css,$gzip_css_content);
			JFile::write($this->cached_css_gz,$cached_css_file_content);
			
		}else{
			
			JFile::write($this->cached_css,$cached_css_file_content);
		}
		
		if(!JFile::exists($this->cached_css)){
			error_log($this->itemid);	
		}	
			
		$add_css_log_content = "<?php defined('JPATH_PLATFORM') or die ('Restricted access');\r\n \$fileslog='".serialize($this->css_log_content)."';";
		JFile::write($this->cached_css_log,$add_css_log_content);
			
	}
	
	// Process JS files	
	private function processJsFiles($jsFiles){
		
		$this->processBg();
		
		if($this->externalApp) return;
		
		$js_file_content = array();
		
		if(!empty($this->exclude_js)){
			$jsFiles = $this->excludeFiles($jsFiles,$this->exclude_js);
		}
		
		foreach($jsFiles as $jsfile){
			
			
			if($this->externalFile($jsfile,$this->site_url))continue;
			
			$fileurl 		= $this->fileLink($jsfile);
			$filepath 		= $this->fileLink($jsfile,true);
			
			if( JFile::exists($filepath) ){
				
				$js_file_content [] = "\r\n/*!$fileurl*/\r\n".JFile::read($filepath)."\r\n";
				$this->js_log_content [$filepath]['filetime']= filemtime($filepath);
				
			}
			
		}
		
		
		
		$cached_js_file_content = implode('',$js_file_content);
		
		if($this->compress_js == 1){
				require_once "lib/Minifier.php";
				$cached_js_file_content = \JShrink\Minifier::minify($cached_js_file_content);					
		}	
		
		if(JFile::exists($this->cached_js)) return;
				
		if($this->gzip_js == 1){
			
			
			$gzip_js_content ='<?php ';
			$gzip_js_content .='header ("content-type: text/javascript; charset: UTF-8");'."\r\n";
			$gzip_js_content .='header ("cache-control: public");'."\r\n";
			$gzip_js_content .='header("X-Content-Encoded-By: JCompress");'."\r\n";
			$gzip_js_content .='$expire = "Expires: " . gmdate ("D, d M Y H:i:s",'.$this->cache_expire.') . " GMT";'."\r\n";
			$gzip_js_content .='header ($expire);'."\r\n";
			$gzip_js_content .='if (isset($_SERVER[\'HTTP_IF_MODIFIED_SINCE\'])'."\r\n";
			$gzip_js_content .=' && (strtotime($_SERVER[\'HTTP_IF_MODIFIED_SINCE\']) == filemtime(__FILE__))){'."\r\n";
			$gzip_js_content .='header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\', filemtime(__FILE__)).\' GMT\', true, 304);'."\r\n";
			$gzip_js_content .='exit;'."\r\n";
			$gzip_js_content .='}else{'."\r\n";
			$gzip_js_content .='header(\'Last-Modified: \'.gmdate(\'D, d M Y H:i:s\', filemtime(__FILE__)).\' GMT\', true, 200);'."\r\n";
			$gzip_js_content .='}'."\r\n";
			$gzip_js_content .='if ( extension_loaded( \'zlib\' ) AND (strpos($_SERVER[\'HTTP_ACCEPT_ENCODING\'], \'gzip\') !== FALSE) ) {'."\r\n";
			$gzip_js_content .='ob_start("ob_gzhandler");'."\r\n";
			$gzip_js_content .='}else{'."\r\n";
			$gzip_js_content .='ob_start();'."\r\n";
			$gzip_js_content .='}'."\r\n";
			$gzip_js_content .='readfile("'.$this->js_file.'_js.php");';
			
			
			JFile::write($this->cached_js,$gzip_js_content);			
			JFile::write($this->cached_js_gz,$cached_js_file_content);
			
		}else{
			
			JFile::write($this->cached_js,$cached_js_file_content);
		}
		
		$add_js_log_content = "<?php defined('JPATH_PLATFORM') or die ('Restricted access');\r\n \$fileslog='".serialize($this->js_log_content)."';";
		JFile::write($this->cached_js_log,$add_js_log_content);	
		
	}

	// Process via Ajax if external app
	private function processviaAjax(){
		
		if(!$this->externalApp) return;
			
		if( !$this->css_is_cached ){
			$body = $this->body;
			
			$ping_url = JURI::current().'?viaAjax=1';
			$ajaxScript = "<script type=\"text/javascript\">";
			$ajaxScript .= " jQuery.ajax({url:\"$ping_url\",success:function(result){";
			$ajaxScript .= " console.log('processed');";
			$ajaxScript .= "}});";
			$ajaxScript .= "</script>";
			$body = str_replace('</head>',$ajaxScript . "\n</head>", $body);
			$this->setBody($body);
		}
		
	}	
	
	// Process all in background
	public function processBg(){
		
		if($this->in_background == 0) return;
		
		if (defined('YJEXTERNAL')) {
			
			$this->externalApp = true;
			
			$viaAjax = $this->input->get('viaAjax');

			if (isset($viaAjax)){
				
				$this->externalApp = false;
			}
			
		} else {
			
			$response = $this->body;
			
			if (headers_sent()) {
				
				echo $response;
				
			}else{
				
				header("Connection: close\r\n");
				header('Content-Length: ' . strlen($response) . "\r\n");
				header("Content-Encoding: none\r\n");
				echo $response;
				while (@ob_end_clean()) ;
				session_write_close();
				ignore_user_abort(true);
				ob_start();
				ob_end_flush(); 
				flush();
				while (@ob_end_clean());
				if (function_exists("set_time_limit") == TRUE && @ini_get("safe_mode") == 0){
					@set_time_limit(0);
				} else {
					error_log('Jcompress: PHP safe_mode is on or the set_time_limit function is disabled.');
				}
			}
			
		}

	}
	

	// Reaplace head files with cached ones
	protected function replaceFiles($files_array,$rep,$content,$type='css'){
		

		$excluded_js = array();
		$excluded_css = array();
		$excluded_css_arr = array();
		$excluded_js_arr = array();	
		$before ='';
		$after ='';
		
		if($type == 'css'){
			
			if(!empty($this->exclude_css)){
				$files_array 		= $this->excludeFiles($files_array,$this->exclude_css,true);
				$excluded_css_arr 	= $this->exclude_css;
			}
			
			$replacemant = '<link rel="stylesheet" href="'.$rep.'" type="text/css" />';
			$clean_reg	 = '/href=["\']?([^"\'>]+)["\']?/';
		}
		
		
		
		if($type == 'js'){
			
			if(!empty($this->exclude_js)){
				$files_array = $this->excludeFiles($files_array,$this->exclude_js,true);
				$excluded_js_arr 	= $this->exclude_js;
			}
			$replacemant = '<script type="text/javascript" src="'.$rep.'"></script>';
			$clean_reg	 = '/src=["\']?([^"\'>]+)["\']?/';
		}
	
		$external_css = array();
		$external_js = array();
		
		
		foreach  ($files_array as $key => $lines){
			
			$clean_file = preg_match($clean_reg, $lines, $cleanurl);

			if ($clean_file != FALSE) {
				if($this->externalFile($cleanurl[1],$this->site_url)){
					if($type == 'css'){
						$external_css [$key] = $lines;
					}else{
						$external_js [$key] = $lines;
					}
				}
			}
			
			if ($this->util->arrayContains($excluded_js_arr, $lines)) {
    			$excluded_js [$key] = $lines;
			}
			if ($this->util->arrayContains($excluded_css_arr, $lines)) {
    			$excluded_css [$key] = $lines;
			}

			if ($lines === end($files_array)){
				
				$content = str_replace($lines,$replacemant,$content);
			}
			
			$content = str_replace($lines,'',$content);
			
		}
		
		
		if($type == 'css' && !empty($excluded_css)){
			if($this->place_excluded_css == 'after') $after .= implode('',$excluded_css);
			if($this->place_excluded_css == 'before') $before .= implode('',$excluded_css);
		}
		
		if($type == 'js' && !empty($excluded_js)){
			if($this->place_excluded_js == 'after') $after .= implode('',$excluded_js);
			if($this->place_excluded_js == 'before') $before .= implode('',$excluded_js);
		}		
		
		if($type == 'css' && !empty($external_css)){
			if($this->place_external_css == 'after') $after .= implode('',$external_css);
			if($this->place_external_css == 'before') $before .= implode('',$external_css);
		}
		
		if($type == 'js' && !empty($external_js)){
			if($this->place_external_js == 'after') $after .= implode('',$external_js);
			if($this->place_external_js == 'before') $before .= implode('',$external_js);
		}
		
		if(!empty($excluded_css) || !empty($excluded_js) || !empty($external_css) || !empty($external_js)){
			$content = str_replace($replacemant,$before.$replacemant.$after,$content);
		}
		

		return $content;
	}

	// Check if the file is external
	protected function externalFile($url,$site_url){

		$parseurl 		= $this->util->parseUrl($url);
		$parsesite_url	= $this->util->parseUrl($site_url);
		
		
		if(isset($parseurl['host']) && $parseurl['host'] != $parsesite_url['host']){
			
			return true;
			
		}else{
			
			return false;
		}
		
	}
	
	
	// Version files
	private function versionFiles($file){
		
		if($this->enable_versions == 0) return;
		
		$version = hash("crc32b",filemtime($file));
		return '?version='.$version;
		
	}
	

	// Add indexes in cache folder
	private function addIndexes(){
		
		$blank = '<html></html>';
		
		$files	= array();
		$files 	[]= JPATH_ROOT."/cache/jcompress/index.html";
		$files 	[]= $this->cache_path."/index.html";
		$files 	[]= $this->cache_path."/css/index.html";
		$files 	[]= $this->cache_path."/js/index.html";
		
		foreach($files as $file){
			
			if(!JFile::exists($file)){
				JFile::write($file,$blank);
			}			
		}
	}
	
	// Cleare cache
	public function clearCache($remove=""){

		if($remove == 'removeall'){
			
			JFolder::delete($this->cache_path);
			
		}elseif(JFile::exists($remove)){
			
			JFile::delete($remove);
		}
		
	}
	
	// Check files log for changes
	private function checkLog($fileslog,$type=""){
		
		if(JFile::exists($fileslog)){
			require_once ($fileslog);
			$files 	= unserialize($fileslog);
		}else{
			
			return;
		}
		
		$checkExCss = false;
		$checkExJs  = false;
		
		if($type == 'css' && !empty($this->exclude_css)){
			
			$excluded = $this->exclude_css;
			$checkExCss = true;
			$cachedFile = $this->cached_css;
			
		}elseif($type == 'js' && !empty($this->exclude_js)){
			
			$excluded = $this->exclude_js;
			$checkExJs = true;
			$cachedFile = $this->cached_js;
		}
		

		foreach($files as $filename => $filetime){
			
	
			if($checkExCss || $checkExJs){
			

				if($this->util->arrayContains($excluded,$filename)){

					if($type == 'css'){
						
						$this->css_is_cached = false;
						
					}elseif($type == 'js'){
						
						$this->js_is_cached = false;
					}
					
					$this->clearCache($cachedFile);
				}
			}


		   if ((!file_exists($filename)) or filemtime($filename) > $filetime['filetime']) {
			 		
				  if($type == 'css'){
					  JFile::delete($this->cached_css);
					  $this->css_is_cached = false;
				  }elseif($type == 'js'){
					  JFile::delete($this->cached_js);
					  $this->js_is_cached = false;					  
				  }

				  JFile::delete($fileslog);
				  break;
			}
		}
	
	}
	
	
	// Exclude files from cache
	private function excludeFiles($files_array,$exlude_array,$push = false){

            foreach ($files_array as $path => $file) {
                
                foreach ($exlude_array as $find) {
                    
                    if (strpos($file, $find) !== false) {
						
						if($push){
							
							$pushed = $files_array[$path];
							unset($files_array[$path]);
							array_push($files_array, $pushed); 
                        	
						}else{
							
							unset($files_array[$path]);
							
						}
                    }
                }
            }	
			$files_array  = array_values($files_array);

			return $files_array;
	}
	
	// Relative to absoulte path CSS
	private function cssRelToAbs($absurl, $css) {
		if (!preg_match('@url@i', $css)) { return $css; }
		
		$options = $this->util->parseUrl($absurl); 
		if (!$options) { return $css; }
		$options['absurl'] = $absurl;
		$options['urlBasePath'] = substr($options['path'], 0, strrpos($options['path'],"/"));
		$options['dirDepth'] = substr_count($options['path'], '/')-1;
		$options['urlBase'] = $options['scheme'].'://'.$options['host'].$options['urlBasePath'];
		
		$css = $this->rewriteCSSUrls($css, $absurl);
		
		$replcss = preg_replace_callback(
			'\'(url\\(\s*[\\\'"]?\\s*)(.*?\\))\'',
			function ($matches) {
				while (strpos($matches[0], "/./") !== false) { 
				
					$matches[0]=str_replace("/./","/",$matches[0]); 
				} 
				if (substr($matches[2], 0, 2) === "./") {
					$matches[2] = substr($matches[2], 2); 
					return $matches[1].$matches[2];
				} else {
					return $matches[0];
				}
			},
			$css
		);

		$replcss = preg_replace_callback(
			'\'(url\\(\\s*[\\\'"]?\\s*)((\\.\\./)+)(?!\.\\./)(.*?\\))\'',
			function ($matches) use ($options) {
				$dirDepthRel = substr_count($matches[2], '../');
				$urlBasePath = $options['urlBasePath'];
				for ($i=0; $i < $dirDepthRel; $i++) {
					$urlBasePath = substr($options['urlBasePath'], 0, strrpos($options['urlBasePath'],"/"));
				}
				$urlBase = $options['scheme'].'://'.$options['host'].$urlBasePath;
				$relativeURL = $urlBase.'/'.$matches[4];
				return $matches[1].$relativeURL;
			},
			$replcss
		); 

		do {
			$tempContent = $replcss; 
			$filtcss = preg_filter('\'(url\\(.*?)(([^/]*)/\\.\\./)(.*?\\))\'', '$1$4', $replcss);
			if ($filtcss != NULL) { $replcss = $filtcss; } 
		} while ($tempContent != $replcss);
		
		
		$finalcss = preg_replace('\'(url\\(\\s*[\\\'"]?\\s*)(//)(.*?\\))\'', '$1'.$options['scheme'].':$2$3', $replcss); 
		$finalcss = preg_replace('\'(url\\(\\s*[\\\'"]?\\s*)(/)(.*?\\))\'', '$1'.$options['scheme'].'://'.$options['host'].'$2$3', $finalcss); 
		$finalcss = preg_replace('\'(url\\(\\s*[\\\'"]?\\s*)(((?!https?://)(?!data:?).)*?\\))\'', '$1'.$options['urlBase'].'/'.'$2', $finalcss); 
		
		if($this->datauri_images == 1 || $this->datauri_fonts == 1){
			preg_match_all('/url\(([\s])?([\"|\'])?(.*?)([\"|\'])?([\s])?\)/i', $finalcss, $urls, PREG_PATTERN_ORDER);
			if ($urls) {
			   foreach($urls[3] as $url) {
	
				 $todataurl = $this->toDataUrl($url);
				 $finalcss = str_replace($url,$todataurl,$finalcss);
			   }
			}
		}
		
		if(strstr($finalcss,'@import')){
				
			$re = "/(@import url\\((.*?)\\);)/"; 
			 
			preg_match_all($re, $finalcss, $matches);
			
			foreach ($matches[2] as $key => $import_file){
				
				$importfilepath 			= str_replace(array('"',"'"), "", $import_file);
				$importfileabspath 			= $this->fileLink($importfilepath,true);
				$importfile_content[$key]   = JFile::read($importfileabspath);
				$importfile_content[$key]	= $this->cssRelToAbs($importfilepath,$importfile_content[$key]);
				
				if(JFile::exists($importfileabspath)){
					$this->css_log_content [$importfileabspath]['filetime'] = filemtime($importfileabspath);
				}

			}
			
			foreach ($matches[0] as $key => $import_file_replacemant){

				$finalcss = str_replace($import_file_replacemant,$importfile_content[$key],$finalcss);
				
			}
		}
		
		return $finalcss;
	}
	
	// get absulte file links or paths
	protected function fileLink($url,$topath = false){
		
		
		if ($this->externalFile($url, $this->site_url)) return $url;
		
		$parseurl = $this->util->parseUrl($this->site_url);
		
		$parsepath ='';

		if(isset($parseurl['path'])){
			$parsepath = $parseurl['path'];
		}
		
		$domain = str_replace($parsepath, '', $this->site_url);
		
		
		if ($url && $domain && strpos($url, $domain) !== false){
			 $url = $this->util->str_replace_once($domain, "", $url);
		}
		
		if ($url && $parsepath && strpos($url, $parsepath) !== false) {
			 $url = $this->util->str_replace_once($parsepath, "", $url);
		}
		
		if($topath){
			
			$file_link = $this->site_path . $url;
			
		}else{
			
			$file_link = $this->site_url . $url;
		}
		
		return $file_link;
		
		
	}

	// get joomla body
	public static function getBody() {

		if (version_compare(JVERSION, '3.2', '=>')) {
			
			$getbody = JFactory::getApplication()->getBody();
			
		}else{
			
			$getbody = JResponse::getBody();
		}
		
		return $getbody;
		
	}

	// set joomla body
	public static function setBody( $content ) {

		if (version_compare(JVERSION, '3.2', '=>')) {
			
			JFactory::getApplication()->setBody( $content );
			
		}else{
			
			JResponse::setBody( $content );
		}
		
	}		

	// replace ../ with absolute url in CSS
	protected function rewriteCSSUrls($new_file_content, $path) {

		$path 				= str_replace(JPATH_ROOT,JURI::base(),$path);
		$path 				= str_replace("/\\","/",$path);
	
		$file_path_explode 	= explode("/",$path);
		$added_url			= "";

		if(count($file_path_explode) > 0){

			//remove the last path, cause is the current file name
			unset($file_path_explode[count($file_path_explode)-1]);
			
			$added_url 			= implode("/",$file_path_explode)."/";

			$new_file_content	= preg_replace("/(:|,)(\s*?[0-9a-zA-Z#-]*?\s*?)(\s*\burl)(\s*\()(['|\"]?)(([^\.]{2})[^h(?=t)][^\)]+)(['|\"]?)(\))/i", '$1$2$3$4$5'.implode("/",$file_path_explode)."/".'$6$8$9', $new_file_content);
			
			$pattern 			= array(); 
			$replace 			= array(); 
			$first_replace 		= "";
			
			for($i = count($file_path_explode)-1; $i >= 2; $i--){
				unset($file_path_explode[$i]);
				$replace_implode= implode("/",$file_path_explode)."/";
				$first_replace 	.= "\.\.\/";
				$pattern[]		= "/(:|,)(\s*?[0-9a-zA-Z#-]*?\s*?)(\s*\burl)(\s*\()(['|\"]?)(".$first_replace.")([^'\"|\)]*)(['|\"]?)(\))/i"; 
				$replace[] 		= '$1$2$3$4$5'.$replace_implode.'$7$8$9';

				//break the array if we get to the site root folder
				if($replace_implode == JURI::base()) break;
			}
			
			$pattern 			= array_reverse($pattern);
			$replace 			= array_reverse($replace);		

			$new_file_content 	= preg_replace($pattern, $replace, $new_file_content);
		}

		return $new_file_content;
	 }
	 
	
	// create menu list for page scan
	public function menuList($menulist='',$base='',$force = false,$echo = false,$save = false){
		
		if(empty($menulist)){
			
			$menulist = $this->menu_list;
		}
		
		if(empty($base)){
			
			$base = JURI::root();
		}
		
		if($save && !$force){
			if(JFile::exists($menulist))return;
		}

		$menus = JFactory::getApplication()->getMenu();
		$items = $menus->getMenu();
		
		$items_array  = array();
		
		
		foreach ($items as $key => $menu_item){
			
			if($menu_item->type !='component')continue;
			
			$items_array[$key]['title'] 	= str_replace("'","",$menu_item->title);
			$items_array[$key]['itemid'] 	= $menu_item->id;
			$items_array[$key]['link'] 		= $menu_item->link;
			
			if(!strstr($menu_item->link,'Itemid=')){
				$items_array[$key]['full_link'] 	= $base.$menu_item->link.'&Itemid='.$menu_item->id;
			}else{
				$items_array[$key]['full_link'] 	= $base.$menu_item->link;
			}
		}
		
		
		$items_array  = array_values($items_array);
		
		if($save){
			$menu_list_content = "<?php defined('JPATH_PLATFORM') or die ('Restricted access');\r\n \$menulist='".serialize($items_array)."';";
			JFile::write($menulist,$menu_list_content);	
		}
		
		if($echo){
			
			return $items_array;
		}
		
	}

	public function toDataUrl($url){
		 
		 $filepath 	= $this->fileLink($url,true);	
		 
		 if(!is_file($filepath)) return $url;
		 
		 if($this->externalFile($url,$this->site_url)) return $url;
		 
		 $stat = @stat($filepath);
		 $size = $stat['size'];
		 
		 if ($size > $this->datauri_size) return $url;
		 
		 $xtn = mb_strtolower(pathinfo(basename($url), PATHINFO_EXTENSION));
		 
		 if (in_array($xtn, array('jpg', 'jpeg', 'gif', 'png')) && $this->datauri_images == 1) {
			$url =	$this->util->dataUrl($url);
		 }
		 
		 if (in_array($xtn, array('ttf', 'svg', 'eot', 'otf','woff',)) && $this->datauri_fonts == 1) {
			$url =	$this->util->dataUrl($url);
		 }
		 
		 return $url;    
	}	
}