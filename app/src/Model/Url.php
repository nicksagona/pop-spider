<?php

namespace PopSpider\Model;

class Url
{

    protected $url = '';

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function __toString()
    {
        return $this->url;
    }

}