<?php

namespace Dancycodes\Hyper\Tests;

use Dancycodes\Hyper\HyperServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Additional setup can go here
    }

    /**
     * Get package providers
     */
    protected function getPackageProviders($app): array
    {
        return [
            HyperServiceProvider::class,
        ];
    }

    /**
     * Define environment setup
     */
    protected function getEnvironmentSetUp($app): void
    {
        // Setup default configuration
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Register test views
        $app['config']->set('view.paths', [
            __DIR__ . '/Fixtures/views',
        ]);

        // Load test routes
        if (file_exists(__DIR__ . '/Fixtures/routes/test.php')) {
            $app['router']->middleware('web')
                ->group(__DIR__ . '/Fixtures/routes/test.php');
        }
    }

    /**
     * Helper to create a fake Hyper request
     */
    protected function makeHyperRequest($uri = '/', $method = 'GET', $data = [])
    {
        return $this->call($method, $uri, $data, [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
    }

    /**
     * Helper to create request with signals
     */
    protected function makeRequestWithSignals($uri, $signals, $method = 'POST')
    {
        return $this->call($method, $uri, ['datastar' => $signals], [], [], [
            'HTTP_DATASTAR_REQUEST' => 'true',
        ]);
    }

    /**
     * Assert that response contains SSE event
     */
    protected function assertHasSSEEvent($response, $eventType)
    {
        $content = $response->getContent();
        $this->assertStringContainsString("event: {$eventType}", $content);
    }

    /**
     * Get SSE events from response
     */
    protected function getSSEEvents($response): array
    {
        // For StreamedResponse, we need to capture the output
        if ($response instanceof \Symfony\Component\HttpFoundation\StreamedResponse) {
            // Use callback-based output buffering to capture even flushed content
            $content = '';
            $callback = function ($buffer) use (&$content) {
                $content .= $buffer;

                return ''; // Don't output anything
            };

            ob_start($callback, 1); // Chunk size of 1 to capture immediately

            try {
                $response->sendContent();

                // End our callback buffer
                while (ob_get_level() > 0) {
                    ob_end_flush(); // This will call our callback
                }
            } catch (\Throwable $e) {
                // Clean up on error
                while (ob_get_level() > 0) {
                    ob_end_clean();
                }
                throw $e;
            }
        } else {
            $content = $response->getContent();
        }
        $lines = explode("\n", $content);

        $events = [];
        $currentEvent = null;
        $dataLines = [];

        foreach ($lines as $line) {
            if (str_starts_with($line, 'event:')) {
                // Save previous event if exists
                if ($currentEvent) {
                    $currentEvent['data'] = $this->parseSSEData($dataLines);
                    $events[] = $currentEvent;
                    $dataLines = [];
                }
                $currentEvent = ['type' => trim(substr($line, 6))];
            } elseif (str_starts_with($line, 'data:')) {
                // Collect data lines
                $dataLines[] = trim(substr($line, 5));
            } elseif (trim($line) === '' && $currentEvent) {
                // Empty line marks end of event
                $currentEvent['data'] = $this->parseSSEData($dataLines);
                $events[] = $currentEvent;
                $currentEvent = null;
                $dataLines = [];
            }
        }

        // Handle last event if not closed
        if ($currentEvent) {
            $currentEvent['data'] = $this->parseSSEData($dataLines);
            $events[] = $currentEvent;
        }

        return $events;
    }

    /**
     * Parse SSE data lines for signals
     */
    protected function parseSSEData(array $dataLines): string
    {
        // For signal events, extract the JSON from "signals {json}" lines
        $signalData = [];
        foreach ($dataLines as $line) {
            if (str_starts_with($line, 'signals ')) {
                $signalData[] = substr($line, 8); // Remove "signals " prefix
            }
        }

        // If we have signal data lines, join them (for multi-line JSON)
        if (!empty($signalData)) {
            return implode('', $signalData);
        }

        // Otherwise, return all data lines joined
        return implode("\n", $dataLines);
    }
}
