<?php

use App\Jobs\FetchProxyCloudResponses;

// Artisan::command('inspire', function () {
//    $this->comment(Inspiring::quote());
// })->purpose('Display an inspiring quote')->hourly();

Schedule::job(new FetchProxyCloudResponses)->everyFifteenMinutes();
