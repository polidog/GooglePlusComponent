<?php
/**
 * 認証用コントローラ 
 */
class AuthController extends AppController
{
	public $components = array(
		'Auth' => array(
			'authenticate' => array(
				'GooglePlus',
			),
			'authorize' => array(
				'GooglePlus',
			),
			'loginAction' => array(
				'controller' => 'auth', 
				'action' => 'login'
			),
			'loginRedirect' => array(
				'controller' => 'pages',
				'action' => 'index'
			),
		),
		'GooglePlus' => array(
			'clientId' => 'クライアントID',
			'clientSecret' => 'シークレット',
			'auth' => array(
				'callback' => 'http://localhost/test/callback',
			),
			'scope' => array(
				'https://www.googleapis.com/auth/plus.me',
			),
		)
	);
	
	public function beforeFilter() {
		parent::beforeFilter();
	}
	
	public function index() {
		// 認証しているかどうかチェックする
		debug($this->Auth->isAuthorized());
	}
	
	public function login() {
	    // ログイン処理
		$this->Auth->login();
	}
	
	public function logout() {
	    // ログアウト処理
		$this->Auth->logout();
		$this->redirect('/');
	}
}