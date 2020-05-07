<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require 'vendor/autoload.php';

class MyDB extends SQLite3 {
    function __construct() {
       $this->open('friends.db');
    }
}

// connect to database
$db = new MyDB();
if(!$db) {
	echo $db->lastErrorMsg();
	exit();
} 

$app = new \Slim\App;
 
// get list of friends
$app->get(
    '/friends',
    function (Request $request, Response $response, array $args) use ($db) {
        $sql = "SELECT * FROM participant";
        $ret = $db->query($sql);
        $friends = [];
        while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
            $friends[] = $friend;
        }
        return $response->withJson($friends);
    }
);

// get one friend details
$app->get(
	'/friends/{id}',
    function (Request $request, Response $response, array $args) use ($db) {
		$sql = "SELECT * FROM participant WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindValue('id', $args['id']);
		$ret = $stmt->execute();
        while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
            $friends[] = $friend;
        }
	if ($friends === NULL){
		return $response->withStatus(404)->withJson(['error' => 'This id does not exist']);
	}
        return $response->withJson($friends);
		
    }
);

// create a new friend
$app->post(
    '/friends',
    function (Request $request, Response $response, array $args) use ($db) {
        $requestData = $request->getParsedBody();
        if (!isset($requestData['name']) || !isset($requestData['surname'])) {
            return $response->withStatus(400)->withJson(['error' => 'Name and surname are required.']);
        }
        $sql = "INSERT INTO 'participant' (name, surname) VALUES (:name, :surname)";
        $stmt = $db->prepare($sql);
        $stmt->bindValue('name', $requestData['name']);
        $stmt->bindValue('surname', $requestData['surname']);
        $stmt->execute();
        $newUserId = $db->lastInsertRowID();
        return $response->withStatus(201)->withHeader('Location', "/friends/$newUserId");
    }
);

// update friend info
$app->put(
    '/friends/{id}',
    function (Request $request, Response $response, array $args) use ($db) {
        $requestData = $request->getParsedBody();
        if (!isset($requestData['name']) || !isset($requestData['surname'])) {
            return $response->withStatus(400)->withJson(['error' => 'id, Name, surname are required.']);
        }
        $sql = "UPDATE 'participant' SET name = :name, surname = :surname WHERE id = :id";
        $stmt = $db->prepare($sql);
		$stmt->bindValue('id', $args['id']);
        $stmt->bindValue('name', $requestData['name']);
        $stmt->bindValue('surname', $requestData['surname']);
        $stmt->execute();
        return $response->withStatus(201)->withHeader('Location', "/friends/$args[id]");
    }
);

// delete a friend
$app->delete(
    '/friends/{id}',
    function (Request $request, Response $response, array $args) use ($db) {
		$sql = "SELECT * FROM participant WHERE id = :id";
		$stmt = $db->prepare($sql);
		$stmt->bindValue('id', $args['id']);
		$ret = $stmt->execute();
        while ($friend = $ret->fetchArray(SQLITE3_ASSOC)) {
            $friends[] = $friend;
        }
	if ($friends === NULL){
		return $response->withStatus(404)->withJson(['error' => 'This id does not exist']);
	}
        $sql = "DELETE FROM 'participant' WHERE id = :id";
        $stmt = $db->prepare($sql);
		$stmt->bindValue('id', $args['id']);
        $stmt->execute();
        return $response->withStatus(204)->withHeader('Location', "/friends");
    }
);

$app->run();
