<?php defined('SYSPATH') OR die('No direct access allowed.');

class Model_User_Token extends Model {

	public
		$id,
		$user_id,
		$user_agent,
		$token,
		$created,
		$expires;

	public function values($values)
	{
		if (is_array($values))
		{
			foreach($values as $key => $val)
			{
				$this->{$key} = $val;
			}
			return $this;
		}
	}

}
