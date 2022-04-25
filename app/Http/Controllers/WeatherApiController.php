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

            // vaildation success
        } else {

            // check data in database
            $checkDataInDb = Weather::where('date', $date)->first();

            $weatherData = [];

            // if data exist, user able to see data from db
            if ($checkDataInDb) {
                return response()->json($checkDataInDb, 200);

                // if data does not exist, fetch data from api as per entered date
            } else {
                foreach (config('helper-config.citiesArray') as $key => $detail) {
                    try {
                        $data = $this->service->getWeatherData($detail);

                        foreach ($data->daily as $key1 => $dateCheck) {
                            $check = Carbon::createFromTimestamp($dateCheck->dt)->toDateString();
                            if ($check == $date) {
                                $weatherData[$date][$detail['city']] = $dateCheck;
                            }
                        }
                    } catch (Throwable $th) {
                        return response()->json($th->getMessage(), 400);
                    }
                }
                // if we have weather data after api hit, then we call the event and store the data in db.
                if (isset($weatherData[$date])) {
                    try {
                        event(new WeatherDataFetch($weatherData));
                        return response()->json($weatherData, 200);
                    } catch (Throwable $th) {
                        return response()->json($th->getMessage(), 400);
                    }
                }
                return response()->json('Data not found', 400);
            }
        }
    }
}