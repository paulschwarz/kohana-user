<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Edit extends Abstract_View_Page {

	/**
	 * @var Model_User
	 */
	public $user;
	public $errors = [];

	public function has_errors()
	{
		return (bool) $this->errors();
	}

	public function errors()
	{
		$errors = [];
		foreach ($this->errors as $key => $error)
		{
			$errors[] = array('key' => $key, 'error' => $error);
		}

		return $errors;
	}

	public function username()
	{
		return [
			'name' => 'username',
			'value' => Arr::get($this->user, 'username'),
			'id' => 'username',
			'label' => Kohana::message('User', 'username.label'),
			'has_error' => Arr::get($this->errors, 'username'),
			'error' => Arr::get($this->errors, 'username'),
		];
	}

	public function email()
	{
		return [
			'name' => 'email',
			'value' => Arr::get($this->user, 'email'),
			'id' => 'email',
			'label' => Kohana::message('User', 'email.label'),
			'has_error' => Arr::get($this->errors, 'email'),
			'error' => Arr::get($this->errors, 'email'),
		];
	}

}
