<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Password extends Abstract_View_Page {

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
