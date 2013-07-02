<?php
namespace Fuel\Tasks;

class Location
{
	public static function run($provider = 'maxmind')
	{
		self::countries($provider);
		self::states($provider);
		self::cities($provider);
		
		\Cli::write('Done!', 'green');
	}
	
	public static function countries($provider = 'maxmind')
	{
		\Cli::write('Starting countries download', 'green');
		
		switch ($provider) {
			case 'maxmind':
				self::countries_maxmind();
				break;
			case 'geonames':
				self::countries_geonames();
				break;
			default:
				\Cli::error('Unknown provider given');
				break;
		}
	}
	
	public static function states($provider = 'maxmind')
	{
		\Cli::write('Starting country states download', 'green');
		
		switch ($provider) {
			case 'maxmind':
				self::states_maxmind();
				break;
			case 'geonames':
				self::states_geonames();
				break;
			default:
				\Cli::error('Unknown provider given');
				break;
		}
	}
	
	public static function cities($provider = 'maxmind')
	{
		\Cli::write('Starting country state cities download', 'green');
		
		switch ($provider) {
			case 'maxmind':
				self::cities_maxmind();
				break;
			case 'geonames':
				self::cities_geonames();
				break;
			default:
				\Cli::error('Unknown provider given');
				break;
		}
	}

