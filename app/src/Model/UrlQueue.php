<?php

namespace PopSpider\Model;

class UrlQueue
{

    protected $baseUrl = null;
    protected $urls    = [];
    protected $parents = [];
    protected $index   = -1;

    public function __construct($url = null)
    {
        if (null !== $url) {
            $this->addUrl($url);
        }
    }

    public function addUrl($url, $parent = null)
    {
        $url = str_replace(
            ['%3A', '%2F', '%23', '%3F', '%3D', '%25', '%2B'],
            [':', '/', '#', '?', '=', '%', '+'],
            rawurlencode($url)
        );

        if (count($this->urls) == 0) {
            $this->baseUrl = $url;
            if (substr($this->baseUrl, -1) == '/') {
                $this->baseUrl = substr($this->baseUrl, 0, -1);
            }
        }

        if (null !== $parent) {
            $this->parents[$url] = $parent;
        }

        $this->urls[] = $url;
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getParent($url)
    {
        return (isset($this->parents[$url])) ?
            $this->parents[$url] : null;
    }

    public function currentUrl()
    {
        return (isset($this->urls[$this->index])) ?
            $this->urls[$this->index] : null;
    }

    public function nextUrl()
    {
        $this->index++;
        return (isset($this->urls[$this->index])) ?
            $this->urls[$this->index] : false;
    }

    public function hasUrl($url)
    {
        return (in_array($url, $this->urls));
    }

}