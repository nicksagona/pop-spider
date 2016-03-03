<?php

namespace PopSpider\Model;

class Crawler
{

    protected $url  = null;
    protected $dir  = null;
    protected $tags = ['title', 'meta', 'a', 'img', 'h1', 'h2', 'h3'];

    public function __construct($url, $dir = 'output', array $tags = [])
    {
        $this->setUrl($url);
        $this->setDir($dir);
        if (count($tags) > 0) {
            $this->setTags(array_merge($this->tags, $tags));
        }
    }

    public function setUrl($url)
    {
        $url = str_replace(
            ['%3A', '%2F', '%23', '%3F', '%3D', '%25', '%2B'],
            [':', '/', '#', '?', '=', '%', '+'],
            rawurlencode($url)
        );

        $this->url = $url;
        return $this;
    }

    public function setDir($dir)
    {
        $this->dir = $dir;
        return $this;
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function run()
    {

    }

}
