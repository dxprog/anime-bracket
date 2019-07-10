<?php

namespace Controller {

    use Api;
    use Lib;
    use stdClass;

    abstract class Page {

        public abstract static function generate(array $params);

        public static final function render(array $params) {

            // Set some page default things
            Lib\Display::addKey('title', DEFAULT_TITLE);
            Lib\Display::setLayout('default');
            static::_initTemplateHelpers();

            Lib\Display::addKey('CSS_VERSION', CSS_VERSION);
            Lib\Display::addKey('JS_VERSION', JS_VERSION);
            Lib\Display::addKey('USE_MIN', USE_MIN);

            $user = Api\User::getCurrentUser();
            Lib\Display::addKey('user', $user);
            Lib\Display::addKey('route', urlencode(Lib\Url::Get('q', '/')));

            // If we have a user, seed the test bucket so that
            // random distribution is deterministic
            if ($user) {
                Lib\TestBucket::initialize($user->id);
            }

            // Kick off page specific rendering
            static::generate($params);

        }

        /**
         * Registers some generic bracket related template helpers
         */
        public static function _initTemplateHelpers() {

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
                } else {
                    $template->setStopToken('else');
                    $template->discard($context);
                    $template->setStopToken(false);
                    $retVal = $template->render($context);
                }
                return $retVal;
            });

        }

        protected static function _bracketStateIs($template, $context, $args, $state) {
            $retVal = '';
            $bracket = $context->get($args);

            $context->push($context->last());
            if ($bracket instanceof Api\Bracket && $bracket->state == $state) {
                $template->setStopToken('else');
                $retVal = $template->render($context);
                $template->setStopToken(false);
                $template->discard($context);
            } else {
                $template->setStopToken('else');
                $template->discard($context);
                $template->setStopToken(false);
                $retVal = $template->render($context);
            }
            $context->pop();

            return $retVal;
        }

        protected static function _checkLogin() {
            $user = Api\User::getCurrentUser();
            $readonly = Lib\Url::GetBool('readonly', null);
            if (!$user && !$readonly && stripos($_SERVER['HTTP_USER_AGENT'], 'google') === false) {
                header('Location: /user/login/?redirect=' . urlencode($_GET['q']));
                exit;
            }

            // Setup a default user if we're in readonly
            if (!$user) {
                $user = new stdClass;
                $user->id = 0;
            }

            return $user;
        }

        protected static function _enableAd() {
            Lib\Display::addKey('showAd', true);
            Lib\Display::addKey('isMobile', preg_match('/(iphone|android)/i', $_SERVER['HTTP_USER_AGENT']));
        }

        /**
         * Verifies that the request has the correct CSRF token either
         * via query string or request body.
         *
         * @return bool
         */
        protected static function _verifyCsrf($user) {
            $csrfToken = Lib\Url::Post('_auth', Lib\Url::Get('_auth', null));
            return $user->csrfToken === $csrfToken;
        }

    }

}
