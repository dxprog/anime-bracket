<?php

namespace Controller {
	
	use Lib;
	use Api;
	
	class Bracket implements Page {
	
		public static function render() {
			
			$action = Lib\Url::Get('action');
			
			switch ($action) {
				case 'nominate':
					Lib\Display::setTemplate('nominate');
					break;
				case 'vote':
					self::_displayCurrentRound(3);
					break;
				case 'tier':
					$tier = Lib\Url::GetInt('tier');
					self::_displayRound(3, $tier);
					break;
			}
			
		}
		
		public static function registerExtension($class, $method, $type) {
		
		}
		
		private static function _displayRound($bracket, $tier) {
			
			$cacheKey = 'BracketRound_' . $bracket . '_' . $tier . '_' . $_SERVER['REMOTE_ADDR'];
			$out = Lib\Cache::Get($cacheKey);
			
			if (false === $out) {
				$rounds = Api\Round::getBracketRounds($bracket, $tier);
				$out = Lib\Display::compile($rounds, 'round', $cacheKey);
			}
			
			Lib\Display::setTemplate('round');
			Lib\Display::setVariable('content', $out);
			
		}
		
		private static function _displayCurrentRound($bracketId) {
			
			$cacheKey = 'CurrentRound_' . $bracketId . '_' . $_SERVER['REMOTE_ADDR'];
			$out = Lib\Cache::Get($cacheKey);
			
			if (false === $out) {
				$rounds = Api\Round::getCurrentRounds($bracketId);
				$out = Lib\Display::compile($rounds, 'round', $cacheKey);
			}
			
			Lib\Display::setTemplate('round');
			Lib\Display::setVariable('content', $out);
			
		}
	
	}

}