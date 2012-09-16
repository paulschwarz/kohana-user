<?php defined('SYSPATH') or die('No direct script access.');

class Login_Message {

	public static function add($type, $message)
	{
		// Get session messages array.
		$messages = Session::instance()->get('messages', []);

		// Append to messages array.
		$messages[$type][] = $message;

		// Set session messages array.
		Session::instance()->set('messages', $messages);
	}

	public static function count()
	{
		return count(Session::instance()->get('messages'));
	}

	public static function output()
	{
		$messages = Session::instance()->get_once('messages', []);

		$buffer = '';

		foreach ($messages as $type => $messages)
		{
			foreach ($messages as $message)
			{
				$buffer .= '<div class="' . $type . '">' . $message . '</div>';
			}
		}

		return $buffer;
	}

}
