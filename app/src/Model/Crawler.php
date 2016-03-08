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
    protected $crawled  = [
        '200' => [],
        '30*' => [],
        '40*' => []
    ];

    public function __construct(UrlQueue $urlQueue, array $tags = [])
    {
        $this->setUrlQueue($urlQueue);

        if (count($tags) > 0) {
            $this->setTags(array_merge($this->tags, $tags));
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

    public function setUrlQueue(UrlQueue $urlQueue)
    {
        $this->urlQueue = $urlQueue;
    }

    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    public function getUrlQueue()
    {
        return $this->urlQueue;
    }

    public function getBaseUrl()
    {
        return $this->urlQueue->getBaseUrl();
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getCrawled()
    {
        return $this->crawled;
    }

    public function getTotal()
    {
        return count($this->crawled['200']) + count($this->crawled['30*']) + count($this->crawled['40*']);
    }

    public function crawl()
    {
        $currentUrl  = $this->urlQueue->currentUrl();
        $response    = Response::parse($currentUrl, $this->context);
        $contentType = null;
        $newUrls     = 0;

        if (null !== $response->getHeader('Content-type')) {
            $contentType = $response->getHeader('Content-type');
        } else if (null !== $response->getHeader('Content-Type')) {
            $contentType = $response->getHeader('Content-Type');
        }

        if ((null !== $contentType) && (stripos($contentType, 'text/html') !== false)) {
            if (($response->getCode() == 200) && !array_key_exists($currentUrl, $this->crawled['200'])) {
                $oldError = ini_get('error_reporting');
                error_reporting(0);

                $dom = new \DOMDocument();
                $dom->recover = true;
                $dom->strictErrorChecking = false;
                $dom->loadHTML($response->getBody());

                error_reporting($oldError);

                $this->crawled['200'][$currentUrl] = $this->parseElements($dom, $currentUrl);
                if (isset($this->crawled['200'][$currentUrl]['a']) && (count($this->crawled['200'][$currentUrl]['a']) > 0)) {
                    foreach ($this->crawled['200'][$currentUrl]['a'] as $a) {
                        if (substr($a['href'], 0, strlen($this->getBaseUrl())) == $this->getBaseUrl() && (!$this->urlQueue->hasUrl($a['href']))) {
                            $this->urlQueue->addUrl($a['href'], $currentUrl);
                            $newUrls++;
                        }
                    }
                }
            } else if ($response->isRedirect()) {
                $this->crawled['30*'][] = [
                    'url'      => $currentUrl,
                    'parent'   => $this->urlQueue->getParent($currentUrl),
                    'code'     => $response->getCode(),
                    'message'  => $response->getMessage(),
                    'location' => $response->getHeader('Location')
                ];
            } else if ($response->isClientError()) {
                $this->crawled['40*'][] = [
                    'url'     => $currentUrl,
                    'parent'  => $this->urlQueue->getParent($currentUrl),
                    'code'    => $response->getCode(),
                    'message' => $response->getMessage()
                ];
            }
        }

        return [
            'content-type' => $contentType,
            'code'         => $response->getCode(),
            'message'      => $response->getMessage(),
            'newUrls'      => $newUrls
        ];
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
                                if (substr($href, 0, strlen($this->getBaseUrl())) == $this->getBaseUrl()) {
                                    $href = substr($href, strlen($this->getBaseUrl()));
                                }
                                $url = substr($currentUrl, strlen($this->getBaseUrl()));

                                if (substr($href, 0, 1) == '/') {
                                    $href = $this->getBaseUrl() . $href;
                                } else if (substr($href, 0, 2) == './') {
                                    $href = $this->getBaseUrl() . $url . substr($href, 1);
                                } else if (strpos($href, '../') !== false) {
                                    $depth  = substr_count($url, '/');
                                    $levels = substr_count($href, '../');
                                    if ($depth > $levels) {
                                        for ($i = 0; $i < $levels; $i++) {
                                            $url = substr($url, 0, strrpos($url, '/'));
                                        }
                                        $href = $this->getBaseUrl() . $url . '/' . str_replace('../', '', $href);
                                    } else {
                                        $href = $this->getBaseUrl() . '/' . str_replace('../', '', $href);
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
