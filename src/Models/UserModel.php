<?php

namespace App\Models;
use App\Models\DB;
use PDO;
require_once __DIR__ . '/DB.php';

class UserModel{
    

    public static function registrar($nombre,$usuario,$password){
        

        try{
            $link= new DB();
            $pdo = $link->getConnection();

            $stmt = $pdo->prepare("SELECT id FROM usuario WHERE usuario = :usuario");
            $stmt->execute([':usuario' => $usuario]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
             return ['error' => 'El nombre de usuario ya esta en uso'];
            }

    
        $stmt = $pdo->prepare("INSERT INTO usuario (nombre,usuario, password) VALUES (:nombre,:usuario, :password)");
        $stmt->execute([
            ':nombre'=> $nombre,
            ':usuario' => $usuario,
            ':password' => $password
        ]);

        return ['Mensaje'=> 'Usuario registrado correctamente']; 

        } catch (PDOException $e) {
            return ['error' => 'Error al registrar el usuario: ' . $e->getMessage()]; //preguntar el codigo de error
            }

        }

    public static function mostrar(){
            $link= new DB();
            $pdo = $link->getConnection();

            $sql=("SELECT * FROM usuario");

            $stmt = $pdo->prepare($sql);
            $stmt->execute();
            $resultado =$stmt->fetch(\PDO::FETCH_ASSOC);

            return $resultado;
            
        }

    public static function validarUsuario($usuario,$password){
            try{
            $link= new DB();
            $pdo = $link->getConnection();

            $sql=("SELECT password,id FROM usuario WHERE usuario=:usuario"); //buscar usuario
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':usuario'=>$usuario]);
            $resultado =$stmt->fetch(PDO::FETCH_ASSOC); 
            if (($resultado) && ($resultado['password']==$password)){
                return $resultado['id'];

            } else {
              return false;
              }
            }catch(PDOException $e){
                return ['error' => 'Error al validar usuario: ' . $e->getMessage()];
            }
    }


    public static function actualizarToken($usuario,$token,$vencimiento){
            try{
                $link= new DB();
                $pdo = $link->getConnection();
                
                $stmt = $pdo->prepare("UPDATE usuario SET token = :token, vencimiento_token = :vencimiento WHERE usuario = :usuario");
                $stmt->execute([':usuario'=> $usuario ,':token'=>$token, ':vencimiento'=>$vencimiento]);
            return true;
            }catch (PDOException $e){
                return false;
            }

        }

    public static function mostrarUsuario($usuario){
            try{
                $link= new DB();
                $pdo = $link->getConnection();
                $sql=("SELECT nombre FROM usuario WHERE usuario=:usuario"); 
                $stmt = $pdo->prepare($sql);
                $stmt->execute([':usuario'=>$usuario]);
                $resultado =$stmt->fetch(\PDO::FETCH_ASSOC);//como pusimos use PDO no hace falta la barra \ antes

                return $resultado;

            }catch(PDOException $e){
                return ['error' => 'Error al buscar usuario ' . $e->getMessage()];
            }
        }

    public static function actualizarUsuario($usuario, $datos){
            try{
                $link= new DB();
                $pdo = $link->getConnection();
                $stmt = $pdo->prepare("UPDATE usuario SET nombre = :nombre, password = :password WHERE usuario = :usuario");
                $stmt->execute([':usuario'=>$usuario,
                                ':nombre' => $datos['nombre'],
                                ':password' => $datos['password']]);
                return ['Mensaje'=> 'Datos actulizados correctamente'];
            }catch(PDOException $e){
                return ['error' => 'Error al actualizar los datos: ' . $e->getMessage()];
            }
        }


        
}