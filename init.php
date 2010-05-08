<?php defined('SYSPATH') OR die('No direct script access.');

Route::set('comments', 'comments/<group>/<action>(/<id>(/<page>))(<format>)', array(
		'id'     => '\d+',
		'page'   => '\d+',
		'format' => '\.\w+',
	))->defaults(array(
		'controller' => 'comments',
		'group'      => 'default',
		'format'     => '.json',
	));

