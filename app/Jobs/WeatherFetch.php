<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use App\Models\Weather;
use Illuminate\Support\Facades\Log;
use App\Services\WeatherApiService;
use Exception;

class WeatherFetch implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            $weatherData = [];

            foreach (config('helper-config.citiesArray') as $key => $detail) {

                //Fetch data from API
                $weatherApi = new WeatherApiService;
                $data = $weatherApi->getWeatherData($detail);
                //Convert UNIX time to date string
                $date = Carbon::createFromTimestamp($data->current->dt)->toDateString();

                $weatherData[$date][$detail['city']] = $data->current;
            }

            foreach ($weatherData as $key => $weather) {
                //Log data to DB
                Weather::updateOrCreate(
                    ['date' => $key],
                    ['data' => $weather],
                );
                Log::alert("Data create or update successfully!!");
            }
        } catch (Exception $e) {
            Log::alert($e->getMessage());
        }
    }
}