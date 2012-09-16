<?php defined('SYSPATH') or die('No direct script access.');

class Login_View_Page_User_Associate extends Abstract_View_Page {

	public $provider_name;

	public function provider_title()
	{
		return ucfirst($this->provider_name);
	}

}
