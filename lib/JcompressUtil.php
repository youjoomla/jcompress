<?php
/**
 * @package      JCompress
 * @copyright    Copyright(C) since 2007  Youjoomla.com. All Rights Reserved.
 * @author       YouJoomla
 * @license      http://www.gnu.org/licenses/gpl-2.0.html GNU/GPLv2 only
 * @websites     http://www.youjoomla.com | http://www.yjsimplegrid.com
 */
// no direct access 
defined( '_JEXEC' ) or die( 'Restricted index access' );


class JcompressUtil {
    
    // Cleanup white space, tabs and new lines
    public static function htmlCleanup($content, $spaces = false) {
        
        if ($spaces) {
            
            $reg = array(
                '/\>[^\S ]+/s',
                '/[^\S ]+\</s',
                '/\>[\s]+\</s'
            );
            
            $rep = array(
                '> ',
                ' <',
                '> <'
            );
            
        } else {
            
            $reg = "/^\n+|^[\t\s]*\n+/m";
            $rep = "";
            
        }
        
        $new_content = preg_replace($reg, $rep, $content);
        
        
        return $new_content;
    }
    
    // Array contains a string
    public static function arrayContains($array, $string) {
        foreach ($array as $name) {
            if (stripos($string, $name) !== FALSE) {
                return true;
            }
        }
    }
    
    
    public static function parseUrl($url) {
        
        if (version_compare(PHP_VERSION, '5.3.0') >= 0) {
            
            return self::split_url($url);
            
        } else {
            
            return parse_url($url);
        }
        
    }
    
