<?php

namespace Lib {

	class SerializeXML {

		public static function serialize($object, $root = 'root') {
			
			return '<?xml version="1.0" encoding="utf-8"?>' . self::_serializeItem($object, $root);
			
		}
		
		private function _serializeItem($item, $root, $attributes = '') {
			
			$retVal = '<' . $root . $attributes . '>';
			
			if (null === $item) {
				$retVal .= 'null';
			} elseif (is_object($item) || is_array($item)) {
				
				foreach ($item as $key=>$val) {
					$elName = is_numeric($key) ? $root . '_item' : $key;
					$elAttr = is_numeric($key) ? ' index="' . $key . '"' : '';
					$retVal .= self::_serializeItem($val, $elName, $elAttr);
				}
				
			} elseif (is_bool($item)) {
				$retVal .= false == $item ? 'false' : 'true';
			} elseif (is_string($item)) {
				$value = htmlspecialchars($item, ENT_NOQUOTES | 8, 'UTF-8', false);
				$value = str_replace(array('&lt;', '&gt;'), array('<', '>'), $value);
				$retVal .= '<![CDATA[' . $value . ']]>';
			} elseif (is_numeric($item)) {
				$retVal .= $item;
			} else {
				// $retVal .= $item;
			}
			
			$retVal .= '</' . $root . '>';
			return $retVal;
		
		}
		
	}
	
}