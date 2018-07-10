<?php

use Sunra\PhpSimple\HtmlDomParser;

class ScrapingQuery
{
    public $name = '';
    public $query = '';
    public $attr = null;
    private $callback = null;
    private $list = false;

    function __construct($name, $query, $attr = null, $callback, $list = false)
    {
        $this->name = $name;
        $this->query = $query;
        $this->attr = $attr;
        $this->callback = $callback;
        $this->list = $list;
    }

    function formatValue($value, $node){
        if($this->callback){
            return call_user_func($this->callback, trim($value), $node);
        }
        return trim($value);
    }

    function getValue($node){
        if($this->attr){
            if($this->attr === "html"){
                $value = $node->innertext;
            }else{
                $value = $node->attr[$this->attr];
            }
            return $this->formatValue($value, $node);
        }

        return $this->formatValue($node->plaintext, $node);
    }

    function getValueList($nodes){
        $result = array();

        foreach ($nodes as $node) {
            $result[] = $this->getValue($node);
        }

        return $result;
    }

    function exec($node){
        $nodes = $node->find($this->query);

        if(count($nodes) === 0){
            return '';
        }

        if($this->list){
            return $this->getValueList($nodes);
        }

        return $this->getValue($nodes[0]);
    }
}

class ScrapingMap
{
    private $nodes = array();
    private $fields = array();
    private $format = null;

    function __construct($nodes)
    {
        $this->nodes = $nodes;
    }

    function format($format)
    {
        $this->format = $format;
    }

    function set($name, $query, $attr = null, $callback = null)
    {
        $this->fields[] = new ScrapingQuery($name, $query, $attr, $callback);
    }

    function setList($name, $query, $attr = null, $callback = null)
    {
        $this->fields[] = new ScrapingQuery($name, $query, $attr, $callback, true);
    }

    function dataList($limit = 0)
    {
        $result = array();
        foreach ($this->nodes as $node) {
            $obj = $this->processNode($node);
            if($obj){
                $result[] = $obj;
            }

            if($limit && count($result) === $limit){
                break;
            }
        }

        return $result;
    }


    function data()
    {
        return $this->processNode($this->nodes[0]);
    }

    function processNode($node)
    {
        $obj = new StdClass();

        foreach ($this->fields as $query) {
            $obj->{$query->name} = $query->exec($node);
        }

        if($this->format){
            $obj = call_user_func($this->format, $obj);
        }

        return $obj;
    }
}

class Scraping
{
    private $dom = null;

    function __construct($url)
    {
        $this->dom = HtmlDomParser::file_get_html($url);
    }

    function map($str)
    {
        if($this->dom){
            $nodes = $this->dom->find($str);
            return new ScrapingMap($nodes);
        }

        return null;
    }
}

?>
