<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    /**
     * Creates the application.
     */
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        if (! $app->environment('testing')
            || config('database.default') !== 'mysql'
            || config('database.connections.mysql.database') !== 'intrams_test'
            || ! in_array(config('database.connections.mysql.host'), ['127.0.0.1', 'localhost'], true)) {
            throw new \RuntimeException('Tests require the local intrams_test database and uncached testing configuration. No database refresh was started.');
        }

        return $app;
    }
}
