<?php
 
namespace Location;

class Location
{
	public static function find_country($country_code)
	{
		return \Model_Country::find()
			->where('code', strtolower($country_code))
			->get_one();
	}

	public static function find_state($country_code, $state_code)
	{
		return \Model_State::find()
			->where('country_code', strtolower($country_code))
			->where('code', strtolower($state_code))
			->get_one();
	}

	public static function find_city($country_code, $state_code, $city_name)
	{
		return \Model_City::find()
			->where('country_code', strtolower($country_code))
			->where('state_code', strtolower($state_code))
			->where('slug', $city_name)
			->get_one();
	}

	public static function find_city_by_ip($ip = null)
	{
		$geo = \Geolocate::forge($ip ? : \Input::real_ip());
		
		if (!$geo) {
			return null;
		}
		
		return \Model_City::find()
			->where('country_code', strtolower($geo->country_code))
			->where('state_code', strtolower($geo->region))
			->where('name', $geo->city)
			->get_one();
	}

}
