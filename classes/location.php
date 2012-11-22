<?php
 
namespace Location;

class Location
{
	public static function find_country($country_code)
	{
		return \Model_Country::find()
			->where('code', \Str::lower($country_code))
			->get_one();
	}

	public static function find_state($country_code, $state_code)
	{
		return \Model_State::find()
			->where('country_code', \Str::lower($country_code))
			->where('code', \Str::lower($state_code))
			->get_one();
	}

	public static function find_city($country_code, $state_code, $city_name)
	{
		return \Model_City::find()
			->where('country_code', \Str::lower($country_code))
			->where('state_code', \Str::lower($state_code))
			->where('slug', \Inflector::friendly_title($city_name, '-', true))
			->get_one();
	}

	public static function find_city_by_ip($ip = null)
	{
		$geo = \Geolocate::forge($ip);
		
		if (!$geo) {
			return null;
		}
		
		return \Model_City::find()
			->where('country_code', \Str::lower($geo->country_code))
			->where('state_code', \Str::lower($geo->region))
			->where('name', $geo->city)
			->get_one();
	}

}
