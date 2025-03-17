<?php

namespace Database\Seeders;

use App\Models\Plugin;
use Illuminate\Database\Seeder;

class ExampleReceiptsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Plugin::create(
            [
                'uuid' => '9e46c6cf-358c-4bfe-8998-436b3a207fec',
                'name' => 'ÖBB Departures',
                'user_id' => '1',
                'data_payload' => null,
                'data_stale_minutes' => 15,
                'data_strategy' => 'polling',
                'polling_url' => 'https://dbf.finalrewind.org/Wien%20Hbf.json?detailed=1&version=3&limit=8&admode=dep&hafas=%C3%96BB&platforms=1%2C2',
                'polling_verb' => 'get',
                'polling_header' => null,
                'render_markup' => null,
                'render_markup_view' => 'receipts.train',
                'detail_view_route' => null,
                'icon_url' => null,
                'flux_icon_name' => 'train-front',
            ]
        );

        Plugin::create(
            [
                'uuid' => '3b046eda-34e9-4232-b935-c33b989a284b',
                'name' => 'Weather',
                'user_id' => '1',
                'data_payload' => null,
                'data_stale_minutes' => 60,
                'data_strategy' => 'polling',
                'polling_url' => 'https://api.met.no/weatherapi/locationforecast/2.0/compact?lat=48.2083&lon=16.3731',
                'polling_verb' => 'get',
                'polling_header' => null,
                'render_markup' => null,
                'render_markup_view' => 'receipts.weather',
                'detail_view_route' => null,
                'icon_url' => null,
                'flux_icon_name' => 'sun',
            ]
        );

        Plugin::create(
            [
                'uuid' => '21464b16-5f5a-4099-a967-f5c915e3da54',
                'name' => 'Zen Quotes',
                'user_id' => '1',
                'data_payload' => null,
                'data_stale_minutes' => 720,
                'data_strategy' => 'polling',
                'polling_url' => 'https://zenquotes.io/api/today',
                'polling_verb' => 'get',
                'polling_header' => null,
                'render_markup' => null,
                'render_markup_view' => 'receipts.zen',
                'detail_view_route' => null,
                'icon_url' => null,
                'flux_icon_name' => 'chat-bubble-bottom-center',
            ]
        );
    }
}
