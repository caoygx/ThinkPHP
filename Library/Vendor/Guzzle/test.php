<?php
 Vendor('Guzzle.autoload');
        //require '/www/web/video/ThinkPHP/Library/Vendor/Guzzle/autoload.php';
        $client = new \GuzzleHttp\Client();
        var_dump($client);exit;
        $res = $client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');
        echo $res->getStatusCode();
// 200
        echo $res->getHeaderLine('content-type');
// 'application/json; charset=utf8'
        echo $res->getBody();
        exit;
// '{"id": 1420053, "name": "guzzle", ...}'

// Send an asynchronous request.
        $request = new \GuzzleHttp\Psr7\Request('GET', 'http://httpbin.org');
        $promise = $client->sendAsync($request)->then(function ($response) {
            echo 'I completed! ' . $response->getBody();
        });
        $promise->wait();