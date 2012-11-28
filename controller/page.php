<?php

namespace Controller {

	interface Page {

		public static function render();
		public static function registerExtension($class, $method, $type);

	}
	
}