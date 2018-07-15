<?php
    require_once __DIR__ . '/scraping.php';
    $settings = require_once __DIR__ . '/../src/settings.php';

    $db = $settings['settings']['db'];
    $pdo = new PDO("mysql:host=" . $db['host'] . ";dbname=" . $db['dbname'], $db['user'], $db['pass']);

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    class ScrapingNewsDetail extends Scraping
    {
        function run($obj){
            print('Get content: ' . $this->path . PHP_EOL);

            // create map from element
            $map = $this->map('article');

            // Set subtitle to map
            $map->set('subtitle', '.field-name-field-sn-subtitle h2');

            // Set image to map
            $map->set('image', '.field-name-field-op-main-image [resource]', 'resource');

            // Set image fallback
            $map->set('imagefallback', 'img', 'src', [$this, 'formatImage']);

            // Set content to map and format
            $map->set('content', '.content', null, [$this, 'formatContent']);

            // Merge objects
            return (object) array_merge((array) $obj, (array) $map->getData());
        }

        function getImageUrl($url){
            if(substr($url, 0, 1) === '/'){
                $url = $this->baseUrl . $url;
            }
            return $url;
        }

        function formatImage($url, $node){
            if($node->hasAttribute('data-echo')){
                return $node->attr['data-echo'];
            }
            return $this->getImageUrl($url);
        }

        function formatContent($content, $node){
            forEach($node->find('img') as $img){
                if($img->hasAttribute('data-echo')){
                    $img->attr['src'] = $img->attr['data-echo'];
                }

                if($img->hasAttribute('height')){
                    unset($img->attr['height']);
                }

                $img->attr['src'] = $this->getImageUrl($img->attr['src']);
            }
            return $node->innertext;
        }
    }

    class ScrapingNews extends Scraping
    {
        function checkItem($url) {
            global $pdo;
            $sth = $pdo->prepare('SELECT id FROM news WHERE url=?');
            $sth->bindParam(1, $url);
            $sth->execute();
            return $sth->fetch(PDO::FETCH_ASSOC);
        }

        function run(){

            // create map from element
            $map = $this->map('#block-views-newsrail-river-main ul li');

            // Set tile to map
            $map->set('title', '.views-field-title a');

            // Set url to map
            $map->set('url', '.views-field-title a', 'href');

            // Set author to map
            $map->set('author', '.views-field-field-op-author .field-content');

            // Set date to map
            $map->set('date', '.views-field-published-at .field-content');

            // format all objects
            $map->format([$this, 'format']);

            // Extract all items
            return $map->getDataList();
        }

        function format($obj){
            global $pdo;

            // check if item exists
            if($this->checkItem($this->baseUrl . $obj->url)){
                return null;
            }

            $scrapingDetail = new ScrapingNewsDetail($this->baseUrl, $obj->url);
            $obj = $scrapingDetail->run($obj);

            $stmt = $pdo->prepare(join(' ', array(
                'INSERT INTO news',
                '(title, subtitle, author, date, url, slug, image, content)',
                'VALUES(:title, :subtitle, :author, :date, :url, :slug, :image, :content)'
            )));

            preg_match('/\/.*\/(.*)\?/', $obj->url, $matches);
            $slug = '';

            if($matches && $matches[1]){
                $slug = $matches[1];
            }

            $stmt->execute(array(
                ':title' => $obj->title,
                ':subtitle' => $obj->subtitle,
                ':author' => $obj->author ? str_replace('by ', '', $obj->author) : null,
                ':date' =>  $obj->date ? date('Y-m-d', strtotime($obj->date)) : null,
                ':url' => $this->baseUrl . $obj->url,
                ':slug' => $slug,
                ':image' => $obj->image ? $obj->image : $obj->imagefallback,
                ':content' => $obj->content
            ));

            return $obj;
        }
    }

    if (defined('STDIN')) {
        $topic = count($argv) === 3 ? $argv[2] : 'life-evolution';
        $scraping = new ScrapingNews('https://www.sciencenews.org', '/topic/'. $topic);
        $data = $scraping->run();
        print('Scraping ' . count($data) . ' news.' . PHP_EOL);
    }
?>
