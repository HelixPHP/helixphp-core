<?php

declare(strict_types=1);

namespace Helix\Providers;

use Helix\Core\Application;
use Psr\Log\LoggerInterface;

/**
 * Logging Service Provider
 */
class LoggingServiceProvider extends ServiceProvider
{
    /**
     * {@inheritdoc}
     */
    public function register(): void
    {
        $this->app->singleton(
            LoggerInterface::class,
            function () {
                $logPath = $this->getLogPath();
                return new \Helix\Providers\Logger($logPath);
            }
        );

        // Aliases
        $this->app->alias('log', LoggerInterface::class);
        $this->app->alias('logger', LoggerInterface::class);
    }

    /**
     * Get the log file path
     */
    private function getLogPath(): string
    {
        // Try to get from environment or config
        if (isset($_ENV['LOG_PATH'])) {
            return $_ENV['LOG_PATH'];
        }

        // Default to logs directory in project root
        $projectRoot = dirname(dirname(__DIR__));
        $logsDir = $projectRoot . '/logs';

        // Create logs directory if it doesn't exist
        if (!is_dir($logsDir)) {
            mkdir($logsDir, 0755, true);
        }

        return $logsDir . '/express-php.log';
    }

    /**
     * {@inheritdoc}
     */
    public function provides(): array
    {
        return [
            LoggerInterface::class,
            'log',
            'logger'
        ];
    }
}
