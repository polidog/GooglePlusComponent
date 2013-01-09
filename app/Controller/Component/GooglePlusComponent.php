<?php

App::uses('Component', 'Controller');
App::uses('CakeSession', 'Model/Datasource');
App::uses('HttpSocket', 'Network/Http');

/**
 * GooglePlus用コンポーネント 
 * @property SessionComponent $Session
 * @property HttpSocket
 * @author polidog <polidogs@gmail.com>
 * 
 * @see https://developers.google.com/accounts/docs/OAuth2WebServer
 */
class GooglePlusComponent extends Component {

	/**
	 * HttpSocket
	 * @var HttpSocket 
	 */
	protected $HttpSocket;

	/**
	 * @var array
	 */
	public $components = array('Session');

	/**
	 * Session key prefix
	 * @var string
	 */
	protected $sessionKeyName = "gpc_";

	/**
	 * ApiBaseUrl
	 * @var string
	 */
	protected $apiBaseUrl = "https://www.googleapis.com";

	/**
	 * API call url
	 * @var string
	 */
	protected $apiUrl = 'https://www.googleapis.com/plus/v1';

	/**
	 * API map
	 * @var array 
	 */
	protected $apiMap = array(
		'plus' => '/plus/v1',
		'oauth2' => '/oauth2/v1',
	);

	/**
	 * AccessToken
	 * @var string
	 */
	protected $accessToken = null;

	public function initialize(Controller $controller) {
		parent::initialize($controller);
		// HTTP socketのインスタンスか
		$this->HttpSocket = new HttpSocket();
	}

	/**
	 * ログイン認証のためのURLを取得する
	 * @param type $scope
	 * @return string 
	 */
	public function getAuthUrl($state = null) {
		$scope = $this->settings['scope'];
		if (is_array($scope)) {
			foreach ($scope as $k => $v) {
				$scope[$k] = rawurlencode($v);
			}
			$scope = implode(' ', $scope);
		} else {
			$scope = rawurlencode($scope);
		}

		$redirectUrl = $this->settings['auth']['callback'];
		$redirectUrl = urlencode($redirectUrl);

		$url = "https://accounts.google.com/o/oauth2/auth?client_id={$this->settings['clientId']}";
		$url .= "&redirect_uri={$redirectUrl}";
		$url .= "&response_type=code";
		$url .= "&scope=" . $scope;

		if (!empty($state)) {
			$url .= "&state=" . $state;
		}

		return $url;
	}

	/**
	 * ログイン認証のためのリダイレクトを行う
	 * @param string $state 
	 */
	public function authRedirect($state = null) {
		$url = $this->getAuthUrl($state);
		$this->_Collection->getController()->redirect($url);
	}

	/**
	 * APIをコールするためのURL取得
	 * @param string $apiPath
	 * @return string 
	 */
	public function getApiUrl($type, $apiPath = null) {
		if (!isset($this->apiMap[$type])) {
			throw new RuntimeException('no api map type ' . $type);
		}
		return $this->apiBaseUrl . $this->apiMap[$type] . $apiPath;
	}

	/**
	 * AccessTokenを取得する
	 * @return boolean 
	 */
	public function getAccessToken() {

		if ($this->accessToken != null) {
			return $this->accessToken;
		}

		$code = $this->getCode();
		if ($code) {
			$url = "https://accounts.google.com/o/oauth2/token";
			$response = $this->HttpSocket->post($url, array(
				'code' => $code,
				'client_id' => $this->settings['clientId'],
				'client_secret' => $this->settings['clientSecret'],
				'redirect_uri' => $this->settings['auth']['callback'],
				'grant_type' => 'authorization_code',
					));

			$token = $response->body;
			if ($this->HttpSocket->response->code == 200) {
				$token = json_decode($token, true);
				$token['isLogin'] = true;
				$token['start_time'] = time();
				$this->setAccessToken($token);
				$this->accessToken = $token;
				return $token;
			}

			$this->Session->delete($this->getSessionKey('access_token'));
			return false;
		}

		$this->accessToken = $token = $this->Session->read($this->getSessionKey('access_token'));
		if (!$token) {
			return false;
		}

		// @todo リフレッシュトークンのチェック

		return $token;
	}

	/**
	 * AccessTokenをセットする
	 * @param array $token 
	 */
	public function setAccessToken($token) {
		$this->Session->write($this->getSessionKey('access_token'), $token);
	}

	/**
	 * アクセストークンを削除する
	 */
	public function deleteAccessToken() {
		$this->Session->delete($this->getSessionKey('access_token'));
	}

	/**
	 * codeを取得する
	 * @return string
	 */
	protected function getCode() {
		if (isset($_REQUEST['code'])) {
			return $_REQUEST['code'];
		}
		return false;
	}

	/**
	 * Sesssion用のキーを取得する
	 * @param string $suffix
	 * @return string
	 */
	protected function getSessionKey($suffix) {
		return $this->sessionKeyName . "session_" . $suffix;
	}

	/**
	 * ログインしているかどうかのフラグ
	 * @return boolean
	 */
	public function isLogin() {
		$token = $this->getAccessToken();
		if (!$token) {
			return false;
		}
		return true;
	}
	
	/**
	 * ユーザー情報を取得する
	 * @param boolean $reflash
	 * @return array 
	 */
	public function me($reflash = false) {
		// sessionから取得
		$reuslt = $this->Session->read($this->getSessionKey('me'));
		if ($reuslt && !$reflash) {
			return $reuslt;
		}

		$result = $this->callApi('plus', '/people/me');
		if ($result) {
			$this->Session->write($this->getSessionKey('me'), $result);
		}
		return $result;
	}

	/**
	 * ユーザー情報を取得する
	 * @return array
	 * @see http://stackoverflow.com/questions/8706288/using-google-api-for-php-need-to-get-the-users-email
	 */
	public function userInfo($isCache = true) {
		if ($isCache) {
			$cache = $this->Session->read($this->getSessionKey('userInfo') );
			if ($cache) {
				return $cache;
			}
		}
		$result = $this->callApi('oauth2', '/userinfo');
		if ($isCache && $result) {
			$this->Session->write($this->getSessionKey('userInfo'), $result);
		}
		return $result;
	}

	/**
	 * ログインをリセットする
	 * @return \GooglePlusComponent
	 */
	public function reset() {
		$this->deleteAccessToken();
		$this->Session->delete($this->getSessionKey('me'));
		return $this;
	}

	/**
	 * APIをコールする
	 * @param string $path API PATH
	 * @param array $params
	 * @param string $method get | post | put | delete
	 * @return boolean 
	 */
	protected function callApi($type, $path, $params = array(), $method = null) {
		if (empty($method)) {
			$method = 'get';
		}

		$url = $this->getApiUrl($type, $path);
		$token = $this->getAccessToken();
		if (!$token['isLogin']) {
			// @todo エラー処理
			return false;
		}



		$response = $this->HttpSocket->$method($url, $params, array(
			'header' => array('Authorization' => "OAuth " . $token['access_token']),
				));
		return json_decode($response->body, true);
	}

}