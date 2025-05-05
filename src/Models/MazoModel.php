<?php

namespace App\Models;
use App\Models\DB;
use PDO;

class MazoModel{

    public static function contarMazos($id){ //$id es id de usuario
        try{
            $link= new DB();
            $pdo = $link->getConnection();

            $stmt = $pdo->prepare ("SELECT COUNT(*) FROM mazo 
                                    WHERE usuario_id = :usuarioId");

            $stmt->execute([':usuarioId' => $id]);
            
            $resultado =$stmt->fetchColumn();

            return $resultado;
        }catch (PDOException $e){
            return ['error' => 'Error al contar mazos ' . $e->getMessage()]; 
        }        
    }

    public static function existeCarta($cartaId){
        try{
            $link= new DB();
            $pdo = $link->getConnection();
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM carta WHERE id = :cartaId");
            $stmt->execute(['cartaId'=>$cartaId]);
            $existe=$stmt->fetchColumn() > 0;

            return $existe;
        }catch (PDOException $e){
            return ['error' => 'Error al buscar carta ' . $e->getMessage()]; 
        }
    }

    public static function altaMazo($idUsuario, $nombreMazo, $cartas){
        try{
            $link= new DB();
            $pdo = $link->getConnection();

            $stmt = $pdo->prepare("INSERT INTO mazo (usuario_id, nombre) 
                                   VALUES (:usuarioId, :nombre)");

            $stmt->execute(['usuarioId' => $idUsuario, 
                            'nombre' => $nombreMazo]);

            $mazoId = $pdo->lastInsertId();

            foreach ($cartas as $cartaId){
                 $stmt = $pdo->prepare("INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES (:mazoId, :cartaId, 'en_mazo')");
                 $stmt->execute(['mazoId'=>$mazoId,'cartaId'=>$cartaId]);
            }

            return $mazoId;
        } catch (PDOException $e){
            return ['error'=>'Error al insertar mazo '.$e->getMessage()];
        }
        
    }

    public static function verificarMazo($idMazo,$idUsuario){
        $link = new DB();
        $pdo = $link->getConnection();

        $stmt = $pdo->prepare("SELECT COUNT(*) 
                               FROM mazo 
                               WHERE id = :idMazo 
                               AND usuario_id = :idUsuario");
        $stmt->execute([':idUsuario' => $idUsuario, ':idMazo'=>$idMazo]);

        return $stmt->fetchColumn() > 0;
    }

    public static function verificarCarta($idCarta, $idPartida):int{
            $link = new DB();
            $pdo = $link->getConnection();
            try{
                $stmt = $pdo->prepare("SELECT p.mazo_id FROM partida as p 
                                        INNER JOIN mazo_carta AS m ON p.mazo_id = m.mazo_id
                                        WHERE m.carta_id=:idCarta AND p.id=:idPartida AND m.estado ='en_mano'");

            $stmt->execute([':idCarta'=>$idCarta,
                            ':idPartida'=>$idPartida]);

            return ($stmt->fetchColumn());

            

            }catch(PDOException $e){
                return ['error'=>'Error al verificar carta '.$e->getMessage()];
            }
            
    }

    public static function actualizarEstado($idMazo,$idCarta){ 
        $link=new DB;
        $pdo=$link->getConnection();

        if ($idCarta==null){
            try{
                $stmt=$pdo->prepare("UPDATE mazo_carta 
                                    SET estado = :estado 
                                    WHERE mazo_id = :idMazo OR mazo_id = 1");
    
                $stmt->execute([':estado'=>'en_mano',':idMazo'=>$idMazo]);
                
            }catch (PDOException $e){
                return ['error'=>'No se pudo poner el mazo en mano'.$e->getMessage()];
            }          
        }else{
            $stmt=$pdo->prepare("UPDATE mazo_carta
                                SET estado = 'descartado'
                                WHERE mazo_id=:idMazo AND carta_id=:idCarta");
            $stmt->execute([':idMazo'=>$idMazo,
                            ':idCarta'=>$idCarta]);
        }
            
    }
    
    public static function obtenerCartas($idMazo){
        $link=new DB();
        $pdo=$link->getConnection();
    
        try{
            $stmt=$pdo->prepare("SELECT carta_id  FROM mazo_carta WHERE mazo_id = :idMazo");
            $stmt->execute([':idMazo'=>$idMazo]);
            $cartas=$stmt->fetchAll(PDO::FETCH_ASSOC);
            return $cartas;
        }catch(PDOException $e){
            return ['error '=>'No se pudo listar las cartas usadas'.$e->getMessage()];
        }
    }
        
    public static function cartasServidor():array{
      $link=new DB();
      $pdo=$link->getConnection();

      $stmt=$pdo->query("SELECT carta_id FROM mazo_carta
                         WHERE mazo_id = 1 AND estado = 'en_mano'");

      $cartasServer=$stmt->fetchAll(PDO::FETCH_COLUMN);   


      return $cartasServer;

    }

    public static function getMazosPorUsuario($usuarioId) {
        try {
            $db = new DB();
            $pdo = $db->getConnection();

            $stmt = $pdo->prepare("SELECT m.nombre AS mazo ,c.nombre AS carta
                                   FROM mazo AS m 
                                   INNER JOIN mazo_carta AS mc ON mc.mazo_id = m.id
                                   INNER JOIN carta AS c ON mc.carta_id = c.id
                                   WHERE m.usuario_id =:usuarioId");
            $stmt->execute([':usuarioId' => $usuarioId]);

            return $stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            
            return ['error' => 'Error al obtener mazos del usuartio: ' . $e->getMessage()];
        }
    }
/*
    public static function buscarCartas($nombre,$atributo) {
        
        $link = new DB(); 
        $pdo = $link->getConnection();
        
        $query = "SELECT * FROM carta WHERE 1 = 1"; // 1 = 1 -> tecnica para que siempre de true y luego se pueda concatenar con operadores LOGICOS 
        $params = [];

        if ($nombre !== null) {
            $query .= " AND nombre LIKE :nombre"; // concatena AND y LIKE (busq exacta)
            $params[':nombre'] = "%$nombre%";  // no entiendo bien esto... por que que contenga ese nombre en cualquier parte..
        }

        if ($atributo !== null) {
            $query .= " AND atributo = :atributo"; // busq. parcial
            $params[':atributo'] = $atributo;
        }

        $stmt = $pdo->prepare($query);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
        */

    public static function listarCartas($nombre = null, $atributo = null) {
        $link = new DB(); 
        $pdo = $link->getConnection();
    
        // Caso 1: Ambos parámetros??
        if ($nombre !== null && $atributo !== null) {
            $stmt = $pdo->prepare("SELECT atr.nombre AS atributo, c.ataque_nombre,c.ataque 
                                   FROM carta AS c
                                   INNER JOIN atributo AS atr ON c.atributo_id = atr.id
                                   WHERE c.nombre = :nombre AND atr.nombre = :atributo");
            $stmt->execute([':nombre' => $nombre, ':atributo' => $atributo]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    
        // Caso 2: Solo nombre
        if ($nombre !== null) {
            $stmt = $pdo->prepare("SELECT atr.nombre AS atributo, c.ataque_nombre,c.ataque 
                                   FROM carta AS c
                                   INNER JOIN atributo AS atr ON c.atributo_id = atr.id
                                   WHERE c.nombre = :nombre");
            $stmt->execute([':nombre' => $nombre]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }
    
        // Caso 3: Solo atributo
        if ($atributo !== null) {
            $stmt = $pdo->prepare("SELECT c.nombre AS Carta ,c.ataque 
                                  FROM carta AS c
                                  INNER JOIN atributo AS atr ON c.atributo_id = atr.id
                                  WHERE atr.nombre = :atributo");
            $stmt->execute([':atributo' => $atributo]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        
    
        // Caso 4: Nada enviado
        return ['error' => 'Debe enviar al menos uno de los parámetros: nombre o atributo.'];
    }

    public static function mazoUsado($idMazo) {
        try {
            $link = new DB();
            $pdo = $link->getConnection();
            // Verifica si jugo una partida y si termino
            $stmt = $pdo->prepare("SELECT COUNT(*) 
                                   FROM partida 
                                   WHERE mazo_id = :idMazo AND estado = 'finalizada'");
            $stmt->execute([':idMazo' => $idMazo]);

            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    public static function borrarMazo($idMazo) {
        try {
            $link = new DB();
            $pdo = $link->getConnection();
            //lo borra del mazo_carta y de mazo que tenga ese id
            $stmt = $pdo->prepare("DELETE FROM mazo_carta WHERE mazo_id = :idMazo");
            $stmt->execute([':idMazo' => $idMazo]);
    
            $stmt = $pdo->prepare("DELETE FROM mazo WHERE id = :idMazo");
            $stmt->execute([':idMazo' => $idMazo]);
    
            return ['mensaje' => 'Mazo eliminado correctamente.'];
        } catch (PDOException $e) {
            return ['error' => 'Error al eliminar el mazo: ' . $e->getMessage()];
        }
    }

    public static function actualizarNombreMazo($idMazo, $nuevoNombre, $usuarioId) {
        try {
 
            $link = new DB;
 
            $pdo = $link->getConnection();
 
            $ok = self::verificarMazo($idMazo,$usuarioId);

            if (!$ok){
 
                return ['error' => 'El mazo no existe o no pertenece al usuario'];
 
            } 
 
            $stmt = $pdo->prepare("UPDATE mazo SET nombre = :nuevoNombre WHERE id = :idMazo");
 
            $stmt->execute([':nuevoNombre' => $nuevoNombre, ':idMazo' => $idMazo]);
 
            return ['mensaje' => 'Nombre del mazo actualizado'];
 
        } catch (PDOException $e) {
 
            return ['error' => 'Error al actualizar: ' . $e->getMessage()];
 
        }
}

    public static function atributosMazoServer(){
        $link = new DB();
        $pdo = $link->getConnection();

        $sql=("SELECT atr.nombre AS atributo 
               FROM mazo_carta AS mc
               INNER JOIN carta AS c ON mc.carta_id = c.id
               INNER JOIN atributo as atr ON atr.id = c.atributo_id
               WHERE mc.mazo_id = 1 AND mc.estado = 'en_mano'");

        $stmt=$pdo->query($sql);

        $resultado = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return $resultado;
    }
}