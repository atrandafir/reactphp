<?php

$params_file=__DIR__ . '/custom-params.php';

if (!file_exists($params_file)) {
  echo 'Please create a params file first';
  exit;
}

$params = require($params_file);

require_once __DIR__ . '/vendor/autoload.php';

use React\Http\Response;
use React\Http\Server;
use React\MySQL\Factory;
use Psr\Http\Message\ServerRequestInterface;


$loop = \React\EventLoop\Factory::create();

$loggingMiddleware = function(ServerRequestInterface $request, callable $next) {
    echo date('Y-m-d H:i:s') . ' ' . $request->getMethod() . ' ' . $request->getUri() . PHP_EOL;
    return $next($request);
};

$factory = new Factory($loop);
$db = $factory->createLazyConnection("{$params['db_user']}:{$params['db_pwd']}@{$params['db_host']}/{$params['db_name']}");

$queryTheDb = function (ServerRequestInterface $request) use ($db) {
    return $db->query('SELECT id, title FROM album limit 50')
        ->then(function (\React\MySQL\QueryResult $queryResult) {
            $users = json_encode($queryResult->resultRows);
            
            if ($request->getUri()=='http://139.162.172.192:8000/loaderio-7c563bb9293e541f6851af3a9039250d.txt') {
              return new Response(200, ['Content-type' => 'text/plain'], 'loaderio-7c563bb9293e541f6851af3a9039250d');
            }

            return new Response(200, ['Content-type' => 'application/json'], $users);
        });
};

$server = new Server([$loggingMiddleware, $queryTheDb]);
$socket = new \React\Socket\Server($params['listen_str'], $loop);
$server->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
$loop->run();