<?php
 
namespace Location;

class Location
{
	protected static $active;

	public static function active()
	{
		if (self::$active !== null) {
			return self::$active;
		}
		
		$city_id = \Session::get('location');

		if ($city_id === false) {
			return false;
		}
		
		if ($city_id) {
			$city = \Model_City::find($city_id);

			if ($city) {
				self::$active = $city;
				
				return $city;
			}
		} else {
			$location = self::find_city_by_ip();
		
			if (!$location) {
				return false;
			}
			
			\Session::set('location', $location->id);

			self::$active = $location;

			return $location;
		}

		\Session::set('location', false);
		
		return false;
	}

	public static function set_active(\Model_City $city)
	{
		\Session::set('location', $city->id);

		self::$active = $city;
	}
	
	public static function find_country($country_code)
	{
		return \Model_Country::query()
			->where('code', \Str::lower($country_code))
			->get_one();
	}

	public static function find_state($country_code, $state_code)
	{
		return \Model_State::query()
			->where('country_code', \Str::lower($country_code))
			->where('code', \Str::lower($state_code))
			->get_one();
	}

	public static function find_city($country_code, $state_code, $city_name)
	{
		return \Model_City::query()
			->where('country_code', \Str::lower($country_code))
			->where('state_code', \Str::lower($state_code))
			->where('slug', \Inflector::friendly_title($city_name, '-', true))
			->get_one();
	}

	public static function find_city_by_ip($ip_address = null)
	{
		$geo = \Geolocate::forge($ip_address);
		
		if (!$geo) {
			$ip_address = empty($ip_address) ? \Input::real_ip() : $ip_address;
			$ip_address = \Config::get('geolocate.fake_ip', $ip_address);
			\Log::error(sprintf('Unable to find location by ip (%s)', $ip_address));
			return null;
		}
		
		return \Model_City::query()
			->where('country_code', \Str::lower($geo->country_code))
			->where('state_code', \Str::lower($geo->region))
			->where('name', $geo->city)
			->get_one();
	}

}
