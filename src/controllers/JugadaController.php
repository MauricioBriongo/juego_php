<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\UserModel;
use App\Models\MazoModel;
use App\Models\PartidaModel;
use App\Models\JugadaModel;


class JugadaController{

    protected static function jugadaServidor():int{ //devuelve id de la carta que juega servidor
       
       $cartasServer=MazoModel::cartasServidor();//array de las cartas válidas que puede usar servidor
        
       $claveAleatoria = array_rand($cartasServer);

       $elegida = $cartasServer[$claveAleatoria];

       MazoModel::actualizarEstado(1,$elegida);//ya se actualiza esta a descartado de la carta elegida

       return $elegida;        
    }

    public static function quienGano($datos):array{ //devuelve array con: un int que representa al ganador, las fuerzas finales de los ataques jugados
        $res=[];

        if ($datos['ventaja_jugador'] > 0){

            $datos['ataque_jugador'] *=1.3;
        }elseif($datos['ventaja_servidor'] > 0){
            $datos['ataque_servidor'] *=1.3;
        }

        $res=['ganador'=>($datos['ataque_jugador'] - $datos['ataque_servidor']), //si la resta da positivo gano jugador, si da negativo ganó servidor, si da 0 empate
             'fuerza_jugador'=>$datos['ataque_jugador'],
             'fuerza_servidor'=>$datos['ataque_servidor']];
        return $res;
    }
   
    public static function registroJugada(Request $request, Response $response){
        $nombreUsuario=$request->getAttribute('usuario');
        $datos = $request->getParsedBody();

        $userCarta = $datos['carta'];
        $idPartida = $datos['partida'];

        $idMazo = MazoModel::verificarCarta($userCarta, $idPartida);

        if (!$idMazo){
            $response->getBody()->write(json_encode(['error' => 'carta invalida']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $serverCarta = self::jugadaServidor();


        
        $datos = JugadaModel::datosJugada($userCarta,$serverCarta);
        
        $resultados = self::quienGano($datos);

        $ganador = $resultados['ganador'];

        if ($ganador > 0){
            $el_usuario = 'gano';
        } elseif ($ganador < 0) {
            $el_usuario = 'perdio';
        } else {
            $el_usuario = 'empato';
        }
        
        JugadaModel::registrarJugada($idPartida,$serverCarta,$userCarta,$el_usuario);

        MazoModel::actualizarEstado($idMazo,$userCarta);

        $atrServer = MazoModel::atributosMazoServer();

        $respuesta=[
                    'Carta Servidor'=>$serverCarta,
                    'Ataque usuario' => $resultados['fuerza_jugador'],
                    'Ataque servidor'=>$resultados['fuerza_servidor'],
                    'Atributos Servidor'=>$atrServer
                     ];

        if (JugadaModel::contarJugadas($idPartida) == 5){

            $puntosJugador = JugadaModel::resultadosJugador($idPartida);            

            if ($puntosJugador['gano'] > $puntosJugador['perdio']) {
                $resultado='gano';
            } elseif ($puntosJugador['gano'] < $puntosJugador['perdio']) {
                $resultado='perdio';
            } else {
                $resultado = 'empato';
            }

            PartidaModel::finalizarPartida($idPartida,$resultado);

            $respuesta = ['Usuario: '=>$nombreUsuario,
                          'Resultado: '=>$resultado,
                          'Victorias: '=>$puntosJugador['gano'],
                          'Derrotas: '=>$puntosJugador['perdio']
                        ];           
        }

        
        
        $response->getBody()->write(json_encode($respuesta));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        
    }
    
}