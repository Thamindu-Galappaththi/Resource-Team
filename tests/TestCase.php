<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    public function createApplication()
    {
        $this->forceSqliteTestingEnvironment();
        $this->forgetConfigurationCache();

        $app = parent::createApplication();

        $app['config']->set('database.default', 'sqlite');
        $app['config']->set('database.connections.sqlite.database', ':memory:');
        $app['config']->set('database.connections.sqlite.driver', 'sqlite');
        $app['db']->purge();

        $default = $app['config']->get('database.default');
        $driver = $app['config']->get("database.connections.{$default}.driver");
        $database = $app['config']->get("database.connections.{$default}.database");

        if ($driver !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(
                "Refusing to run tests against {$driver}:{$database}. Tests must use sqlite :memory: so local MySQL data is not wiped."
            );
        }

        return $app;
    }

    private function forceSqliteTestingEnvironment(): void
    {
        foreach ([
            'APP_ENV' => 'testing',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => ':memory:',
            'DB_URL' => '',
        ] as $key => $value) {
            putenv($key.'='.$value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    private function forgetConfigurationCache(): void
    {
        $cacheDir = dirname(__DIR__).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'cache';

        foreach (['config.php', 'routes-v7.php', 'routes.php'] as $file) {
            $cached = $cacheDir.DIRECTORY_SEPARATOR.$file;
            if (is_file($cached)) {
                @unlink($cached);
            }
        }
    }
}
