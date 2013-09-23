<?php

namespace Controller {
	
	use Lib;
	use Api;
	use stdClass;
	
	class Bracket implements Page {
	
		public static function render() {
			
			$action = Lib\Url::Get('action');
			$perma = Lib\Url::Get('bracket');
			$bracket = Api\Bracket::getBracketByPerma($perma);

			if ($bracket) {
				Lib\Display::setVariable('BRACKET_ID', $bracket->id);
				Lib\Display::setVariable('TITLE', $bracket->name . ' - The Great Awwnime Bracket');
				switch ($action) {
					case 'nominate':
						if ($bracket->state == BS_NOMINATIONS) {
							self::_displayNominations($bracket);
						} else {
							// error
						}
						break;
					case 'vote':
						if ($bracket->state == BS_ELIMINATIONS || $bracket->state == BS_VOTING || $bracket->state == BS_WILDCARD) {
							self::_displayCurrentRound($bracket->id);
						}
						break;
					case 'tier':
						$tier = Lib\Url::GetInt('tier');
						self::_displayRound($bracket->id, $tier);
						break;
					case 'view':
						self::_displayBracketView($bracket);
						break;
				}
			} else {
				Lib\Display::setVariable('title', 'The Great Awwnime Bracket');
				self::_displayLanding();
			}
			
		}
		
		public static function registerExtension($class, $method, $type) { }

		private static function _checkLogin() {
			$user = Api\User::getCurrentUser();
			$readonly = Lib\Url::GetBool('readonly', null);
			if (!$user && !$readonly) {
				header('Location: /login/?redirect=' . urlencode($_GET['q']));
				exit;
			}

			// Setup a default user if we're in readonly
			if (!$user) {
				$user = new stdClass;
				$user->id = 0;
			}

			return $user;
		}
		
		private static function _displayRound($bracket, $tier) {
			$user = self::_checkLogin();
			$cacheKey = 'BracketRound_' . $bracket . '_' . $tier . '_' . $user->id;
			$out = Lib\Cache::Get($cacheKey);
			
			if (false === $out) {
				$rounds = Api\Round::getBracketRounds($bracket, $tier);
				$out = Lib\Display::compile($rounds, 'round', $cacheKey);
			}
			
			Lib\Display::setTemplate('round');
			Lib\Display::setVariable('content', $out);
			
		}
		
		private static function _displayCurrentRound($bracketId) {
			$user = self::_checkLogin();
			$cacheKey = 'CurrentRound_' . $bracketId . '_' . $user->id;
			$out = Lib\Cache::Get($cacheKey);
			
			if (false === $out) {
				$out = new stdClass;
				$out->userId = $user->id;
				$out->round = Api\Round::getCurrentRounds($bracketId);
				$out = Lib\Display::compile($out, 'round', $cacheKey);
			}
			
			Lib\Display::setTemplate('round');
			Lib\Display::setVariable('content', $out);
			
		}

		private static function _displayLanding() {
			$brackets = Api\Bracket::getAll();
			$content = Lib\Display::compile($brackets, 'landing');
			Lib\Display::setTemplate('default');
			Lib\Display::setVariable('content', $content);
		}

		private static function _displayBracketView($bracket) {
			$bracket->results = $bracket->getResults();
			$user = Api\User::getCurrentUser();
			if ($user) {
				$bracket->userVotes = $bracket->getVotesForUser($user);
			}
			Lib\Display::setTemplate('results');
			Lib\Display::setVariable('data', json_encode($bracket));
			Lib\Display::setVariable('title', $bracket->name);
		}

		private static function _displayNominations($bracket) {
			self::_checkLogin();
			Lib\Display::setVariable('rules', Lib\Michelf\Markdown::defaultTransform($bracket->rules));
			Lib\Display::setVariable('perma', $bracket->perma);
			Lib\Display::setTemplate('nominate');
		}
	
	}

}