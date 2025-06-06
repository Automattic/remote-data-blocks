<?php declare(strict_types = 1);

namespace RemoteDataBlocks\Example\Weather;

use RemoteDataBlocks\Config\Query\HttpQuery;

defined( 'ABSPATH' ) || exit();

/**
 * Convert weather code to human-readable description
 * Based on OpenMeteo weather codes: https://open-meteo.com/en/docs
 */
function get_weather_description( int $code ): string {
	$weather_codes = [
		0 => 'Clear sky',
		1 => 'Mainly clear',
		2 => 'Partly cloudy',
		3 => 'Overcast',
		45 => 'Fog',
		48 => 'Depositing rime fog',
		51 => 'Light drizzle',
		53 => 'Moderate drizzle',
		55 => 'Dense drizzle',
		56 => 'Light freezing drizzle',
		57 => 'Dense freezing drizzle',
		61 => 'Slight rain',
		63 => 'Moderate rain',
		65 => 'Heavy rain',
		66 => 'Light freezing rain',
		67 => 'Heavy freezing rain',
		71 => 'Slight snow fall',
		73 => 'Moderate snow fall',
		75 => 'Heavy snow fall',
		77 => 'Snow grains',
		80 => 'Slight rain showers',
		81 => 'Moderate rain showers',
		82 => 'Violent rain showers',
		85 => 'Slight snow showers',
		86 => 'Heavy snow showers',
		95 => 'Thunderstorm',
		96 => 'Thunderstorm with slight hail',
		99 => 'Thunderstorm with heavy hail',
	];

	return $weather_codes[ $code ] ?? 'Unknown';
}

/**
 * Generate rain prediction based on precipitation probability
 */
function generate_rain_prediction( int $probability ): string {
	if ( $probability >= 80 ) {
		return 'It definitely looks like rain today!';
	} elseif ( $probability >= 20 ) {
		return 'It might rain today.';
	} else {
		return 'Rain is unlikely today.';
	}
}

/**
 * Registers a remote data block for fetching weather data from the OpenMeteo API.
 * This block accepts a city name as input and returns current weather information
 * including temperature, humidity, weather description, and rain prediction.
 *
 * @see https://open-meteo.com/en/docs
 */
function register_weather_remote_data_block(): void {
	$openmeteo_data_source = [
		'display_name' => 'OpenMeteo Weather API',
		'endpoint' => 'https://api.open-meteo.com/v1/',
		'request_headers' => [
			'Content-Type' => 'application/json',
		],
	];

	$get_geo_data_from_city_query = [
		'data_source' => $openmeteo_data_source,
		'display_name' => 'Get latitude and longitude from city name',
		'endpoint' => function ( array $input_variables ): string {
			return add_query_arg( [
				'name' => $input_variables['city'],
				'count' => 1,
				'language' => 'en',
				'format' => 'json',
			], 'https://geocoding-api.open-meteo.com/v1/search' );
		},
		'input_schema' => [
			'city' => [
				'name' => 'City Name',
				'type' => 'string',
				'required' => true,
			],
		],
		'output_schema' => [
			'is_collection' => false,
			'path' => '$.results[0]',
			'type' => [
				'country' => [
					'name' => 'Country',
					'path' => '$.country',
					'type' => 'string',
				],
				'lat' => [
					'name' => 'Latitude',
					'path' => '$.latitude',
					'type' => 'number',
				],
				'long' => [
					'name' => 'Longitude',
					'path' => '$.longitude',
					'type' => 'number',
				],
				'name' => [
					'name' => 'Name',
					'path' => '$.name',
					'type' => 'string',
				],
			],
		],
	];

	$get_weather_query = [
		'data_source' => $openmeteo_data_source,
		'display_name' => 'Get weather by city name',
		'endpoint' => function ( array $input_variables ) use ( $openmeteo_data_source, $get_geo_data_from_city_query ): string {
			// Get latitude and longitude from the city name by executing a dependent
			// query. This approach can avoid the need for a custom query runner or
			// other complicated configuration.
			//
			// Using `HttpQuery` allows us to benefit from the caching layer, which is
			// important since this code runs on every request before the object cache
			// is checked.
			$geo_data_query = HttpQuery::from_array( $get_geo_data_from_city_query );
			$geo_data = $geo_data_query->execute( [ 'city' => $input_variables['city'] ] );

			$latitude = $geo_data['results'][0]['result']['lat']['value'] ?? 'invalid';
			$longitude = $geo_data['results'][0]['result']['long']['value'] ?? 'invalid';

			// Construct and return weather API URL
			return add_query_arg( [
				'latitude' => $latitude,
				'longitude' => $longitude,
				'current' => 'temperature_2m,relative_humidity_2m,weather_code,precipitation_probability',
				'timezone' => 'auto',
				'temperature_unit' => 'celsius',
			], $openmeteo_data_source['endpoint'] . 'forecast' );
		},
		'input_schema' => [
			'city' => [
				'name' => 'City Name',
				'type' => 'string',
				'required' => true,
			],
		],
		'output_schema' => [
			'is_collection' => false, // This query returns a single weather record
			'type' => [
				'location_name' => [
					'name' => 'Location',
					'type' => 'string',
					'generate' => function ( array $_data, array $response_data ): string {
						return $response_data['input_variables']['city'] ?? 'Unknown';
					},
				],
				'temperature_celsius' => [
					'name' => 'Temperature (°C)',
					'type' => 'number',
					'path' => '$.current.temperature_2m',
				],
				'temperature_fahrenheit' => [
					'name' => 'Temperature (°F)',
					'type' => 'number',
					'generate' => function ( array $data ): float {
						$temp_c = $data['current']['temperature_2m'] ?? 0;
						return round( ( $temp_c * 9 / 5 ) + 32, 1 );
					},
				],
				'weather_description' => [
					'name' => 'Weather Description',
					'type' => 'string',
					'generate' => function ( array $data ): string {
						$weather_code = $data['current']['weather_code'] ?? 0;
						return get_weather_description( (int) $weather_code );
					},
				],
				'humidity' => [
					'name' => 'Humidity (%)',
					'type' => 'integer',
					'path' => '$.current.relative_humidity_2m',
				],
				'precipitation_probability' => [
					'name' => 'Precipitation Probability (%)',
					'type' => 'integer',
					'path' => '$.current.precipitation_probability',
				],
				'rain_prediction' => [
					'name' => 'Rain Prediction',
					'type' => 'string',
					'generate' => function ( array $data ): string {
						$probability = $data['current']['precipitation_probability'] ?? 0;
						return generate_rain_prediction( (int) $probability );
					},
				],
			],
		],
	];

	register_remote_data_block( [
		'title' => 'Weather',
		'icon' => 'cloud',
		'render_query' => [
			'query' => $get_weather_query,
		],
		// Supply a pattern for the block that will be used to display the weather
		// data. This takes the place of the default pattern provided by the plugin.
		'patterns' => [
			[
				'title' => 'Weather for city',
				'html' => file_get_contents( __DIR__ . '/patterns/weather-block-pattern.html' ),
				'role' => 'inner_blocks', // Bypass the pattern selection step.
			],
		],
	] );
}
add_action( 'init', __NAMESPACE__ . '\\register_weather_remote_data_block' );
