<?php
    require_once __DIR__ . '/vendor/autoload.php';
    require_once __DIR__ . '/scraping.php';

    $path = '/topic/life-evolution';
    $baseUrl = 'https://www.sciencenews.org';
    $scrap = new Scraping($baseUrl . $path);

    $map = $scrap->map('#block-views-newsrail-river-main ul li');

    $map->set('title', '.views-field-title a');
    $map->set('url', '.views-field-title a', 'href',
    function($url)
    {
        global $baseUrl;
        return $baseUrl . $url;
    });

    $map->format(function($obj){
        $scrapDetail = new Scraping($obj->url);
        $mapDetail = $scrapDetail->map('article');

        if($mapDetail === null){
            return null;
        }

        $mapDetail->set('subtitle', '.field-name-field-sn-subtitle h2');
        $mapDetail->set('image', '.field-name-field-op-main-image [resource]', 'resource');
        $mapDetail->set('content', '.field-name-body [itemprop=description]', null,
        function($content, $node)
        {
            global $baseUrl;
            forEach($node->find('img') as $img){
                if($img->hasAttribute('data-echo')){
                    $img->attr['src'] = $img->attr['data-echo'];
                }
            }
            return $node->innertext;
        });

        $obj = (object) array_merge((array) $obj, (array) $mapDetail->data());
        return $obj;
    });
    echo "<pre>";
    print_r($map->dataList(20));
?>