    // Remove white space
    public static function wspace($string) {
        
        $string = preg_replace(array(
            '/[^(http:)]\/\/.*$/m',
            '/\/\*.*\*\//U',
            '/\s+/'
        ), array(
            '',
            '',
            ' '
        ), $string);
        return $string;
    }
    
    
    // Remove coments
    public static function removeComments($txt) {
        $txt = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $txt);
        return $txt;
    }
    
    // Remove empty lines
    public static function removeEmptyLines($txt) {
        $txt = str_replace(array(
            "\r\n",
            "\r",
            "\n",
            "\t",
            '  ',
            '    ',
            '    '
        ), '', $txt);
        return $txt;
    }
    
    
    // str_replace once
    public static function str_replace_once($search, $replace, $subject) {
        $pos = strpos($subject, $search);
        if ($pos !== false) {
            $subject = substr_replace($subject, $replace, $pos, strlen($search));
        }
        return $subject;
    }
    
    
    // get everything inside head tag
    public static function getHeadData($buffer) {
        
        //remove the commented tags
        $buffer = preg_replace('/\<\!\-\-.*\-\-\>/Us', '', $buffer);
        
        $linksRegexHead = "#(<head>)(.*?)(</head>)#is";
        preg_match_all($linksRegexHead, $buffer, $head_content);
        
        
        $scriptRegex = "/<\s*script[^>]+.*src=['\"]([^'\"]*)['\"].*[\/>|><\/script>]/i";
        preg_match_all($scriptRegex, $head_content[2][0], $js_matches);
        
        foreach ($js_matches[1] as $row => $change_file) {
            
            preg_match("/\\.js(.*)/", $change_file, $extrajs);
            
            if (!empty($extrajs[1])) {
                $js_matches[1][$row] = str_replace($extrajs[1], '', $change_file);
            }
            
            if (!strstr($change_file, ".js") || strstr($change_file, "index.php") || !strstr($js_matches[0][$row], "text/javascript")) {
                unset($js_matches[0][$row]);
                unset($js_matches[1][$row]);
            }
        }
        
        $js_matches[0] = array_values($js_matches[0]);
        $js_matches[1] = array_values($js_matches[1]);
        
        
        $buffer_uncomment = preg_replace('#<!--\[[^\[<>].*?(?<!!)-->#s', '', $buffer);
        
        $linksRegex = "|<\s*link[^>]+.*href=['\"]([^'\"]*)['\"].*[/]?>|U";
        preg_match_all($linksRegex, $buffer_uncomment, $css_matches);
        
        //check for external or media or index.php
        foreach ($css_matches[1] as $row => $change_file) {
            
            
            preg_match("/\\.css(.*)/", $change_file, $extracss);
            if (!empty($extracss[1])) {
                $css_matches[1][$row] = str_replace($extracss[1], '', $change_file);
            }
            
            if (!strstr($css_matches[0][$row], "text/css")) {
                
                unset($css_matches[0][$row]);
                unset($css_matches[1][$row]);
            }
        }
        
        $css_matches[0] = array_values($css_matches[0]);
        $css_matches[1] = array_values($css_matches[1]);
        
        
        //inline js inside head
        $linksRegex = "#<script\b((?!>|\bsrc\w*=).)*>(.*?)</script>#is";
        preg_match_all($linksRegex, $head_content[2][0], $inline_js_head);
        
        
        foreach ($inline_js_head[2] as $key => $inline_js) {
            
            $inline_js_head[$key] = $inline_js;
        }
        
        $linksRegex = "#<style type=['\"]text/css['\"]>(.*?)</style>#is";
        preg_match_all($linksRegex, $head_content[2][0], $inline_css_head);
        
        return array(
            
            'headTagContent' => $head_content[2][0],
            'styleSheets' => $css_matches[1],
            'styleSheetsLines' => $css_matches[0],
            'scripts' => $js_matches[1],
            'scriptsLines' => $js_matches[0],
            'inline_js_head' => $inline_js_head,
            'inline_css_head' => $inline_css_head[1]
        );
    }
    
    public static function setHead($body, $newhead) {
        
        $RegexHead = "#(<head>)(.*?)(</head>)#is";
        preg_match_all($RegexHead, $body, $head_content);
        
        $head = $head_content[2][0];
        
        $compres_head = self::htmlCleanup($newhead);
        
        $body = str_replace($head, $compres_head, $body);
        return $body;
    }

    protected static function split_url($url, $decode = TRUE) {
        
        $xunressub = 'a-zA-Z\d\-._~\!$&\'()*+,;=';
        $xpchar    = $xunressub . ':@%';
        
        $xscheme = '([a-zA-Z][a-zA-Z\d+-.]*)';
        
        $xuserinfo = '(([' . $xunressub . '%]*)' . '(:([' . $xunressub . ':%]*))?)';
        
        $xipv4 = '(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})';
        
        $xipv6 = '(\[([a-fA-F\d.:]+)\])';
        
        $xhost_name = '([a-zA-Z\d-.%]+)';
        
        $xhost      = '(' . $xhost_name . '|' . $xipv4 . '|' . $xipv6 . ')';
        $xport      = '(\d*)';
        $xauthority = '((' . $xuserinfo . '@)?' . $xhost . '?(:' . $xport . ')?)';
        
        $xslash_seg    = '(/[' . $xpchar . ']*)';
        $xpath_authabs = '((//' . $xauthority . ')((/[' . $xpchar . ']*)*))';
        $xpath_rel     = '([' . $xpchar . ']+' . $xslash_seg . '*)';
        $xpath_abs     = '(/(' . $xpath_rel . ')?)';
        $xapath        = '(' . $xpath_authabs . '|' . $xpath_abs . '|' . $xpath_rel . ')';
        
        $xqueryfrag = '([' . $xpchar . '/?' . ']*)';
        
        $xurl = '^(' . $xscheme . ':)?' . $xapath . '?' . '(\?' . $xqueryfrag . ')?(#' . $xqueryfrag . ')?$';
        
        
        // Split the URL into components.
        if (!preg_match('!' . $xurl . '!', $url, $m))
            return FALSE;
        
        if (!empty($m[2]))
            $parts['scheme'] = strtolower($m[2]);
        
        if (!empty($m[7])) {
            if (isset($m[9]))
                $parts['user'] = $m[9];
            else
                $parts['user'] = '';
        }
        if (!empty($m[10]))
            $parts['pass'] = $m[11];
        
        if (!empty($m[13]))
            $h = $parts['host'] = $m[13];
        else if (!empty($m[14]))
            $parts['host'] = $m[14];
        else if (!empty($m[16]))
            $parts['host'] = $m[16];
        else if (!empty($m[5]))
            $parts['host'] = '';
        if (!empty($m[17]))
            $parts['port'] = $m[18];
        
        if (!empty($m[19]))
            $parts['path'] = $m[19];
        else if (!empty($m[21]))
            $parts['path'] = $m[21];
        else if (!empty($m[25]))
            $parts['path'] = $m[25];
        
        if (!empty($m[27]))
            $parts['query'] = $m[28];
        if (!empty($m[29]))
            $parts['fragment'] = $m[30];
        
        if (!$decode)
            return $parts;
        if (!empty($parts['user']))
            $parts['user'] = rawurldecode($parts['user']);
        if (!empty($parts['pass']))
            $parts['pass'] = rawurldecode($parts['pass']);
        if (!empty($parts['path']))
            $parts['path'] = rawurldecode($parts['path']);
        if (isset($h))
            $parts['host'] = rawurldecode($parts['host']);
        if (!empty($parts['query']))
            $parts['query'] = rawurldecode($parts['query']);
        if (!empty($parts['fragment']))
            $parts['fragment'] = rawurldecode($parts['fragment']);
        return $parts;
    }
    
    
    public static function mime_content_type($filename) {
        
        $mime_types = array(
            
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',
            
            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',
            
            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',
            
            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            
            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',
            
            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            
            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
        );
        
        $ext = strtolower(array_pop(explode('.', $filename)));
        if (array_key_exists($ext, $mime_types)) {
            return $mime_types[$ext];
        } elseif (function_exists('finfo_open')) {
            $finfo    = finfo_open(FILEINFO_MIME);
            $mimetype = finfo_file($finfo, $filename);
            finfo_close($finfo);
            return $mimetype;
        } else {
            return 'application/octet-stream';
        }
    }
    
    public static function dataUrl($url) {
        
        $mime      = self::mime_content_type($url);
        $processed = base64_encode(file_get_contents($url));
        $dataUrl   = 'data:';
        $dataUrl .= $mime . ';base64,';
        $dataUrl .= $processed;
        
        return $dataUrl;
    }
	
	
	// Process inline CSS
	public static function processInlineCss($inlinecss,$content){
		
		foreach($inlinecss as $css){
			
			$compresscss = self::removeEmptyLines($css);
			$content = str_replace($css,$compresscss,$content);
		}
		
		return $content;
		
	}	

	// Process inline JS
	public static function processInlineJs($inlinejs,$content){
		
		foreach($inlinejs as $js){
			
			$compressed_js = self::wspace($js);
			$content = str_replace($js,$compressed_js,$content);
		}
		
		return $content;
	}
	
	// Place JS at the bottom of the page
	public static function jsToBottom($body){
		
        $jsRegex = "@(<script[^>]*?.*?</script>)@siu";
        preg_match_all($jsRegex, $body, $js_body,PREG_PATTERN_ORDER);

		$allScripts = array();
		if($js_body){
			foreach ($js_body[0] as $script){
				
				$allScripts [] = $script;
				$body = str_replace($script,'',$body);
				
			}
		}
		$body = str_replace('</body>',implode($allScripts).'</body>',$body);
		$body = self::htmlCleanup($body);
		return $body;
	
	} 


}