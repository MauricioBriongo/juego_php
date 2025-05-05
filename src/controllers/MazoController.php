<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\UserModel;
use App\Models\MazoModel;
    


class MazoController{

    public static function crearMazo(Request $request, Response $response){
        $datos=$request->getParsedBody();//deberia recibir un nombre de mazo y un array de 5 id de cartas
        $nombre=$datos['nombre'];
        $cartas=$datos['cartas'];
        $cartasPorMazo = 5;
        $mazosPermitidos=3;
        $usuarioId=$request->getAttribute('id'); //obtengo el id de usuario desde lo que mandó el token

        if (empty($nombre) || !is_array($cartas) || count($cartas)!=$cartasPorMazo){
            $response->getBody()->write(json_encode(['error' => 'Debe enviar un nombre de mazo y exactamente 5 cartas']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        if (count(array_unique($cartas))!= $cartasPorMazo){
            $response->getBody()->write(json_encode(['error' => 'No se pueden repetir cartas']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $cantMazos = MazoModel::contarMazos($usuarioId);
            if ($cantMazos >= $mazosPermitidos){
                $response->getBody()->write(json_encode(['error' => 'Máximo 3 mazos permitidos']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

        foreach ($cartas as $cartaId) {
            if (!MazoModel::existeCarta($cartaId)) {
                $response->getBody()->write(json_encode(['error' => "Carta $cartaId no existe"]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }
        }

        try{
            $mazoId = MazoModel::altaMazo($usuarioId,$nombre,$cartas);
        
            $response->getBody()->write(json_encode(['id' => $mazoId,'nombre' => $nombre]));

            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        }catch(\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'No se pudo crear el mazo']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }
    }

    public static function obtenerMazosUsuario(Request $request, Response $response) {
        
        $usuarioId = $request->getAttribute('id'); // valida login
        $usuarioNombre=$request->getAttribute('usuario');
        try {
            $mazosData = MazoModel::getMazosPorUsuario($usuarioId);

            if (isset($mazosData['error'])) {
                $response->getBody()->write(json_encode($mazosData));

                return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
            }

            $mazos=['usuario'=>$usuarioNombre];

            foreach ($mazosData as $fila){
                $nombreMazo = $fila['mazo'];
                $nombreCarta = $fila['carta'];

                if(!isset($mazos[$nombreMazo])){
                    $mazos[$nombreMazo]=[];
                }

                $mazos[$nombreMazo][]=$nombreCarta;
            }


            $response->getBody()->write(json_encode($mazos));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['error' => 'No se pudieron obtener los mazos']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public static function listarCartasConParametros(Request $request, Response $response) {

        $params = $request->getQueryParams(); // botiene parametros del request -> nom y atr
        $nombre = $params['nombre'] ?? null; // por si no se envia el dato
        $atributo = $params['atributo'] ?? null;
        

        try {
            $cartas = MazoModel::listarCartas($nombre, $atributo); // consulta sql en el model

            $response->getBody()->write(json_encode($cartas)); // dicho met retornara algo y eso se guarda en $response
            
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        
        } catch (\Exception $e) {

            $response->getBody()->write(json_encode(['error' => 'No se pudieron obtener las cartas'])); // sino cap la exp y retorna msj de error
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public static function eliminarMazo($request, $response, $args) {
        $idMazo = $args['mazo']; 
        $usuarioId = $request->getAttribute('id'); 

        if (!MazoModel::verificarMazo($idMazo, $usuarioId)) {
            $response->getBody()->write(json_encode(['error' => 'El mazo no existe o no pertenece al usuario']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        if (MazoModel::mazoUsado($idMazo)) {
            $response->getBody()->write(json_encode(['error' => 'El mazo a ha sido usado en una partida, y no puede ser eliminado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
        }

        // Si todas las validaciones anteriores son correctas, ahora intentamos eliminar el mazo
        try {
            $result = MazoModel::borrarMazo($idMazo);

            if (isset($result['error'])) {
                $response->getBody()->write(json_encode(['mensaje' => 'error al eliminar el maso']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
            $response->getBody()->write(json_encode(['mensaje' => 'Mazo eliminado correctamente']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } catch (\Exception $e) {
            // cualquier otro error
            $response->getBody()->write(json_encode(['mensaje' => 'Error interno del servidor']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);


        }
    }

    public static function actualizarNombreMazo($request, $response, $args){
        $mazoId = $args['mazo'];
        $usuarioId = $request->getAttribute('id');

        $datos = $request->getParsedBody();
        $nuevoNombre = $datos['nombre'] ?? null;

        if (empty($nuevoNombre)) {
            $response->getBody()->write(json_encode(['error' => 'Debe proporcionar un nuevo nombre para el mazo']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        $resultado = MazoModel::actualizarNombreMazo($mazoId, $nuevoNombre, $usuarioId);

        if (isset($resultado['error'])) {
            $response->getBody()->write(json_encode($resultado));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }
        $response->getBody()->write(json_encode($resultado));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    }
}