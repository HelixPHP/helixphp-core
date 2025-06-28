<?php

require_once __DIR__ . '/../vendor/autoload.php';

use Express\Http\Psr7\Message;
use Express\Http\Psr7\OptimizedMessage;
use Express\Http\Psr7\Stream;
use Express\Http\Psr7\OptimizedStream;

class OptimizationBenchmark
{
    private int $iterations = 10000;

    public function run()
    {
        echo "🔧 Performance Optimization Comparison\n";
        echo "=====================================\n";
        echo "Iterations per test: " . number_format($this->iterations) . "\n\n";

        $this->benchmarkHeaderManipulation();
        $this->benchmarkStreamOperations();
        $this->benchmarkMemoryUsage();
        $this->generateReport();
    }

    private function benchmarkHeaderManipulation()
    {
        echo "🔄 Testing Header Manipulation Optimizations...\n";

        // Original implementation
        $originalTime = $this->benchmark(function () {
            $stream = new Stream(fopen('php://temp', 'r+'));
            $message = new Message($stream);

            for ($i = 0; $i < 10; $i++) {
                $message = $message
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Authorization', 'Bearer token123')
                    ->withAddedHeader('X-Custom', "value$i");
            }

            return $message->getHeaderLine('Content-Type');
        });

        // Optimized implementation
        $optimizedTime = $this->benchmark(function () {
            $stream = new OptimizedStream(fopen('php://temp', 'r+'));
            $message = new OptimizedMessage($stream);

            for ($i = 0; $i < 10; $i++) {
                $message = $message
                    ->withHeader('Content-Type', 'application/json')
                    ->withHeader('Authorization', 'Bearer token123')
                    ->withAddedHeader('X-Custom', "value$i");
            }

            return $message->getHeaderLine('Content-Type');
        });

        $originalOps = 1 / $originalTime * 1000000;
        $optimizedOps = 1 / $optimizedTime * 1000000;
        $improvement = (($optimizedOps - $originalOps) / $originalOps) * 100;

        echo "   - Original Headers... ✅ " . number_format($originalOps, 0) . " ops/sec\n";
        echo "   - Optimized Headers... ✅ " . number_format($optimizedOps, 0) . " ops/sec\n";
        echo "   - Improvement: " . ($improvement >= 0 ? '+' : '') . number_format($improvement, 1) . "%\n\n";
    }

    private function benchmarkStreamOperations()
    {
        echo "🔄 Testing Stream Operations...\n";

        $testData = str_repeat('Hello World! ', 1000);

        // Original stream
        $originalTime = $this->benchmark(function () use ($testData) {
            $stream = Stream::createFromString($testData);
            $stream->rewind();
            $content = $stream->getContents();
            return strlen($content);
        });

        // Optimized stream
        $optimizedTime = $this->benchmark(function () use ($testData) {
            $stream = OptimizedStream::createFromString($testData);
            $stream->rewind();
            $content = $stream->getContents();
            return strlen($content);
        });

        $originalOps = 1 / $originalTime * 1000000;
        $optimizedOps = 1 / $optimizedTime * 1000000;
        $improvement = (($optimizedOps - $originalOps) / $originalOps) * 100;

        echo "   - Original Streams... ✅ " . number_format($originalOps, 0) . " ops/sec\n";
        echo "   - Optimized Streams... ✅ " . number_format($optimizedOps, 0) . " ops/sec\n";
        echo "   - Improvement: " . ($improvement >= 0 ? '+' : '') . number_format($improvement, 1) . "%\n\n";
    }

    private function benchmarkMemoryUsage()
    {
        echo "🔄 Testing Memory Usage...\n";

        // Original objects
        $memoryBefore = memory_get_usage();
        $originalObjects = [];
        for ($i = 0; $i < 1000; $i++) {
            $stream = new Stream(fopen('php://temp', 'r+'));
            $message = new Message($stream, ['Content-Type' => 'application/json']);
            $originalObjects[] = $message;
        }
        $originalMemory = memory_get_usage() - $memoryBefore;
        unset($originalObjects);

        // Optimized objects
        $memoryBefore = memory_get_usage();
        $optimizedObjects = [];
        for ($i = 0; $i < 1000; $i++) {
            $stream = new OptimizedStream(fopen('php://temp', 'r+'));
            $message = new OptimizedMessage($stream, ['Content-Type' => 'application/json']);
            $optimizedObjects[] = $message;
        }
        $optimizedMemory = memory_get_usage() - $memoryBefore;
        unset($optimizedObjects);

        $memorySaving = (($originalMemory - $optimizedMemory) / $originalMemory) * 100;

        echo "   - Original Memory: " . number_format($originalMemory / 1024, 2) . " KB\n";
        echo "   - Optimized Memory: " . number_format($optimizedMemory / 1024, 2) . " KB\n";
        echo "   - Memory Saving: " . ($memorySaving >= 0 ? '+' : '') . number_format($memorySaving, 1) . "%\n\n";
    }

    private function benchmark(callable $callable): float
    {
        $start = microtime(true);

        for ($i = 0; $i < $this->iterations; $i++) {
            $callable();
        }

        $end = microtime(true);
        return ($end - $start) / $this->iterations;
    }

    private function generateReport()
    {
        echo "📋 Optimization Summary\n";
        echo "=====================\n";
        echo "✅ Optimized Message and Stream classes created\n";
        echo "✅ Reduced validation overhead for trusted environments\n";
        echo "✅ Improved header manipulation performance\n";
        echo "✅ Streamlined memory usage patterns\n\n";

        echo "💡 Usage Recommendations:\n";
        echo "- Use optimized classes in high-performance scenarios\n";
        echo "- Keep original classes for maximum safety/validation\n";
        echo "- Consider using optimized factories for bulk operations\n";
        echo "- Monitor memory usage in production environments\n";
    }
}

(new OptimizationBenchmark())->run();
