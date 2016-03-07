<?php

namespace PopSpider\Model;

use Pop\Console\Console;
use Pop\Http\Response;

class Crawler
{

    /**
     * @var Console
     */
    protected $console = null;
    protected $baseUrl = null;
    protected $dir     = null;
    protected $depth   = 0;
    protected $tags    = ['title', 'meta', 'a', 'img', 'h1', 'h2', 'h3'];
    protected $crawled = [
        '200' => [],
        '30*' => [],
        '404' => []
    ];

    public function __construct($url, $dir = 'output', array $tags = [])
    {
        $this->setBaseUrl($url);
        $this->setDir($dir);
        if (count($tags) > 0) {
            $this->setTags(array_merge($this->tags, $tags));
        }
    }

    public function setBaseUrl($url)
    {
        $url = str_replace(
            ['%3A', '%2F', '%23', '%3F', '%3D', '%25', '%2B'],
            [':', '/', '#', '?', '=', '%', '+'],
            rawurlencode($url)
        );

        if (substr($url, -1) == '/') {
            $url = substr($url, 0, -1);
        }

        $this->baseUrl = $url;
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

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function getDir()
    {
        return $this->dir;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getCrawled()
    {
        return $this->crawled;
    }

    public function getDepth()
    {
        return $this->depth;
    }

    public function getTotal()
    {
        return count($this->crawled['200']) + count($this->crawled['30*']) + count($this->crawled['404']);
    }

    public function prepare(Console $console)
    {
        $this->console = $console;
    }

    /**
     * Crawl the base URL
     *
     * @param  string $currentUrl
     * @param  array  $context
     * @param  string $parent
     * @return array
     */
    public function crawl($currentUrl, $context, $parent = null)
    {
        $response    = Response::parse($currentUrl, $context);
        $contentType = null;

        if (null !== $response->getHeader('Content-type')) {
            $contentType = $response->getHeader('Content-type');
        } else if (null !== $response->getHeader('Content-Type')) {
            $contentType = $response->getHeader('Content-Type');
        }

        if ((null !== $contentType) && (stripos($contentType, 'text/html') !== false)) {
            $this->console->write($currentUrl, false);
            $this->console->send();
            if (($response->getCode() == 200) && !array_key_exists($currentUrl, $this->crawled['200'])) {
                $oldError = ini_get('error_reporting');
                error_reporting(0);

                $dom = new \DOMDocument();
                $dom->recover = true;
                $dom->strictErrorChecking = false;
                $dom->loadHTML($response->getBody());

                error_reporting($oldError);

                if ((substr_count($currentUrl, '/') - 2) > $this->depth) {
                    $this->depth = (substr_count($currentUrl, '/') - 2);
                }

                $this->crawled['200'][$currentUrl] = $this->parseElements($dom, $currentUrl);
                $this->console->write($this->console->colorize('200 OK', Console::BOLD_GREEN));
                $this->console->send();
                if (isset($this->crawled['200'][$currentUrl]['a']) && (count($this->crawled['200'][$currentUrl]['a']) > 0)) {
                    foreach ($this->crawled['200'][$currentUrl]['a'] as $a) {
                        if ((substr($a['href'], 0, strlen($this->baseUrl)) == $this->baseUrl) &&
                            !array_key_exists($a['href'], $this->crawled['200'])) {
                            $this->crawl($a['href'], $context, $currentUrl);
                        }
                    }
                }
            } else if (($response->isRedirect()) && !array_key_exists($currentUrl, $this->crawled['30*'])) {
                $this->crawled['30*'][$currentUrl] = $response->getCode() . ' ' . $response->getMessage();
                $this->console->write($this->console->colorize($response->getCode() . ' ' . $response->getMessage(), Console::BOLD_CYAN));
                $this->console->send();
            } else if (($response->getCode() == 404) && !array_key_exists($currentUrl, $this->crawled['404'])) {
                $this->crawled['404'][$currentUrl] = $parent;
                $this->console->write($this->console->colorize('404 NOT FOUND', Console::BOLD_RED));
                $this->console->send();
            }
        }
    }

    /**
     * Parse the document's elements
     *
     * @param  \DOMDocument $dom
     * @param  string       $currentUrl
     * @return array
     */
    protected function parseElements(\DOMDocument $dom, $currentUrl)
    {
        $elements = [];

        foreach ($this->tags as $tag) {
            switch ($tag) {
                case 'title':
                    $title = $dom->getElementsByTagName('title');

                    $elements['title'] = (null !== $title->item(0)) ?
                        $title->item(0)->nodeValue : null;
                    break;

                case 'meta':
                    $meta = $dom->getElementsByTagName('meta');

                    if (null !== $meta->item(0)) {
                        foreach ($meta as $m) {
                            if ($m->hasAttribute('name') && $m->hasAttribute('content')) {
                                if (!isset($elements['meta'])) {
                                    $elements['meta'] = [];
                                }
                                $elements['meta'][] = [
                                    'name'    => $m->getAttribute('name'),
                                    'content' => $m->getAttribute('content')
                                ];
                            }
                        }
                    }
                    break;

                case 'a':
                    $anchors = $dom->getElementsByTagName('a');

                    if (null !== $anchors->item(0)) {
                        foreach ($anchors as $a) {
                            if (!isset($elements['a'])) {
                                $elements['a'] = [];
                            }

                            $href = ($a->hasAttribute('href') ? $a->getAttribute('href') : null);

                            if ((null !== $href) && ($this->isValidHref($href))) {
                                if (substr($href, 0, strlen($this->baseUrl)) == $this->baseUrl) {
                                    $href = substr($href, strlen($this->baseUrl));
                                }
                                $url = substr($currentUrl, strlen($this->baseUrl));

                                if (substr($href, 0, 1) == '/') {
                                    $href = $this->baseUrl . $href;
                                } else if (substr($href, 0, 2) == './') {
                                    $href = $this->baseUrl . $url . substr($href, 1);
                                } else if (strpos($href, '../') !== false) {
                                    $depth  = substr_count($url, '/');
                                    $levels = substr_count($href, '../');
                                    if ($depth > $levels) {
                                        for ($i = 0; $i < $levels; $i++) {
                                            $url = substr($url, 0, strrpos($url, '/'));
                                        }
                                        $href = $this->baseUrl . $url . '/' . str_replace('../', '', $href);
                                    } else {
                                        $href = $this->baseUrl . '/' . str_replace('../', '', $href);
                                    }
                                }
                            }

                            if ($a->nodeValue != '') {
                                $value = $a->nodeValue;
                            } else {
                                $imgs  = $a->getElementsByTagName('img');
                                $value = (null !== $imgs->item(0)) ? '[image]' : null;
                            }

                            $elements['a'][] = array(
                                'href'  => $href,
                                'value' => $value,
                                'title' => ($a->hasAttribute('title') ? $a->getAttribute('title') : null),
                                'name'  => ($a->hasAttribute('name') ? $a->getAttribute('name') : null),
                                'rel'   => ($a->hasAttribute('rel') ? $a->getAttribute('rel') : null)
                            );
                        }
                    }
                    break;

                case 'img':
                    $images = $dom->getElementsByTagName('img');

                    if (null !== $images->item(0)) {
                        foreach ($images as $image) {
                            if (!isset($elements['img'])) {
                                $elements['img'] = [];
                            }
                            $elements['img'][] = [
                                'src'   => ($image->hasAttribute('src') ? $image->getAttribute('src') : null),
                                'alt'   => ($image->hasAttribute('alt') ? $image->getAttribute('alt') : null),
                                'title' => ($image->hasAttribute('title') ? $image->getAttribute('title') : null),
                            ];
                        }
                    }
                    break;

                default:
                    $element = $dom->getElementsByTagName($tag);

                    if (null !== $element->item(0)) {
                        foreach ($element as $e) {
                            $elements[$tag][] = $e->nodeValue;
                        }
                    }
            }
        }

        return $elements;
    }

    /**
     * Check if an HREF is valid
     *
     * @param  string $href
     * @return boolean
     */
    protected function isValidHref($href)
    {
        return (($href != '') &&
            (substr($href, 0, 1) != '#') &&
            (substr($href, 0, 1) != '?') &&
            (substr(strtolower($href), 0, 7) != 'mailto:') &&
            (substr(strtolower($href), 0, 4) != 'tel:'));
    }

}
