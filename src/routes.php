<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    $sth = $this->db->prepare("SELECT title, image, slug FROM news ORDER BY date");
    $sth->execute();
    $news = $sth->fetchAll();
    return $this->renderer->render(
        $response, 'index.phtml', array('news' => $news)
    );
});

$app->get('/{slug}', function (Request $request, Response $response, array $args) {
    $sth = $this->db->prepare("SELECT * FROM news WHERE url=?");
    $sth->bindParam(1, $args['slug']);
    $sth->execute();
    $item = $sth->fetch();
    return $this->renderer->render(
        $response, 'detail.phtml', array('item' => $item)
    );
});
