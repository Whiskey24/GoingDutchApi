<?php
require 'vendor/autoload.php';
require 'Db/Db.php';

//require 'RedBeanPHP/rb.php';

// set up database connection
//R::setup('mysql:host=' . $dbConfig['host'] . ';dbname=' . $dbConfig['name'], $dbConfig['user'], $dbConfig['pass']);
//R::freeze(true);

$app = new \Slim\App();

$auth = new \Middleware\Authenticate();

$app->get('/version', function ($request, $response, $args) {
    $response->write("Going Dutch API v0.1");
    return $response;
})->add($auth);

$app->get('/groups', function ($request, $response, $args) {
    $member = new \Models\Member();
    $response->write($member->getGroups(\Middleware\Authenticate::$requestUid));
    $newResponse = $response->withHeader('Content-type', 'application/json');
    return $newResponse;
})->add($auth);

$app->get('/hello[/{name}]', function ($request, $response, $args) {
    $response->write("Hello, " . $args['name']);
    return $response;
})->setArgument('name', 'World!');

/*// handle GET requests for /articles
$app->get('/articles', function ($request, $response, $args) {
//$app->get('/', function () use ($app) {
    // query database for all articles
    $articles = R::find('articles');

    // send response header for JSON content type
    // $app->response()->header('Content-Type', 'application/json');

    // return JSON-encoded response body with query results
    //echo json_encode(R::exportAll($articles));

    $response->write(json_encode(R::exportAll($articles)));
    return $response;
});*/


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();