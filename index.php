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

$queryTheDb = function () use ($db) {
    return $db->query('SELECT id, title FROM album limit 50')
        ->then(function (\React\MySQL\QueryResult $queryResult) {
            $users = json_encode($queryResult->resultRows);
            

            return new Response(200, ['Content-type' => 'application/json'], $users);
        });
};

$server = new Server([$loggingMiddleware, $queryTheDb]);
$socket = new \React\Socket\Server('127.0.0.1:8000', $loop);
$server->listen($socket);

echo 'Listening on ' . str_replace('tcp:', 'http:', $socket->getAddress()) . PHP_EOL;
$loop->run();