<?php
/**
 * Pop Spider Web SEO Tool
 *
 * @link       https://github.com/nicksagona/pop-spider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 */

/**
 * @namespace
 */
namespace PopSpider\Model;

/**
 * Crawler model class
 *
 * @category   PopSpider
 * @package    PopSpider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 * @version    4.0.2
 */
class Crawler
{
    /**
     * @var UrlQueue
     */
    protected $urlQueue = null;
    protected $context  = [];
    protected $tags     = ['title', 'meta', 'a', 'img', 'h1', 'h2', 'h3'];
    protected $parsed   = [];
    protected $crawled  = [
        '200'    => [],
        '30*'    => [],
        '40*'    => [],
        'images' => [],
        'other'  => []
    ];

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
            'http' => [
                'method'     => 'GET',
                'header'     => "Accept: */*\r\n" . "Accept-language: en\r\n" . "User-Agent: " . $ua . "\r\n",
                'user_agent' => $ua
            ]
        ];
    }

    public function getBaseUrl()
    {
        return $this->urlQueue->getBaseUrl();
    }

    public function getUrlQueue()
    {
        return $this->urlQueue;
    }

    public function getTags()
    {
        return $this->tags;
    }

    public function getTotal()
    {
        return count($this->crawled['200']) + count($this->crawled['30*']) + count($this->crawled['40*']) +
            count($this->crawled['images']) + count($this->crawled['other']);
    }

    public function getTotalHtml()
    {
        return count($this->crawled['200']) + count($this->crawled['30*']) + count($this->crawled['40*']);
    }

    public function getTotalImages()
    {
        return count($this->crawled['images']);
    }

    public function getTotalOther()
    {
        return count($this->crawled['other']);
    }

    public function getTotalOk()
    {
        return count($this->crawled['200']);
    }

    public function getTotalRedirects()
    {
        return count($this->crawled['30*']);
    }

    public function getTotalErrors()
    {
        return count($this->crawled['40*']);
    }

    public function getCrawled()
    {
        return $this->crawled;
    }

    public function crawl($saveDir = null)
    {
        $result = [];
        $this->urlQueue->parseCurrentUrl($this->context, $this->tags, $saveDir);

        if ($this->urlQueue->current()->getCode() == 200) {
            if ((stripos($this->urlQueue->current()->getContentType(), 'text/html') !== false) &&
                !array_key_exists((string)$this->urlQueue->current(), $this->crawled['200'])) {
                $this->crawled['200'][(string)$this->urlQueue->current()] = $this->urlQueue->current()->getElements();
                if ($this->urlQueue->current()->hasChildren()) {
                    foreach ($this->urlQueue->current()->getChildren() as $child) {
                        if (!$this->urlQueue->hasUrl($child)) {
                            $this->urlQueue[] = new Url($child, (string)$this->urlQueue->current());
                        }
                    }
                }
            } else if ((stripos($this->urlQueue->current()->getContentType(), 'image/') !== false) && !array_key_exists((string)$this->urlQueue->current(), $this->crawled['images'])) {
                $this->crawled['images'][(string)$this->urlQueue->current()] = [
                    'url'          => (string)$this->urlQueue->current(),
                    'content-type' => $this->urlQueue->current()->getContentType(),
                    'parent'       => $this->urlQueue->current()->getParent()
                ];
            } else if (!array_key_exists((string)$this->urlQueue->current(), $this->crawled['other'])){
                $this->crawled['other'][(string)$this->urlQueue->current()] = [
                    'url'          => (string)$this->urlQueue->current(),
                    'content-type' => $this->urlQueue->current()->getContentType(),
                    'parent'       => $this->urlQueue->current()->getParent()
                ];
            }
        } else if (($this->urlQueue->current()->isRedirect()) && !array_key_exists((string)$this->urlQueue->current(), $this->crawled['30*'])) {
            $this->crawled['30*'][(string)$this->urlQueue->current()] = [
                'url'          => (string)$this->urlQueue->current(),
                'content-type' => $this->urlQueue->current()->getContentType(),
                'parent'       => $this->urlQueue->current()->getParent(),
                'code'         => $this->urlQueue->current()->getCode(),
                'message'      => $this->urlQueue->current()->getMessage(),
                'location'     => $this->urlQueue->current()->response()->getHeader('Location')
            ];
        } else if (($this->urlQueue->current()->isError()) && !array_key_exists((string)$this->urlQueue->current(), $this->crawled['40*'])) {
            $this->crawled['40*'][(string)$this->urlQueue->current()] = [
                'url'          => (string)$this->urlQueue->current(),
                'content-type' => $this->urlQueue->current()->getContentType(),
                'parent'       => $this->urlQueue->current()->getParent(),
                'code'         => $this->urlQueue->current()->getCode(),
                'message'      => $this->urlQueue->current()->getMessage()
            ];
        }

        if (($this->urlQueue->current()->isParsed()) && !in_array((string)$this->urlQueue->current(), $this->parsed)) {
            $this->parsed[] = (string)$this->urlQueue->current();
            $result         = [
                'url'          => (string)$this->urlQueue->current(),
                'content-type' => $this->urlQueue->current()->getContentType(),
                'code'         => $this->urlQueue->current()->getCode(),
                'message'      => $this->urlQueue->current()->getMessage()
            ];
        }

        return $result;
    }

}
