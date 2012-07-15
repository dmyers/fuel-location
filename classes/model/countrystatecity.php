<?php

namespace Cities;

class Model_CountryStateCity extends \Orm\Model
{
	protected static $_table_name = 'country_state_cities';

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
		'state' => array(
			'model_to' => 'Model_CountryState',
			'key_from' => 'state_id',
			'key_to'   => 'id',
		),
	);

	public static function find_using_slug($country_code, $state_code, $city_name)
	{
		return self::find()
			->where('country_code', strtolower($country_code))
			->where('state_code', strtolower($state_code))
			->where('slug', $city_name)
			->get_one();
	}

	public static function find_using_ip($ip = null)
	{
		$geo = \Geolocate::forge($ip ? : \Input::real_ip());
		
		if (!$geo) {
			return null;
		}
		
		return self::find()
			->where('country_code', strtolower($geo->country_code))
			->where('state_code', strtolower($geo->region))
			->where('name', $geo->city)
			->get_one();
	}

	public function getLink()
	{
		$data = array();
		$data[] = '';
		$data[] = $this->state ? $this->state->code : false;
		$data[] = $this->slug;
		
		return implode('/', $data);
	}
	
	public function getDisplayName()
	{
		return \Inflector::humanize($this->name);
	}
}