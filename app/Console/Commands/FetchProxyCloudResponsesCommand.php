<?php

namespace App\Console\Commands;

use App\Jobs\FetchProxyCloudResponses;
use Illuminate\Console\Command;

class FetchProxyCloudResponsesCommand extends Command
{
    protected $signature = 'trmnl:cloud:proxy';

    protected $description = 'Fetch Cloud Screens';

    public function handle(): void
    {
        FetchProxyCloudResponses::dispatchSync();
    }
}
