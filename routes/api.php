<?php
declare(strict_types=1);

use App\Controllers\GameController;
use App\Core\Router;

$router = new Router();
$controller = new GameController();

$router->post('/rooms/create', [$controller, 'create']);
$router->post('/rooms/join', [$controller, 'join']);
$router->post('/game/state', [$controller, 'state']);
$router->post('/game/roll', [$controller, 'roll']);
$router->post('/game/move', [$controller, 'move']);

return $router;
