<?php

namespace Location;

class Model_City extends \Orm\Model
{
	protected static $_table_name = 'location_cities';

	protected static $_properties = array(
		'id',
		'country_code',
		'country_id',
		'state_code',
		'state_id',
		'name',
		'slug',
	);

	protected static $_has_one = array(
		'country' => array(
			'model_to' => 'Model_Country',
			'key_from' => 'country_id',
			'key_to'   => 'id',
		),
		'state' => array(
			'model_to' => 'Model_State',
			'key_from' => 'state_id',
			'key_to'   => 'id',
		),
	);

	public function getLink()
	{
		$data = array();
		$data[] = '';
		$data[] = $this->state ? \Str::lower($this->state->code) : false;
		$data[] = $this->slug;
		
		return implode('/', $data);
	}
	
	public function getDisplayName()
	{
		return \Inflector::humanize($this->name);
	}
}