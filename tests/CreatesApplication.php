<?php

namespace Tests;

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;

trait CreatesApplication
{
    public function createApplication(): Application
    {
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        $database = $app->make('config')->get('database.connections.mysql.database');

        if ($database !== 'testing') {
            fwrite(STDERR, "\nABORTADO: os testes apontam para o banco '{$database}' em vez de 'testing'.\n".
                "Provável config em cache. Rode: docker-compose exec -T app php artisan config:clear\n\n");
            exit(1);
        }

        return $app;
    }
}
