<?php

namespace App\Models;
use App\Models\DB;
use PDO;

class JugadaModel{
    public static function registrarJugada($idPartida, $serverCarta, $userCarta, $el_usuario){
            $link=new DB();
            $pdo = $link->getConnection();
        try{
            $stmt=$pdo->prepare("INSERT INTO jugada (partida_id, carta_id_a, carta_id_b, el_usuario)
                                VALUES (:idPartida, :cartaServer, :cartaUser, :resultado)");
    
             $stmt->execute([':idPartida' => $idPartida, 
                             ':cartaServer'=>$serverCarta,
                             ':cartaUser' => $userCarta,
                            ':resultado'=>$el_usuario]);
        }catch(PDOException $e){
            return['Error'=> 'al registrar jugada' . $e->getMessage()];
        }

    }

    public static function datosJugada($userCarta,$serverCarta){
        $link=new DB();
        $pdo = $link->getConnection();

        $sql = "SELECT 
                    c1.ataque AS ataque_jugador, 
                    c1.atributo_id AS atributo_jugador,

                    c2.ataque AS ataque_servidor, 
                    c2.atributo_id AS atributo_servidor,

                    CASE
                        WHEN ga1.atributo_id IS NOT NULL THEN 1
                        ELSE 0
                    END AS ventaja_jugador,

                    CASE
                        WHEN ga2.atributo_id IS NOT NULL AND ga1.atributo_id IS NULL THEN 1
                        ELSE 0
                    END AS ventaja_servidor

                FROM carta AS c1

                JOIN carta c2 ON c2.id = :cartaServidor

                LEFT JOIN gana_a ga1 ON ga1.atributo_id = c1.atributo_id AND ga1.atributo_id2 = c2.atributo_id
                LEFT JOIN gana_a ga2 ON ga2.atributo_id = c2.atributo_id AND ga2.atributo_id2 = c1.atributo_id

                WHERE c1.id = :cartaJugador";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([':cartaJugador'=>$userCarta,
                        ':cartaServidor'=>$serverCarta]);

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        return $datos;

    }

    public static function contarJugadas($idPartida):int{
        $link = new DB();
        $pdo = $link->getConnection();

        $sql="SELECT COUNT(*) AS total FROM jugada WHERE partida_id = :idPartida";

        $stmt=$pdo->prepare($sql);
        $stmt->execute([':idPartida'=>$idPartida]);

        $cant = $stmt->fetch();

        return (int) $cant['total'];
    }

    public static function resultadosJugador($idPartida){
        $link = new DB();
        $pdo = $link->getConnection();

        $sql = "SELECT
              (SELECT COUNT(*) FROM jugada WHERE partida_id = :idPartida AND el_usuario = 'gano') AS gano,
              (SELECT COUNT(*) FROM jugada WHERE partida_id = :idPartida AND el_usuario = 'perdio') AS perdio";

        $stmt=$pdo->prepare($sql);

        $stmt->execute([':idPartida'=>$idPartida]);

        $datos = $stmt->fetch();

        return [ //porque puede devolver Strings en lugar de enteros
            'gano' => (int)$datos['gano'],
            'perdio' => (int)$datos['perdio']
        ];

    }

    public static function usuarioEnPartida($usuarioLog, $partidaId):bool{
        $link = new DB();
        $pdo = $link->getConnection();

        $sql = "SELECT COUNT(*) FROM PARTIDA 
                WHERE usuario_id = :usuario
                AND id =:partida";

        $stmt=$pdo->prepare($sql);

        $stmt->execute([':partida'=>$partidaId,
                        ':usuario'=>$usuarioLog]);

        $count = $stmt->fetchColumn();

        return $count>0;
    }

    public static function atributosEnMano($usuarioLog, $partidaId){
        try{
            $link = new DB();
            $pdo = $link->getConnection();

            $sql = "SELECT c.atributo_id
                    FROM partida p
                    JOIN mazo_carta mc ON mc.mazo_id = p.mazo_id
                    JOIN carta c ON c.id = mc.carta_id
                    WHERE p.id = :partida
                        AND p.usuario_id = :usuario
                        AND mc.estado = 'en_mano'"; 

            $stmt=$pdo->prepare($sql);

            $stmt->execute([':partida'=>$partidaId,
                            ':usuario'=>$usuarioLog]);

            $atributos = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return $atributos;
            }catch (PDOException $e){
                return['error'=> 'al buscar atributos en mano' . $e->getMessage()];
            }
        }
    
}