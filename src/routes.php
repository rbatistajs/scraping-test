<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

$app->get('/', function (Request $request, Response $response, array $args) {
    $sth = $this->db->prepare("SELECT title, author, date, image, slug FROM news ORDER BY date");
    $sth->execute();
    $news = $sth->fetchAll();
    return $this->renderer->render(
        $response, 'index.phtml', array(
            'view' => 'news.phtml',
            'news' => $news
        )
    );
});

$app->get('/{slug}', function (Request $request, Response $response, array $args) {
    $sth = $this->db->prepare("SELECT * FROM news WHERE slug=?");
    $sth->bindParam(1, $args['slug']);
    $sth->execute();
    $item = $sth->fetch();
    return $this->renderer->render(
        $response, 'index.phtml', array(
            'view' => 'detail.phtml',
            'item' => $item
        )
    );
});
