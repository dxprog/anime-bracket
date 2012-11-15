<?php

namespace Lib {

	use DOMDocument;
	use stdClass;
	use XSLTProcessor;

	class Display {
		
		private static $_templateVars = array();
		private static $_theme;
		private static $_pageTemplate;
		private static $_rendered = false;
		private static $_extensions = array();
		
		/**
		 * Renders the page
		 **/
		public static function render()
		{
		
			if (!self::$_rendered) {
				
				$out = file_get_contents(VIEW_PATH . self::$_theme . '/' . self::$_pageTemplate . '.tpl');
				
				// Replace all the variables in the template
				foreach (self::$_templateVars as $name=>$val) {
					$out = str_replace('{'.$name.'}', $val, $out);
				}
				
				// Run through all the extensions and run/replace where necessary
				foreach (self::$_extensions as $extension) {
					$tag = '{' . strtoupper($extension->tag) . '}';
					if (strpos($out, $tag) !== false) {
						$value = call_user_func(array('Controller\\' . $extension->class, $extension->method));
						$out = str_replace($tag, $value, $out);
					}
				}
				
				header('Content-Type: text/html; charset=utf-8');
				echo $out;
				self::$_rendered = true;
			}
		}
		
		/**
		 * Registers a display extension to be caught at render time
		 */
		public static function registerExtension($class, $method, $tag) {
			$obj = new stdClass();
			$obj->class = $class;
			$obj->method = $method;
			$obj->tag = $tag;
			self::$_extensions[] = $obj;
		}
		
		// Displays an error message and halts rendering
		public static function showError($code, $message) {
			global $_title;
			$content = self::compile('<error><code>' . $code . '</code><message>' . $message . '</message></error>', 'error');
			self::setVariable('title', 'Error - ' . $_title);
			self::setVariable('content', $content);
			self::setTemplate('simple');
			self::render();
		}
		
		public static function setTheme($name) {
			self::$_theme = $name;
		}
		
		public static function setTemplate($name) {
			self::$_pageTemplate = $name;
		}
		
		public static function setVariable($name, $val) {
			self::$_templateVars[strtoupper($name)] = $val;
		}
		
		public static function compile($data, $template, $cacheKey = false) {
			
			global $_baseURI, $_theme;
			$retVal = false;
			
			if (!self::$_rendered && file_exists(VIEW_PATH . self::$_theme . '/' . $template . '.xslt')) {
				
				$retVal = false !== $cacheKey ? Cache::Get($cacheKey) : false;
				if (!$retVal || !is_string($retVal)) {		
					$xml = new DOMDocument();
					$xsl = new DOMDocument();
					$t = new XSLTProcessor();
					$parseXml = false;
					
					// If the incoming data is an object, serialize it before continuing
					if (!is_string($data)) {
						$xs = new SerializeXML();
						$parseXml = $xs->serialize($data, $template);
					} else {
						$parseXml = $data;
					}

					// Run the transform and return the results
					if (!$xml->loadXML($parseXml)) {
						self::showError('XML Parse Error', 'Something went kablooie while rendering this page!');
					}
					$xsl->load(VIEW_PATH . self::$_theme . '/' . $template . '.xslt');
					$t->importStyleSheet($xsl);
					$t->registerPHPFunctions();
					$retVal = str_replace('_BASEURI', $_baseURI, $t->transformToXML($xml));
					$retVal = str_replace(' xmlns:php="http://php.net/xsl"', '', $retVal);
					unset($t);
					unset($xsl);
					unset($xml);
					
					// Strip XML headers out
					$retVal = str_replace('<?xml version="1.0"?>', '', $retVal);
					
					if (false !== $cacheKey) {
						Cache::Set($cacheKey, $retVal);
					}
				}

			}
			
			return $retVal;
		}

	}
}