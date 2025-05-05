<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use App\Controllers\UserController;
use App\Controllers\MazoController;
use App\Controllers\PartidaController;
use App\Controllers\JugadaController;
use App\Controllers\EstadisticasController;
use App\Middleware\VerificarToken;

require __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../Models/DB.php';



$app = AppFactory::create();
$app->addBodyParsingMiddleware();



$app->post('/registro', [UserController::class, 'registro']);

$app->post('/login', [UserController::class, 'login']);

$app->get('/usuarios/{usuario}',[UserController::class, 'getUser'])->add(new VerificarToken());

$app->put('/usuarios/{usuario}',[UserController::class, 'updateUser'])->add(new VerificarToken());

$app->get('/usuarios/{usuario}/mazos',[MazoController::class, 'obtenerMazosUsuario'])->add(new VerificarToken());

$app->post('/mazos',[MazoController::class, 'crearMazo'])->add(new VerificarToken());

$app->post('/partidas',[PartidaController::class, 'crearPartida'])->add(new VerificarToken());

$app->post('/jugadas',[JugadaController::class, 'registroJugada'])->add(new VerificarToken());

$app->get('/estadisticas',[EstadisticasController::class, 'estadisticas']);

$app->get('/cartas',[MazoController::class, 'listarCartasConParametros'])->add(new VerificarToken());

$app->delete('/mazos/{mazo}', [MazoController::class , ':eliminarMazo'])->add(new VerificarToken());

$app->put('/mazos/{mazo}', [MazoController::class, 'actualizarNombreMazo'])->add(new VerificarToken());

$app->run();

