<?php

namespace Tests\Feature;

use App\Events\WeatherDataFetch;
use App\Jobs\WeatherFetch;
use App\Listeners\PushDbData;
use App\Models\Weather;
use App\Services\WeatherApiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

class WeatherTest extends TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate');
    }

    public function test_weather_fetch_schedule()
    {
        dispatch(new WeatherFetch);
        $this->assertDatabaseHas('weather', [
            'date' => Carbon::now()->toDateString(),
        ]);
    }

    public function test_weather_fetch_by_date()
    {
        $response = $this->get('api/fetchWeather/' . Carbon::now()->toDateString());
        $response->assertStatus(200);
    }

    public function test_weather_fetch_by_date_with_event()
    {
        $response = $this->get('api/fetchWeather/' . Carbon::now()->addDays('5')->toDateString());
        $this->assertDatabaseHas('weather', [
            'date' => Carbon::now()->addDays('5')->toDateString(),
        ]);
        $response->assertStatus(200);
    }

    public function test_weather_event_call()
    {
        Event::fake(WeatherDataFetch::class);
        $this->get('api/fetchWeather/' . Carbon::now()->addDay()->toDateString());
        Event::assertDispatched(WeatherDataFetch::class);
        Event::assertListening(
            WeatherDataFetch::class,
            PushDbData::class
        );
    }
}