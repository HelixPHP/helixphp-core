<?php

use Express\Core\Application;
use Express\Events\ApplicationStarted;
use Express\Events\RequestReceived;
use Express\Events\ResponseSent;

require_once '../vendor/autoload.php';

// Criar aplicação com providers PSR
$app = Application::create(__DIR__ . '/..');

// Registrar listeners de eventos
$app->addEventListener(ApplicationStarted::class, function (ApplicationStarted $event) {
    echo "Application started at: " . $event->startTime->format('Y-m-d H:i:s') . "\n";
});

$app->addEventListener(RequestReceived::class, function (RequestReceived $event) {
    echo "Request received: " . $event->request->getMethod() . " " . $event->request->getUri() . "\n";
});

$app->addEventListener(ResponseSent::class, function (ResponseSent $event) {
    echo "Response sent with status: " . $event->response->getStatusCode() .
         " (Processing time: " . round($event->processingTime * 1000, 2) . "ms)\n";
});

// Configurar rotas
$app->get('/', function ($request, $response) use ($app) {
    // Usar o logger PSR-3
    $logger = $app->getLogger();
    if ($logger) {
        $logger->info('Hello route accessed');
    }

    return $response->json([
        'message' => 'Hello from Express-PHP with PSR Providers!',
        'version' => $app->version(),
        'providers' => [
            'container' => $app->has('container') ? 'PSR-11' : 'none',
            'logger' => $app->has('logger') ? 'PSR-3' : 'none',
            'events' => $app->has('events') ? 'PSR-14' : 'none',
        ],
        'timestamp' => (new DateTime())->format('c')
    ]);
});

$app->get('/events/test', function ($request, $response) use ($app) {
    // Disparar evento customizado
    $customEvent = new class {
        public string $name = 'Custom Test Event';
        public DateTime $timestamp;

        public function __construct() {
            $this->timestamp = new DateTime();
        }
    };

    $app->dispatchEvent($customEvent);

    return $response->json([
        'message' => 'Custom event dispatched',
        'event' => $customEvent->name,
        'timestamp' => $customEvent->timestamp->format('c')
    ]);
});

$app->get('/services', function ($request, $response) use ($app) {
    $container = $app->getContainer();

    $services = [];

    // Testar se os serviços estão disponíveis
    if ($app->has('container')) {
        $services['container'] = 'Available (PSR-11)';
    }

    if ($app->has('logger')) {
        $services['logger'] = 'Available (PSR-3)';
    }

    if ($app->has('events')) {
        $services['events'] = 'Available (PSR-14)';
    }

    if ($app->has('listeners')) {
        $services['listeners'] = 'Available (PSR-14)';
    }

    return $response->json([
        'message' => 'PSR Services Status',
        'services' => $services,
        'container_type' => get_class($container)
    ]);
});

// Executar aplicação
$app->run();
