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
 * Url queue model class
 *
 * @category   PopSpider
 * @package    PopSpider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 * @version    4.0.2
 */
class UrlQueue implements \Iterator, \ArrayAccess
{

    protected $position   = -1;
    protected $baseUrl    = null;
    protected $urls       = [];
    protected $urlStrings = [];

    public function __construct($url)
    {
        $url = str_replace(
            ['%3A', '%2F', '%23', '%3F', '%3D', '%25', '%2B'],
            [':', '/', '#', '?', '=', '%', '+'],
            rawurlencode($url)
        );

        $info = pathinfo($url);

        if (!empty($info['extension']) && (substr($url, 0 - (strlen($info['extension']) + 1)) == '.' . $info['extension'])) {
            $baseUrl = substr($url, 0, strrpos($url, '/'));
        } else {
            $baseUrl = $url;
        }

        $this->urls[]       = new Url($url);
        $this->urlStrings[] = urldecode($url);
        $this->baseUrl      = $baseUrl;
        if (substr($this->baseUrl, -1) == '/') {
            $this->baseUrl = substr($this->baseUrl, 0, -1);
        }
    }

    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    public function parseCurrentUrl($context, $tags, $saveDir = null)
    {
        if ((null !== $this->current() && (!$this->current()->isParsed()))) {
            $this->current()->parse($this->baseUrl, $context, $tags, $saveDir);
        }
    }

    public function hasUrl($url)
    {
//        $check = $this->urls;
//        foreach ($check as $u) {
//            if ((string)$u == (string)$url) {
//                return true;
//            }
//        }
//        return false;
        return in_array((string)$url, $this->urlStrings);
    }

    public function hasParsedUrl($url)
    {
        $check = $this->urls;
        foreach ($check as $u) {
            if (((string)$u == (string)$url) && ($u->isParsed()) && ($u->getCode() == 200)) {
                return true;
            }
        }
        return false;
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        $i = ($this->position < 0) ? 0 : $this->position;
        return (isset($this->urls[$i])) ? $this->urls[$i] : null;
    }

    public function key()
    {
        return $this->position;
    }

    public function prev()
    {
        if ($this->position > 0) {
            --$this->position;
        }
        return $this->current();
    }

    public function next()
    {
        ++$this->position;
        while ($this->hasParsedUrl($this->current())) {
            ++$this->position;
        }
        return $this->current();
    }

    public function valid()
    {
        return isset($this->urls[$this->position]);
    }

    public function offsetSet($offset, $value)
    {
        if (null === $offset) {
            $this->urls[] = $value;
        } else {
            $this->urls[$offset] = $value;
        }
        $this->urlStrings[] = ($value instanceof Url) ? urldecode((string)$value) : urldecode($value);
    }

    public function offsetExists($offset)
    {
        return isset($this->urls[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->urls[$offset]);
    }

    public function offsetGet($offset)
    {
        return isset($this->urls[$offset]) ? $this->urls[$offset] : null;
    }

    public function __set($name, $value)
    {
        $this->offsetSet($name, $value);
    }

    public function __get($name)
    {
        return $this->offsetGet($name);
    }

    public function __unset($name)
    {
        $this->offsetUnset($name);
    }

    public function __isset($name)
    {
        return $this->offsetExists($name);
    }

}
