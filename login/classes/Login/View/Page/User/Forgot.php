<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Forgot extends Abstract_View_Page {

	public $errors = [];

	public function email()
	{
		return [
			'name' => 'email',
			'id' => 'email',
			'label' => Kohana::message('User', 'email.label'),
			'has_error' => Arr::get($this->errors, 'email'),
			'error' => Arr::get($this->errors, 'email'),
		];
	}

}