	public static function countries_maxmind()
	{
		$response = self::request('http://dev.maxmind.com/static/csv/codes/iso3166.csv');
		
		$response = trim($response);
		
		$lines = explode("\n", $response);

		$bad = array('a1', 'a2', 'o1');
		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;

			$line = trim($line);
			$params = str_getcsv($line);
			
			$code = \Str::lower($params[0]);
			$name = str_replace('"', '', $params[1]);
			
			if (empty($name) || empty($code)) {
				\Cli::error(sprintf('Missing name,code (%s, %s)', $name, $code));
				continue;
			}

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $code));

			if (in_array($code, $bad)) {
				\Cli::write(sprintf('Skipping bad country code %s', $code), 'red');
				continue;
			}

			\Cli::write(sprintf('Adding %s (%s)', $name, $code), 'green');

			$country = array(
				'code' => $code,
				'name' => $name,
			);
			
			\DB::insert('location_countries')
				->set($country)
				->execute();
		}
	}

	public static function states_maxmind()
	{
		$response = self::request('http://dev.maxmind.com/static/maxmind-region-codes.csv');
		
		$response = trim($response);
		
		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;
			
			$line = trim($line);
			$params = str_getcsv($line);
			
			$country_code = \Str::lower($params[0]);
			$state_code = \Str::lower($params[1]);
			$name = str_replace('"', '', $params[2]);
			
			if (empty($name) || empty($country_code) || empty($state_code)) {
				\Cli::error(sprintf('Missing name,country_code,state_code (%s, %s, %s)', $name, $country_code, $state_code));
				continue;
			}

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			$country = \DB::select('id')
				->from('location_countries')
				->where('code', $country_code)
				->limit(1)
				->execute();

			$country = $country[0];

			if (!$country) {
				\Cli::write(sprintf('Country not found for code (%s)', $country_code), 'red');
				continue;
			}

			\Cli::write(sprintf('Adding %s (%s)', $name, $state_code), 'green');

			$state = array(
				'code'         => $state_code,
				'country_code' => $country_code,
				'country_id'   => $country['id'],
				'name'         => $name,
			);
			
			\DB::insert('location_states')
				->set($state)
				->execute();
		}
	}

	public static function cities_maxmind()
	{
		$response = self::request('http://www.maxmind.com/GeoIPCity-534-Location.csv');
		
		$response = trim($response);

		$lines = explode("\n", $response);

		$bad = array('a1', 'a2', 'o1');
		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;

			if ($i == 1 || $i == 2) {
				continue;
			}
			
			$line = trim($line);
			$params = str_getcsv($line);
			
			$city_id = $params[0];
			
			\Cli::write(sprintf('Loaded city_id (%s)', $city_id), 'green');
			
			$name = str_replace('"', '', utf8_encode($params[3]));
			$country_code = \Str::lower(str_replace('"', '', $params[1]));
			$state_code = \Str::lower(str_replace('"', '', $params[2]));
			
			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			if (in_array($country_code, $bad)) {
				\Cli::write(sprintf('Skipping bad country code %s', $country_code), 'red');
			}

			if (empty($name) || empty($country_code) || empty($state_code)) {
				\Cli::write(sprintf('Missing name,country_code,state_code (%s, %s, %s)', $name, $country_code, $state_code), 'red');
				continue;
			}
			
			$country = \DB::select('id')
				->from('location_countries')
				->where('code', $country_code)
				->limit(1)
				->execute();

			$country = $country[0];

			if (!$country) {
				\Cli::write(sprintf('Country not found for code (%s)', $country_code), 'red');
				continue;
			}

			$state = \DB::select('id')
				->from('location_states')
				->where('country_code', $country_code)
				->where('code', $state_code)
				->limit(1)
				->execute();

			$state = $state[0];

			if (!$state) {
				\Cli::write(sprintf('State not found for state,country code (%s, %s)', $state_code, $country_code), 'red');
				continue;
			}

			$slug = \Inflector::friendly_title($name, '-', true);

			$city = \DB::select('id')
				->from('location_cities')
				->where('country_code', $country_code)
				->where('state_code', $state_code)
				->where('slug', $slug)
				->limit(1)
				->execute();

			$city = $city[0];

			if ($city) {
				\Cli::write(sprintf('Already added %s (%s, %s)', $name, $state_code, $country_code), 'red');
				continue;
			}

			\Cli::write(sprintf('Adding %s (%s, %s)', $name, $state_code, $country_code), 'green');

			$city = array(
				'id'           => $city_id,
				'country_code' => $country_code,
				'country_id'   => $country['id'],
				'state_code'   => $state_code,
				'state_id'     => $state['id'],
				'name'         => $name,
				'slug'         => $slug,
			);
			
			\DB::insert('location_cities')
				->set($city)
				->execute();
		}
	}
	
	public static function countries_geonames()
	{
		$response = self::request('http://download.geonames.org/export/dump/countryInfo.txt');

		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;
			
			if (substr($line, 0, 1) == '#') {
				continue;
			}
			
			$params = explode("\t", $line);
			
			if (count($params) != 19) {
				continue;
			}
			
			list($iso, $iso3, $iso_numeric, $fips, $country, $capital, $area_in_sqkm, $population, $continent, $tld, $currency_code, $currency_name, $phone, $postal_code_format, $postal_code_regex, $languages, $geoname_id, $neighbors, $equivalentfipscode) = $params;
			
			$id = trim($geoname_id);
			$code = trim(\Str::lower($iso));
			$name = trim($country);
			
			if (empty($id) || empty($name) || empty($code)) {
				\Cli::error(sprintf('Missing id,name,code (%s, %s, %s)', $id, $name, $code));
				continue;
			}

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $code));

			\Cli::write(sprintf('Adding %s (%s)', $name, $code), 'green');

			$country = array(
				'id'   => $id,
				'code' => $code,
				'name' => $name,
			);
			
			\DB::insert('location_countries')
				->set($country)
				->execute();
		}
	}
	
	public static function states_geonames()
	{
		$response = self::request('http://download.geonames.org/export/dump/admin1CodesASCII.txt');

		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;
			
			$params = explode("\t", $line);
			
			if (count($params) != 4) {
				continue;
			}
			
			list($admin1_code, $admin1_name, $admin1_name_ascii, $geoname_id) = $params;
			
			$id = trim($geoname_id);
			$info = explode('.', $admin1_code);
			$country_code = trim(\Str::lower($info[0]));
			$state_code = trim(\Str::lower($info[1]));
			$name = trim($admin1_name);
			
			if (empty($id) || empty($name) || empty($country_code) || empty($state_code)) {
				\Cli::error(sprintf('Missing id,name,country_code,state_code (%s, %s, %s, %s)', $id, $name, $country_code, $state_code));
				continue;
			}

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			$country = \DB::select('id')
				->from('location_countries')
				->where('code', $country_code)
				->limit(1)
				->execute();

			$country = $country[0];

			if (!$country) {
				\Cli::write(sprintf('Country not found for code (%s)', $country_code), 'red');
				continue;
			}

			\Cli::write(sprintf('Adding %s (%s)', $name, $state_code), 'green');

			$state = array(
				'id'           => $id,
				'code'         => $state_code,
				'country_code' => $country_code,
				'country_id'   => $country['id'],
				'name'         => $name,
			);
			
			\DB::insert('location_states')
				->set($state)
				->execute();
		}
	}
	
	public static function cities_geonames()
	{
		$path =  APPPATH . 'tmp' . DS;
		$database_path = $path . 'cities5000';

		$command = "curl -s http://download.geonames.org/export/dump/cities5000.zip > $database_path.zip";

		exec($command);

		if (file_exists($database_path.'.zip')) {
			exec('unzip -d '.$path.' '.$database_path.'.zip');
		}
		
		if (!file_exists($database_path.'.txt')) {
			\Cli::write('Error downloading cities db', 'red');
			return;
		}
		
		$response = file_get_contents($database_path.'.txt');
		
		unlink($database_path.'.txt');
		
		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;
			
			$params = explode("\t", $line);
			
			if (count($params) != 19) {
				continue;
			}
			
			list($geoname_id, $name, $asciiname, $alternate_names, $latitude, $longitude, $feature_class, $feature_code, $country_code, $cc2, $admin1_code, $admin2_code, $admin3_code, $admin4_code, $population, $elevation, $gtopo30, $timezone, $modification_date) = $params;
			
			$id = trim($geoname_id);
			$country_code = trim(\Str::lower($country_code));
			$state_code = trim(\Str::lower($admin1_code));
			$name = trim($name);

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			if (empty($id) || empty($name) || empty($country_code) || empty($state_code)) {
				\Cli::write(sprintf('Missing id,name,country_code,state_code (%s, %s, %s, %s)', $id, $name, $country_code, $state_code), 'red');
				continue;
			}
			
			$country = \DB::select('id')
				->from('location_countries')
				->where('code', $country_code)
				->limit(1)
				->execute();

			$country = $country[0];

			if (!$country) {
				\Cli::write(sprintf('Country not found for code (%s)', $country_code), 'red');
				continue;
			}

			$state = \DB::select('id')
				->from('location_states')
				->where('country_code', $country_code)
				->where('code', $state_code)
				->limit(1)
				->execute();

			$state = $state[0];

			if (!$state) {
				\Cli::write(sprintf('State not found for state,country code (%s, %s)', $state_code, $country_code), 'red');
				continue;
			}

			$slug = \Inflector::friendly_title($name, '-', true);

			$city = \DB::select('id')
				->from('location_cities')
				->where('country_code', $country_code)
				->where('state_code', $state_code)
				->where('slug', $slug)
				->limit(1)
				->execute();

			$city = $city[0];

			if ($city) {
				\Cli::write(sprintf('Already added %s (%s, %s)', $name, $state_code, $country_code), 'red');
				continue;
			}

			\Cli::write(sprintf('Adding %s (%s, %s)', $name, $state_code, $country_code), 'green');

			$city = array(
				'id'           => $geoname_id,
				'country_code' => $country_code,
				'country_id'   => $country['id'],
				'state_code'   => $state_code,
				'state_id'     => $state['id'],
				'name'         => $name,
				'slug'         => $slug,
			);
			
			\DB::insert('location_cities')
				->set($city)
				->execute();
		}
	}

	protected static function request($url)
	{
		$request = \Request::forge($url, 'curl');
		$request->set_auto_format(false);
		$request->set_options(array(
			'timeout' => 60,
		));

		try {
			$request->execute();
		} catch (\RequestException $e) {
			\Cli::error("Failed to load url ($url)");
			return;
		}

		$response = $request->response();

		return $response;
	}
}
