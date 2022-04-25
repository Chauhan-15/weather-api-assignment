<?php

namespace App\Http\Controllers;

use App\Models\Weather;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Carbon;
use App\Events\WeatherDataFetch;
use App\Services\WeatherApiService;
use Throwable;


class WeatherApiController extends Controller
{
    private $service;

    public function __construct(WeatherApiService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a weather data as per date request by user.
     * 
     * @param string $date
     * 
     * @return void
     * 
     */
    public function fetchWeather($date)
    {
        // validate the date format
        $validator = Validator::make(['date' => $date], [
            'date' => 'date_format:Y-m-d'
        ]);

        // if validation fail, return error
        if ($validator->fails()) {
            return response()->json($validator->errors(), 400);
        } else {

            // check data in database
            $checkDataInDb = Weather::where('date', $date)->first();

            // initalize log data array 
            $weatherData = [];

            // if data exist, return success response
            if ($checkDataInDb) {
                return response()->json($checkDataInDb, 200);
            } else {
                // if data does not exist, fetch data from api
                try {
                    foreach (config('helper-config.citiesArray') as $key => $detail) {
                        $data = $this->service->getWeatherData($detail);
                        // dd($data);
                        foreach ($data->daily as $key1 => $dateCheck) {
                            $check = Carbon::createFromTimestamp($dateCheck->dt)->toDateString();
                            if ($check == $date) {
                                $weatherData[$date][$detail['city']] = $dateCheck;
                            }
                        }
                    }

                    // if we have weather data after api hit, then we call the event and store the data in db.
                    if (isset($weatherData[$date])) {
                        event(new WeatherDataFetch($weatherData));
                        return response()->json($weatherData, 200);
                    }
                    return response()->json('Data Not Found for ' . $date, 400);
                } catch (Throwable $th) {
                    return response()->json($th->getMessage(), 400);
                }
            }
        }
    }
}