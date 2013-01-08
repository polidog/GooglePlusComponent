<?php
App::uses('BaseAuthorize', 'Controller/Component/Auth');
/**
 * GooglePlusのOAuth認証用のAuthorizeクラス
 * @author polidog <polidogs@gmail.com>
 */
class GooglePlusAuthorize extends BaseAuthorize
{
	public function authorize($user, CakeRequest $request ) {
		if ( !empty($user) ) {
			return true;
		}
		return false;
	}
}
