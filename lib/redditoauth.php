<?php

namespace Lib {

  class RedditOAuth {

    const REDDIT_API_URL = 'https://ssl.reddit.com/api/v1';
    const REDDIT_OAUTH_URL = 'https://oauth.reddit.com';

    private $clientId = null;
    private $clientSecret = null;
    private $clientUserAgent = null;
    private $handlerUrl = null;
    private $token = null;
    private $refreshToken = null;
    private $expiration = null;

    public function __construct($clientId, $clientSecret, $userAgent, $handlerUrl) {
      $this->clientId = $clientId;
      $this->clientSecret = $clientSecret;
      $this->clientUserAgent = $userAgent;
      $this->handlerUrl = $handlerUrl;
    }

    /**
     * Makes a call to get the token or, if a token has been retrieved, returns the last result
     */
    public function getToken($code = '') {

      // If we have a token set and a new code isn't being passed in,
      // use what's already available
      $retVal = $this->token && !$code ? $this->token : false;

      if (!$retVal && $code) {

        $response = $this->_post('access_token', [
          'grant_type' => 'authorization_code',
          'code' => $code,
          'redirect_uri' => $this->handlerUrl
        ], false);

        $this->_updateToken($response);
        $retVal = $this->token;

      }

      return $retVal;
    }

    public function setToken($token) {
      $this->token = $token;
    }

    public function getRefreshToken() {
      return $this->refreshToken;
    }

    public function setRefreshToken($refreshToken) {
      $this->refreshToken = $refreshToken;
    }

    public function getExpiration() {
      return $this->expiration;
    }

    public function setExpiration($expiration) {
      $this->expiration = $expiration;
    }

    public function getLoginUrl($duration, array $scope, $state = null) {
      $params = [
        'response_type' => 'code',
        'client_id' => $this->clientId,
        'scope' => implode(',', $scope),
        'redirect_uri' => $this->handlerUrl,
        'state' => $state ? $state : md5(rand()),
        'duration' => $duration
      ];

      return self::REDDIT_API_URL . '/authorize?' . $this->_urlEncodeKVPs($params);
    }

    /**
     * Makes an OAuthenticated API call to reddit
     */
    public function call($endpoint, $params = null) {
      $retVal = false;

      if ($this->token) {
        if (!$params) {
          $retVal = $this->_get($endpoint);
        } else {
          $retVal = $this->_post($endpoint, $params);
        }
      }

      return $retVal;
    }

    private function _updateToken($response) {
      if ($response && isset($response->access_token)) {
        $this->token = $response->access_token;
        if (isset($response->refresh_token)) {
          $this->refreshToken = $response->refresh_token;
        }

        if (isset($response->expires_in)) {
          $this->expiration = time() + $response->expires_in;
        }
        $retVal = $this->token;
      } else {
        $this->token = null;
      }
    }

    /**
     * Verifies that the current token is still fresh. If not,
     * refreshs if possible
     */
    private function _verifyTokenFresh() {
      $retVal = time() < $this->expiration;

      if (!$retVal && $this->refreshToken) {
        $response = $this->_post('access_token', [
          'grant_type' => 'refresh_token',
          'refresh_token' => $this->refreshToken
        ], false);

        $this->_updateToken($response);
        $retVal = !!$this->token;
      }

      return $retVal;
    }

    private function _createCurl($endpoint, $isOauth = true) {
      $apiUrl = $isOauth ? self::REDDIT_OAUTH_URL : self::REDDIT_API_URL;

      $retVal = curl_init($apiUrl . '/' . $endpoint);
      curl_setopt($retVal, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($retVal, CURLOPT_USERAGENT, $this->clientUserAgent);

      // For authenticated requests
      if ($isOauth && $this->_verifyTokenFresh()) {
        curl_setopt($retVal, CURLOPT_HTTPHEADER, [ 'Authorization: bearer ' . $this->token ]);
      // For authentication requests
      } else {
        curl_setopt($retVal, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($retVal, CURLOPT_USERPWD, $this->clientId . ':' . $this->clientSecret);
      }

      return $retVal;
    }

    private function _get($endpoint) {
      $c = $this->_createCurl($endpoint);
      $response = curl_exec($c);
      return $response ? json_decode($response) : false;
    }

    /**
     * Makes an OAuthenticated POST request
     */
    private function _post($endpoint, array $params, $isOauth = true) {
      $c = $this->_createCurl($endpoint, $isOauth);
      curl_setopt($c, CURLOPT_POST, true);
      curl_setopt($c, CURLOPT_POSTFIELDS, $params);
      $response = curl_exec($c);
      return $response ? json_decode($response) : false;
    }

    private function _urlEncodeKVPs($params) {
        $retVal = [];
        foreach ($params as $key => $value) {
            $retVal[] = urlencode($key) . '=' . urlencode($value);
        }
        return implode('&', $retVal);
    }
  }

}