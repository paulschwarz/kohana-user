<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Register extends Abstract_View_Page {

	public $username, $email;

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
			'value' => $this->username,
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
			'value' => $this->email,
			'id' => 'email',
			'label' => Kohana::message('User', 'email.label'),
			'has_error' => Arr::get($this->errors, 'email'),
			'error' => Arr::get($this->errors, 'email'),
		];
	}

	public function password()
	{
		return [
			'name' => 'password',
			'id' => 'password',
			'label' => Kohana::message('User', 'password.label'),
			'has_error' => Arr::get($this->errors, 'password'),
			'error' => Arr::get($this->errors, 'password'),
		];
	}

	public function confirm()
	{
		return [
			'name' => 'confirm',
			'id' => 'confirm',
			'label' => Kohana::message('User', 'confirm.label'),
			'has_error' => Arr::get($this->errors, 'confirm'),
			'error' => Arr::get($this->errors, 'confirm'),
		];
	}

}
