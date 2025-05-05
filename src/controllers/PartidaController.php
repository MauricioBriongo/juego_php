<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\UserModel;
use App\Models\MazoModel;
use App\Models\PartidaModel;

class PartidaController{

    public static function crearPartida(Request $request, Response $response){ //recibe un id de mazo en el cuepro, usuario e id en la validación
        
        $idUsuario = $request->getAttribute('id');

        $idMazo = $request->getParsedBody()['idMazo'];

        if (!$idMazo){ //si no mandan el id de mazo
            $response->getBody()->write(json_encode(['error '=>'se requiere un id de mazo']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);  
        }   

        if (!MazoModel::verificarMazo($idMazo,$idUsuario)) { //si el id de mazo no es el de usuario
            $response->getBody()->write(json_encode(['error' => 'El mazo no te pertenece']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);  
        }

        try {
            $idPartida = PartidaModel::crearPartida($idUsuario, $idMazo);

            MazoModel::actualizarEstado($idMazo,null,null);

            $cartas = MazoModel::obtenerCartas($idMazo);

            $atributosServer = MazoModel::atributosMazoServer();

            $response->getBody()->write(json_encode([
                'id de partida ' => $idPartida,
                'atributos cartas Servidor'=>$atributosServer,
                'cartas Usuario' => $cartas
            ]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

        } catch (Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'No se pudo crear la partida']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }


    
}