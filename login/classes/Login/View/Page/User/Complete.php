<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Complete extends Abstract_View_Page {

	public $email;

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

}
