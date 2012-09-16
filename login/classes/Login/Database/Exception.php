<?php defined('SYSPATH') OR die('No direct script access.');

class Login_Database_Exception extends Kohana_Database_Exception {

	const DUPLICATE_ENTRY = 1062;

	public function is_duplicate_entry()
	{
		return $this->getCode() === self::DUPLICATE_ENTRY;
	}

}
