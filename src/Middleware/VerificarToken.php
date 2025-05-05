<?php

namespace App\Middleware;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response as SlimResponse;

class VerificarToken implements MiddlewareInterface
{
    public function process(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Token no enviado']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }

        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $decoded = JWT::decode($token, new Key("mi_clave_super_secreta", 'HS256'));
            $request = $request->withAttribute('usuario', $decoded->usuario);
            $request = $request->withAttribute('id', $decoded->id);
            return $handler->handle($request);
        }/* catch (\Exception $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode(['error' => 'Token inválido: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
        }*/
        catch (ExpiredException $e) {
            return $this->unauthorizedResponse('Token expirado');
        } catch (SignatureInvalidException $e) {
            return $this->unauthorizedResponse('Firma del token inválida');
        } catch (\UnexpectedValueException $e) {
            return $this->unauthorizedResponse('Token inválido');
        }
    }

    private function unauthorizedResponse(string $mensaje): Response
{
    $response = new SlimResponse();
    $response->getBody()->write(json_encode(['error' => $mensaje]));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
}
}