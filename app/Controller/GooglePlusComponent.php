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
class GooglePlusComponent extends Component
{
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
	 * API call url
	 * @var string
	 */
	protected $apiUrl = 'https://www.googleapis.com/plus/v1';

	
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
		if ( is_array($scope) ) {
			$scope = implode(',', $scope);
		}
		
		$redirectUrl = $this->settings['auth']['callback'];
		$scope = urlencode($scope);
		$redirectUrl = urlencode($redirectUrl);
		
		$url = "https://accounts.google.com/o/oauth2/auth?client_id={$this->settings['clientId']}";
		$url .= "&redirect_uri={$redirectUrl}";
		$url .= "&response_type=code";
		$url .= "&scope=".$scope;
		
		if ( !empty($state) ) {
			$url .= "&state=".$state;
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
	public function getApiUrl($apiPath = null) {
		return $this->apiUrl.$apiPath;
	}
 	
	
	/**
	 * AccessTokenを取得する
	 * @return boolean 
	 */
	public function getAccessToken() {
		
		$code = $this->getCode();
		if ( $code ) {
			$url = "https://accounts.google.com/o/oauth2/token";
			$token = $this->HttpSocket->post($url, array(
				'code'			=> $code,
				'client_id' => $this->settings['clientId'],
				'client_secret'	=> $this->settings['clientSecret'],
				'redirect_uri'	=> $this->settings['auth']['callback'],
				'grant_type'	=> 'authorization_code',
			));
			
			if ( $this->HttpSocket->response->code == 200 ) {
				$token = json_decode($token,true);
				$token['isLogin'] = true;
				$token['start_time'] = time();
				$this->setAccessToken($token);
				return $token;
			}
			
			$this->Session->delete($this->getSessionKey('access_token'));
			return false;
			
		}
		
		$token = $this->Session->read($this->getSessionKey('access_token'));
		if ( !$token ) {
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
		$this->Session->write($this->getSessionKey('access_token'),$token);
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
		if ( isset($_REQUEST['code']) ) {
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
		return $this->sessionKeyName."session_".$suffix;
	}
	
	
	/**
	 * ユーザー情報を取得する
	 * @param boolean $reflash
	 * @return array 
	 */
	public function me($reflash = false) {
		// sessionから取得
		$reuslt = $this->Session->read($this->getSessionKey('me'));
		if ( $reuslt && !$reflash ) {
			return $reuslt;
		}
		
		$result = $this->callApi('/people/me');
		if ( $result ) {
			$this->Session->write($this->getSessionKey('me'),$result);
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
	 * @param type $path
	 * @param type $params
	 * @param string $method
	 * @param type $tokenType
	 * @return boolean 
	 */
	protected function callApi( $path, $params = array(), $method = null, $tokenType = null ) {
		if ( empty( $method ) ) {
			$method = 'get';
		}

		
		$url = $this->getApiUrl($path);
		$token = $this->getAccessToken();
		if ( !$token['isLogin'] ) {
			// @todo エラー処理
			return false;
		}
		
		
		
		$json = $this->HttpSocket->$method($url, $params, array(
			'header' => array('Authorization' => "OAuth ".$token['access_token']),	
		));
		return json_decode($json,true);
	}
}