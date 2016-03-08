<?php

namespace PopSpider\Model;

use Pop\Http\Response;

class Crawler
{
    /**
     * @var UrlQueue
     */
    protected $urlQueue = null;
    protected $context  = [];
    protected $tags     = ['title', 'meta', 'a', 'img', 'h1', 'h2', 'h3'];

    public function __construct(UrlQueue $urlQueue, array $tags = [])
    {
        $this->urlQueue = $urlQueue;

        if (count($tags) > 0) {
            $this->tags = (array_merge($this->tags, $tags));
        }

        $ua = (isset($_SERVER['HTTP_USER_AGENT'])) ?
            $_SERVER['HTTP_USER_AGENT'] :
            'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:16.0) Gecko/20100101 Firefox/16.0';

        $this->context = [
            'method'     => 'GET',
            'header'     => "Accept-language: en\r\n" . "User-Agent: " . $ua . "\r\n",
            'user_agent' => $ua
        ];
    }

    public function getUrlQueue()
    {
        return $this->urlQueue;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function crawl()
    {
        return [];
    }

}
