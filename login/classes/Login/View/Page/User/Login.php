<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Login extends Abstract_View_Page {

	public $errors = [];

	public $allow_registration, $enabled_providers;

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

	public function has_errors_excluding($error, $error_to_exclude)
	{
		return (bool) $error && $error != $error_to_exclude;
	}

	public function username()
	{
		$has_error = $this->has_errors_excluding(Arr::get($this->errors, 'username'),
			Kohana::message('User', 'username.jailed'));

		return [
			'name' => 'username',
			'id' => 'username',
			'value' => $this->username,
			'label' => Kohana::message('User', 'username_or_email.label'),
			'has_error' => $has_error,
			'error' => Arr::get($this->errors, 'username'),
		];
	}

	public function password()
	{
		$has_error = $this->has_errors_excluding(Arr::get($this->errors, 'password'),
			Kohana::message('User', 'password.invalid'));

		return [
			'name' => 'password',
			'id' => 'password',
			'label' => Kohana::message('User', 'password.label'),
			'has_error' => $has_error,
			'error' => Arr::get($this->errors, 'password'),
		];
	}

	public function remember()
	{
		return [
			'name' => 'remember',
			'id' => 'remember',
			'label' => Kohana::message('User', 'remember.label'),
		];
	}

	public function has_providers()
	{
		return ! empty($this->enabled_providers);
	}

	public function providers()
	{
		return Arr::map(
			function($provider)
			{
				return [
					'name'	=> $provider,
					'href'	=> URL::site('/user/provider/'.$provider),
				];
			},
			$this->enabled_providers
		);
	}

}
