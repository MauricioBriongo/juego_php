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
        $nombreUsuario=$request->getAttribute('usuario_token');
        $idUsuario=$request->getAttribute('id');
        $datos = $request->getParsedBody();

        $userCarta = $datos['carta'] ?? null;
        $idPartida = $datos['partida'] ?? null;

        if ((!$userCarta) || (!$idPartida)){
            $response->getBody()->write(json_encode(['error' => 'Faltan campos obligatorios']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $control=MazoModel::usuarioEnPartida($idUsuario,$idPartida); //controla que la partida recibida sea la correcta     

        if (!$control){
            $response->getBody()->write(json_encode(['error' => 'La partida no pertenece al usuario o está finalizada']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $idMazo = MazoModel::verificarCarta($userCarta, $idPartida);

        if (!$idMazo){
            $response->getBody()->write(json_encode(['error' => 'Carta inválida']));
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

        //$atrServer = MazoModel::atributosMazoServer();

        $respuesta=[
                    'Carta Servidor'=>$serverCarta,
                    'Ataque usuario' => $resultados['fuerza_jugador'],
                    'Ataque servidor'=>$resultados['fuerza_servidor'],
                    'Ganador'=>null
                     ];

        if (JugadaModel::contarJugadas($idPartida) == 5){

            $puntosJugador = JugadaModel::resultadosJugador($idPartida);            

            if ($puntosJugador['gano'] > $puntosJugador['perdio']) {
                $resultado='gano';
                $ganador=$nombreUsuario;
            } elseif ($puntosJugador['gano'] < $puntosJugador['perdio']) {
                $resultado='perdio';
                $ganador='Servidor';
            } else {
                $resultado = 'empato';
                $ganador = 'Empate';
            }

            PartidaModel::finalizarPartida($idPartida,$resultado);

            $respuesta = ['Carta Servidor'=>$serverCarta,
                          'Ataque usuario' => $resultados['fuerza_jugador'],
                          'Ataque servidor'=>$resultados['fuerza_servidor'],
                          'Ganador'=>$ganador
                        ];           
        }  
        
        $response->getBody()->write(json_encode($respuesta));

        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        
    }

    public static function atributosEnMano(Request $request, Response $response, array $args){

        $usuarioUrl = (int)$args['usuario'] ?? null; 
        $partidaId = (int)$args['partida'] ?? null;

        $usuarioLog = $request->getAttribute('id');
        
        if (($usuarioLog != $usuarioUrl) & ($usuarioUrl!=1)) { //puedo ver las del servidor (id=1)
            
            $respuesta = ['error' => 'No autorizado para ver las cartas de este usuario.'];
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        if (!JugadaModel::usuarioEnPartida($usuarioLog, $partidaId)) {
            $respuesta = ['error' => 'Usuario no participa en la partida.'];
            $response->getBody()->write(json_encode($respuesta));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $atributos = JugadaModel::atributosEnMano($usuarioLog, $partidaId);

        if (isset($atributos['error'])) {
            $response->getBody()->write(json_encode([
                'error' => 'Error al obtener los atributos',
                'detalle' => $atributos['error']
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }

        if (!$atributos){
            $response->getBody()->write(json_encode(['error' => 'Partida finalizada']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
    
        $response->getBody()->write(json_encode($atributos));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);

    }    
}