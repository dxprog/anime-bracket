<?php

namespace Controller {

	use Lib;
	use Api;
	use stdClass;

	class Bracket implements Page {

		public static function render() {

			self::initTemplateHelpers();

			$action = Lib\Url::Get('action');
			$perma = Lib\Url::Get('bracket');
			$bracket = Api\Bracket::getBracketByPerma($perma);

			Lib\Display::addKey('CSS_VERSION', CSS_VERSION);
			Lib\Display::addKey('JS_VERSION', JS_VERSION);
			Lib\Display::addKey('USE_MIN', USE_MIN);

			if ($bracket) {
				Lib\Display::addKey('bracket_id', $bracket->id);
				Lib\Display::addKey('title', $bracket->name . ' - The Great Awwnime Bracket');
				switch ($action) {
					case 'nominate':
						if ($bracket->state == BS_NOMINATIONS && $bracket->start < time()) {
							self::_displayNominations($bracket);
						}
						break;
					case 'vote':
						if ($bracket->start <=  time() && ($bracket->state == BS_ELIMINATIONS || $bracket->state == BS_VOTING || $bracket->state == BS_WILDCARD)) {
							self::_displayCurrentRound($bracket->id);
						}
						break;
					case 'view':
						self::_displayBracketView($bracket);
						break;
					case 'characters':
						self::_displayBracketCharacters($bracket)	;
						break;
				}
			} else {
				if ($action === 'all') {
					self::_displayBrackets();
				} else {
					self::_displayLanding();
				}
			}

		}

		private static function _checkLogin() {
			$user = Api\User::getCurrentUser();
			$readonly = Lib\Url::GetBool('readonly', null);
			if (!$user && !$readonly && stripos($_SERVER['HTTP_USER_AGENT'], 'google') === false) {
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

		private static function _displayCurrentRound($bracketId) {
			$user = self::_checkLogin();
			$cacheKey = 'CurrentRound_' . $bracketId . '_' . $user->id;
			$out = Lib\Cache::fetch(function() use ($user, $bracketId) {
				$out = new stdClass;
				$out->userId = $user->id;
				$out->prizes = isset($user->prizes) && $user->prizes ? 1 : 0;
				$out->bracket = Api\Bracket::getById($bracketId);
				$out->round = Api\Round::getCurrentRounds($bracketId);
				$out->groupName = self::_getGroupName($out->round);
				return $out;
			}, $cacheKey, CACHE_MEDIUM);

			if ($out) {
				$template = $out->bracket->state == BS_ELIMINATIONS ? 'eliminations' : 'voting';
				Lib\Display::addKey('page', 'vote');
				Lib\Display::renderAndAddKey('content', $template, $out);
			}

		}

		private static function _displayLanding() {
			Lib\Display::addKey('page', 'landing');
			Lib\Display::renderAndAddKey('content', 'landing', null);
		}

		private static function _displayBrackets() {

			$brackets = Lib\Cache::fetch(function() {

				$brackets = Api\Bracket::getAll();

				// Check for card images
				foreach ($brackets as $bracket) {
					if (is_readable('./images/bracket_' . $bracket->id . '_card.jpg')) {
						$bracket->cardImage = '/images/bracket_' . $bracket->id . '_card.jpg';
					} else {
						$bracket->entrants = Api\Character::getRandomCharacters($bracket, 9);
					}
				}

				// Sort the brackets by reverse date
				usort($brackets, function($a, $b) {
					return $a->start > $b->start ? -1 : 1;
				});

				return $brackets;

			}, 'Controller::Brackets_displayBrackets');

			Lib\Display::addKey('page', 'brackets');
			Lib\Display::renderAndAddKey('content', 'bracketsView', $brackets);
		}

		private static function _displayBracketView($bracket) {
			$bracket->results = $bracket->getResults();
			$user = Api\User::getCurrentUser();
			if ($user) {
				$bracket->userVotes = $bracket->getVotesForUser($user);
			}
			Lib\Display::addKey('page', 'results');
			Lib\Display::renderAndAddKey('content', 'results', $bracket);
		}

		private static function _displayNominations($bracket) {
			self::_checkLogin();
			$out = new stdClass;
			$out->rules = Lib\Michelf\Markdown::defaultTransform($bracket->rules);
			$out->bracket = $bracket;
			Lib\Display::addKey('page', 'nominate');
			Lib\Display::renderAndAddKey('content', 'nominate', $out);
		}

		private static function _displayBracketCharacters($bracket) {
			$out = new stdClass;
			$out->characters = Api\Character::getByBracketId($bracket->id);
			$out->bracket = $bracket;
			Lib\Display::addKey('page', 'characters');
			$content = Lib\Display::renderAndAddKey('content', 'characters', $out);
		}

		/**
		 * Given an array of rounds, determine's the group name
		 */
		private static function _getGroupName($rounds) {
			
			$retVal = 'Group';

			foreach($rounds as $round) {
				if (!isset($groups[$round->group])) {
					$groups[$round->group] = 0;
				}
				$groups[$round->group]++;
			}

			if (count($groups) === 1) {
				$retVal .= ' ' . chr(65 + array_keys($groups)[0]);
			} else {

			}

			return $retVal;
		}

		public static function initTemplateHelpers() {

            Lib\Display::addHelper('isBracketNotHidden', function($template, $context, $args, $source) {
                $bracket = $context->get($args);
                $retVal = '';

                if ($bracket instanceof Api\Bracket && $bracket->state != BS_HIDDEN) {
                	$retVal = $template->render($context);
                }

                return $retVal;
            });

            Lib\Display::addHelper('hasNotStarted', function($template, $context, $args, $source) {
            	return self::_bracketStateIs($template, $context, $args, BS_NOT_STARTED);
            });

            Lib\Display::addHelper('isBracketNominations', function($template, $context, $args, $source) {
            	return self::_bracketStateIs($template, $context, $args, BS_NOMINATIONS);
            });

            Lib\Display::addHelper('isBracketEliminations', function($template, $context, $args, $source) {
				return self::_bracketStateIs($template, $context, $args, BS_ELIMINATIONS);
            });

            Lib\Display::addHelper('isBracketVoting', function($template, $context, $args, $source) {
                return self::_bracketStateIs($template, $context, $args, BS_VOTING);
            });

            Lib\Display::addHelper('isBracketFinal', function($template, $context, $args, $source) {
            	return self::_bracketStateIs($template, $context, $args, BS_FINAL);
            });

            Lib\Display::addHelper('hasResults', function($template, $context, $args, $source) {
				$retVal = '';
				$bracket = $context->get($args);
				if ($bracket instanceof Api\Bracket && ($bracket->state == BS_VOTING || $bracket->state == BS_FINAL)) {
					$retVal = $template->render($context);
				}
				return $retVal;
            });

		}

		private static function _bracketStateIs($template, $context, $args, $state) {
			$retVal = '';
			$bracket = $context->get($args);
			if ($bracket instanceof Api\Bracket && $bracket->state == $state) {
				$retVal = $template->render($context);
			}
			return $retVal;
		}

	}

}