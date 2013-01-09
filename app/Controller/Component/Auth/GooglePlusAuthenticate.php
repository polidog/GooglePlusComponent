<?php
App::uses('BaseAuthorize', 'Controller/Component/Auth');
/**
 * GooglePlus用のAuthenticateクラス
 * @author polidog <polidogs@gmail.com>
 */
class GooglePlusAuthenticate extends BaseAuthenticate
{

	/**
	 * ログイン認証
	 * @param CakeRequest $request
	 * @param CakeResponse $response
	 * @return boolean 
	 */
	public function authenticate(CakeRequest $request, CakeResponse $response) {
		$GooglePlus = $this->_Collection->load('GooglePlus');
		$token = $GooglePlus->getAccessToken();
		if (!$token) {
			return $GooglePlus->authRedirect();
		}
		else {
			return $GooglePlus->me();
		}
		return false;
	}
	
	
	/**
	 * ユーザー情報を取得する
	 * @param CakeRequest $request
	 * @return array|false
	 */
	public function getUser($request = null) {
		return $this->_Collection->load('GooglePlus')->me();
	}
	
	
	/**
	 * ログアウト処理
	 */
	public function logout( $user = null ) {
		$this->_Collection->load('GooglePlus')->reset();
	}
	
}