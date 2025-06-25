<?php
namespace Express\Test;

require_once 'layout.php';
$path = __DIR__;
$path = explode(DIRECTORY_SEPARATOR, $path);
$path = array_slice($path, 0, count($path) - 1);
$path = implode(DIRECTORY_SEPARATOR, $path);
require_once $path . '/vendor/autoload.php';
use Express\SRC\Controller\Router;

//testar rotas para usar nome variÃ¡vel no path

echo '<pre>';

// var_dump($_SERVER);

Router::use('/user');
Router::get('/:id', function($id) {
    echo "User ID: $id";
});
Router::get('/:id/:rotina', function($id, $rotina) {
    echo "User ID: $id, Rotina: $rotina";
});
Router::post('/:id/', function($id, $rotina) {
    echo "User ID: $id, Rotina: $rotina";
});
Router::post('/:id/:rotina', function($id, $rotina) {
    echo "User ID: $id, Rotina: $rotina";
});



// var_dump(Router::toString());
print_r(Router::identify(strtolower($_SERVER['REQUEST_METHOD']), '/user/123/update')); // Deve retornar a rota com o handler correspondente
echo '</pre>';

