<?php
namespace Fuel\Tasks;

class Cities
{
	public static function run()
	{
		self::countries();
		self::states();
		self::cities();
		\Cli::write('Done!', 'green');
	}

	public static function countries()
	{
		\Cli::write('Starting countries download', 'green');

		$request = \Request::forge('http://www.maxmind.com/app/iso3166', array('driver' => 'curl'))->execute();
		$response = $request->response();

		if ($response->status == 404) {
			\Cli::write('Failed to load page', 'red');
			return;
		}

		$pos = strpos($response, '<pre>');

		if ($pos === false) {
			\Cli::write('Cannot find html tag "pre" in response', 'red');
			return;
		}

		$response = substr($response, $pos + 5);
		$response = substr($response, 0, strpos($response, '</pre>'));
		$response = trim($response);
		
		$lines = explode("\n", $response);

		$bad = array('a1', 'a2', 'o1');
		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;

			$line = trim($line);
			$params = str_getcsv($line);
			
			$code = strtolower($params[0]);
			$name = str_replace('"', '', $params[1]);

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
			
			\DB::insert('countries')
				->set($country)
				->execute();
		}
	}

	public static function states()
	{
		\Cli::write('Starting country states download', 'green');

		$request = \Request::forge('http://www.maxmind.com/app/fips10_4', array('driver' => 'curl'))->execute();
		$response = $request->response();

		if ($response->status == 404) {
			\Cli::write('Failed to load page', 'red');
			return;
		}

		$pos = strpos($response, '<pre>');

		if ($pos === false) {
			\Cli::write('Cannot find html tag "pre" in response', 'red');
			return;
		}

		$response = substr($response, $pos + 5);
		$response = substr($response, 0, strpos($response, '</pre>'));
		$response = trim($response);
		
		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;

			if ($i == 1) {
				\Cli::write('Skipping first line', 'red');
				continue;
			}
			
			$line = trim($line);
			$params = str_getcsv($line);
			
			$country_code = strtolower($params[0]);
			$state_code = $params[1];
			$name = str_replace('"', '', $params[2]);

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			$country = \DB::select('id')
				->from('countries')
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
			
			\DB::insert('states')
				->set($state)
				->execute();
		}

		$request = \Request::forge('http://www.maxmind.com/app/iso3166_2', array('driver' => 'curl'))->execute();
		$response = $request->response();

		if ($response->status == 404) {
			\Cli::write('Failed to load page', 'red');
			return;
		}

		$pos = strpos($response, '<pre>');

		if ($pos === false) {
			\Cli::write('Cannot find html tag "pre" in response', 'red');
			return;
		}

		$response = substr($response, $pos + 5);
		$response = substr($response, 0, strpos($response, '</pre>'));
		$response = trim($response);
		
		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;

			if ($i == 1) {
				\Cli::write('Skipping first line', 'red');
				continue;
			}
			
			$line = trim($line);
			$params = str_getcsv($line);
			
			$country_code = strtolower($params[0]);
			$state_code = strtolower($params[1]);
			$name = str_replace('"', '', $params[2]);

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			$country = \DB::select('id')
				->from('countries')
				->where('code', $country_code)
				->limit(1)
				->execute();

			$country = $country[0];

			if (!$country) {
				\Cli::write(sprintf('Country not found for code (%s)', $country_code), 'red');
				continue;
			}

			\Cli::write(sprintf('Updating %s (%s)', $name, $state_code), 'green');
			
			\DB::update('states')
				->where('country_code', $country_code)
				->where('name', $name)
				->value('code', $state_code)
				->execute();
		}
	}

	public static function cities()
	{
		\Cli::write('Starting country state cities download', 'green');

		$request = \Request::forge('http://www.maxmind.com/GeoIPCity-534-Location.csv', array('driver' => 'curl'))->execute();
		$response = $request->response();

		if ($response->status == 404) {
			\Cli::write('Failed to load page', 'red');
			return;
		}
		
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
			$country_code = strtolower(str_replace('"', '', $params[1]));
			$state_code = strtolower(str_replace('"', '', $params[2]));
			
			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			if (in_array($country_code, $bad)) {
				\Cli::write(sprintf('Skipping bad country code %s', $country_code), 'red');
			}

			if (empty($name) || empty($country_code) || empty($state_code)) {
				\Cli::write(sprintf('Missing name,country_code,state_code (%s, %s, %s)', $name, $country_code, $state_code), 'red');
				continue;
			}
			
			$country = \DB::select('id')
				->from('countries')
				->where('code', $country_code)
				->limit(1)
				->execute();

			$country = $country[0];

			if (!$country) {
				\Cli::write(sprintf('Country not found for code (%s)', $country_code), 'red');
				continue;
			}

			$state = \DB::select('id')
				->from('states')
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
				->from('cities')
				->where('country_code', $country_code)
				->where('state_code', $state_code)
				->where('slug', $slug)
				->limit(1)
				->execute();

			$city = $city[0];

			if ($city) {
				\Cli::write(sprintf('Already added %s (%s)', $name, $country_code), 'red');
				continue;
			}

			\Cli::write(sprintf('Adding %s (%s)', $name, $country_code), 'green');

			$city = array(
				'id'           => $city_id,
				'country_code' => $country_code,
				'country_id'   => $country['id'],
				'state_code'   => $state_code,
				'state_id'     => $state['id'],
				'name'         => $name,
				'slug'         => $slug,
			);
			
			\DB::insert('cities')
				->set($city)
				->execute();
		}
	}

	public static function states_geonames()
	{
		\Cli::write('Starting country states download', 'green');

		$request = \Request::forge('http://download.geonames.org/export/dump/admin1CodesASCII.txt', array('driver' => 'curl'))->execute();
		$response = $request->response();

		if ($response->status == 404) {
			\Cli::write('Failed to load page', 'red');
			return;
		}

		$response = trim($response);
		
		$lines = explode("\n", $response);

		$total = count($lines);
		$i = 0;

		foreach ($lines as $key => $line) {
			$i++;
			
			$line = trim($line);
			$params = explode("\t", $line);

			$info = explode('.', $params[0]);
			$country_code = strtolower($info[0]);
			$state_code = strtolower($info[1]);
			$name = trim($params[1]);

			\Cli::write(sprintf('Processing %d of %d - %s', $i, $total, $name));

			$country = \DB::select('id')
				->from('countries')
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
			
			\DB::insert('states')
				->set($state)
				->execute();
		}
	}
}
