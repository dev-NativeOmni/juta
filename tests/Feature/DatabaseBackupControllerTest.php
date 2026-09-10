<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class DatabaseBackupControllerTest extends TestCase
{
    public function test_store_validates_prune_parameter_true()
    {
        Artisan::shouldReceive('call')->withArgs(function ($command, $params) {
            return $command === 'ims:backup-database' && $params['--prune'] === true;
        })->once()->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        $response = $this->withoutMiddleware()->post(route('database-backups.store'), ['prune' => 1]);
        $response->assertSessionHasNoErrors();
    }

    public function test_store_validates_prune_parameter_false()
    {
        Artisan::shouldReceive('call')->withArgs(function ($command, $params) {
            return $command === 'ims:backup-database' && $params['--prune'] === false;
        })->once()->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        $response = $this->withoutMiddleware()->post(route('database-backups.store'), ['prune' => 0]);
        $response->assertSessionHasNoErrors();
    }

    public function test_store_defaults_to_true_when_prune_is_missing()
    {
        Artisan::shouldReceive('call')->withArgs(function ($command, $params) {
            return $command === 'ims:backup-database' && $params['--prune'] === true;
        })->once()->andReturn(0);
        Artisan::shouldReceive('output')->once()->andReturn('');

        $response = $this->withoutMiddleware()->post(route('database-backups.store'));
        $response->assertSessionHasNoErrors();
    }
}
