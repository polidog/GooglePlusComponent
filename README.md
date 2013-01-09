cakePHP2系のgoogle plus api用のコンポーネント
==========

どちらかというとgoogle plusのoauthを使って認証したいなーと思って作りましたので 
google plusのapiのラッパーというには機能が少ないかと・・・  
ただ、既存のcakeのAuthComponent使ってgoogle plusのoauth使うには便利かと。

動作環境
------------
php5.3以上  
みなさん、php5.4使いましょうヽ(｀・ω・´)ﾉ ｳﾜｧｧﾝ!

動かし方
------------
コントローラのコンポーネントの設定の部分に以下のように記述してください。
用意いただくのはclientIdとclientSecretのみ！！！


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
		),
		'GooglePlus' => array(
			'clientId' => 'きー',
			'clientSecret' => 'しーくれっと',
			'auth' => array(
				'callback' => 'http://localhost/test/callback', // コールバック先を指定する
			),
			'scope' => array(
				'https://www.googleapis.com/auth/plus.me',
			),
		)
	);
	
もうあとはいたって、普通にcakeのAuthの認証になります。  
もちろん$this->Auth->user();とかやったら普通のgoogle plusのユーザーの情報が取得できますよー。


ちなみにAuthComponentのほうのloginRedirectは効かないので、GooglePlusのほうのauth callbackにログイン後にリダイレクトしたいページを指定してください。  
GooglePlusのほうのauth callbackのURLはgoogle plus側で指定するコールバックのURLとあわせるようにしてください。