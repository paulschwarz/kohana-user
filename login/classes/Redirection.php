<?php defined('SYSPATH') or die('No direct script access.');

class Redirection {

	public function redirect($uri = '', $code = 302)
	{
		HTTP::redirect($uri, $code);
	}

}
