<?php

namespace DanielCHood\BaseballMatchupComparisonPredictions;

use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\Dotenv\Dotenv;

class Container {
    public static function getInstance(
        string $envPath,
        string $configPath
    ): \Symfony\Component\DependencyInjection\Container {
        static $container;

        if (!$container) {
            // Load .env file before creating the container
            $env = new Dotenv();
            $env->loadEnv($envPath);

            // Create the container
            $container = new ContainerBuilder();

            // Load the service configuration
            $loader = new YamlFileLoader(
                $container,
                new FileLocator($configPath),
                'PROD'
            );

            $loader->load('services.yml');

            $container->compile(true);
        }

        return $container;
    }
}
