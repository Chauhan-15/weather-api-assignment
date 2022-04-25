<?php

namespace App\Services;

use Throwable;
use Exception;

class WeatherApiService
{
    private $client;

    function __construct()
    {
        $this->client = new \GuzzleHttp\Client();
    }

    function getWeatherData($detail)
    {
        try {
            //Get data from API
            $response  = $this->client->get(env('WEATHER_FORECAST'), [
                'query' => [
                    'lat'     => $detail['latitude'],
                    'lon'     => $detail['longitude'],
                    'exclude' => 'minutely,hourly,alerts',
                    'appid'   => env('WEATHER_API_KEY')
                ]
            ])->getBody();

            return json_decode($response);
        } catch (Throwable $th) {
            throw $th;
        }
    }
}