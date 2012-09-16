<?php defined('SYSPATH') or die('No direct access allowed.');

Route::set('user/providers', 'user/<action>(/<provider>)', [
		'action'     => 'provider|provider_return|associate|associate_return',
		'provider'   => '.+',
	])->defaults([
		'controller' => 'user',
		'provider'   => NULL,
	]);

Repositories::add([
	'users',
	'roles',
	'user_tokens',
	'user_identities'
]);
