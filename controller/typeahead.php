<?php

namespace Controller {

    use Api;
    use Lib;

    class Typeahead {

        public static function render() {

            $query = Lib\Url::Get('q');
            $bracketId = Lib\Url::GetInt('bracketId');
            $out = Api\MalItem::getNameTypeahead($query, 'character');

            if ($bracketId) {
                $out = self::_getSimilarCharacters($bracketId, $query, $out);
            }

            // Standardize the output
            $out = self::_standardizeData($out);

            Lib\Display::renderJson($out);

        }

        private static function _getSimilarCharacters($bracketId, $query, $out) {

            $retVal = $out;

            // Search for similar entered characters first
            $characters = Api\Character::searchBracketCharacters($query, $bracketId);
            if ($characters && count($characters)) {
                $retVal = array_merge($characters, $retVal);
            } else {

                // Search nominees so that maybe we can prevent another similar character being nominated

            }

            return $retVal;
        }

        private static function _standardizeData($out) {
            $retVal = [];

            foreach ($out as $suggestion) {
                if ($suggestion instanceof Api\Character || $suggestion instanceof Api\Nominee) {
                    $retVal[] = [
                        'order' => 0, // database items take precedence over MAL
                        'id' => $suggestion->id,
                        'name' => $suggestion->name,
                        'source' => $suggestion->source,
                        'image' => $suggestion->image,
                        'thumb' => $suggestion->image
                    ];
                } else if ($suggestion instanceof Api\MalItem) {
                    $retVal[] = [
                        'order' => 1,
                        'id' => $suggestion->id,
                        'name' => $suggestion->name,
                        'source' => count($suggestion->sources) ? $suggestion->sources[0]->name : '',
                        'image' => str_replace('t.jpg', '.jpg', $suggestion->pic),
                        'thumb' => $suggestion->pic
                    ];
                }
            }

            return $retVal;
        }

    }

}