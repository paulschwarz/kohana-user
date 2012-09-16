<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Index extends Abstract_View_Page {

	public $errors = [];

	public $inactive_providers, $active_identities;

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

	public function has_identities()
	{
		return ! empty($this->active_identities);
	}

	public function identities()
	{
		$identities = [];

		foreach ($this->active_identities as $identity)
		{
			$identities[] = [
				'name' => $identity
			];
		};

		return $identities;
	}

	public function has_providers()
	{
		return ! empty($this->inactive_providers);
	}

	public function providers()
	{
		$providers = [];

		foreach ($this->inactive_providers as $provider)
		{
			$providers[] = [
				'name' => $provider,
				'href' => URL::site('/user/associate/'.$provider)
			];
		};

		return $providers;
	}

}
