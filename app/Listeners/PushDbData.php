<?php

namespace App\Listeners;

use App\Events\WeatherDataFetch;
use App\Models\Weather;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;
use Exception;

class PushDbData implements ShouldQueue
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\WeatherDataFetch  $event
     * @return void
     */
    public function handle(WeatherDataFetch $data)
    {
        $date = array_keys($data->data)[0];

        //Log data to DB
        Weather::updateOrCreate(
            ['date' => $date],
            ['data' => $data->data[$date]],
        );
    }
}