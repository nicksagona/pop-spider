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

use Pop\Http;

/**
 * Url model class
 *
 * @category   PopSpider
 * @package    PopSpider
 * @author     Nick Sagona, III <dev@nolainteractive.com>
 * @copyright  Copyright (c) 2012-2023 NOLA Interactive, LLC. (http://www.nolainteractive.com)
 * @license    https://github.com/nicksagona/pop-spider/blob/master/LICENSE.TXT     New BSD License
 * @version    4.0.0
 */
class Url
{

    /**
     * @var Http\Server\Response
     */
    protected $response    = null;
    protected $contentType = null;
    protected $url         = '';
    protected $elements    = [];
    protected $parent      = null;
    protected $children    = [];
    protected $allowed     = [
        'bmp'    => 'image/bmp',
        'csv'    => 'text/csv',
        'doc'    => 'application/msword',
        'docx'   => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'gif'    => 'image/gif',
        'jpe'    => 'image/jpeg',
        'jpg'    => 'image/jpeg',
        'jpeg'   => 'image/jpeg',
        'log'    => 'text/plain',
        'pdf'    => 'application/pdf',
        'png'    => 'image/png',
        'ppt'    => 'application/vnd.ms-powerpoint',
        'pptx'   => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'svg'    => 'image/svg+xml',
        'tif'    => 'image/tiff',
        'tiff'   => 'image/tiff',
        'tsv'    => 'text/tsv',
        'txt'    => 'text/plain',
        'xls'    => 'application/vnd.ms-excel',
        'xlsx'   => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml'    => 'application/xml',
        'zip'    => 'application/zip'
    ];

    public function __construct($url, $parent = null)
    {
        $url = str_replace(
            ['%3A', '%2F', '%23', '%3F', '%3D', '%25', '%2B'],
            [':', '/', '#', '?', '=', '%', '+'],
            rawurlencode($url)
        );

        $this->url    = $url;
        $this->parent = $parent;
    }

    public function response()
    {
        return $this->response;
    }

    public function getCode()
    {
        return (null !== $this->response) ? $this->response->getCode() : null;
    }

    public function getMessage()
    {
        return (null !== $this->response) ? $this->response->getMessage() : null;
    }

    public function getContentType()
    {
        return $this->contentType;
    }

    public function isSuccess()
    {
        return (null !== $this->response) ? $this->response->isSuccess() : false;
    }

    public function isRedirect()
    {
        return (null !== $this->response) ? $this->response->isRedirect() : false;
    }

    public function isError()
    {
        return (null !== $this->response) ? $this->response->isClientError() : false;
    }

    public function parse($baseUrl, $context, array $tags, $saveDir = null)
    {
        $dom            = null;
        $domain         = parse_url($baseUrl)['host'];
        $this->response = Http\Parser::parseResponseFromUri($this->url, 'GET', 'r', $context);


        if (null !== $this->response->getHeader('Content-type')) {
            $this->contentType = $this->response->getHeader('Content-type');
        } else if (null !== $this->response->getHeader('Content-Type')) {
            $this->contentType = $this->response->getHeader('Content-Type');
        }

        if ((null !== $this->contentType) && (stripos($this->contentType, 'text/html') !== false)) {
            if ($this->response->getCode() == 200) {
                $oldError = ini_get('error_reporting');
                error_reporting(0);

                $responseBody = $this->response->getBody();

                $dom = new \DOMDocument();
                $dom->recover = true;
                $dom->strictErrorChecking = false;
                $dom->loadHTML($responseBody);

                if (null !== $saveDir) {
                    $filename = str_replace($baseUrl, '', urldecode($this->url));
                    if ($filename == '/') {
                        $filename = 'index.html';
                    } else {
                        if (stripos($filename, $domain) !== false) {
                            $filename = substr($filename, (stripos($filename, $domain) + strlen($domain) + 1));
                        }
                        if (substr($filename, -1) == '/') {
                            $filename = substr($filename, 0, -1);
                        }
                        $filename = str_replace('/', '_', $filename);
                        if (empty($filename)) {
                            $filename = 'index.html';
                        }
                        if (stripos($filename, '.html') === false) {
                            $filename .= '.html';
                        }
                    }

                    file_put_contents($saveDir . DIRECTORY_SEPARATOR . $filename, $responseBody);
                }

                error_reporting($oldError);
            }
        } else if (null !== $saveDir) {
            if (in_array(strtolower((string)$this->contentType->getValue()), $this->allowed)) {
                $fileName = urldecode(basename($this->url));
                if (strpos($this->url, $baseUrl) !== false) {
                    $location = str_replace([$baseUrl, $fileName], ['', ''], urldecode($this->url));
                } else {
                    $location = str_replace($fileName, '', urldecode($this->url));
                    $location = substr($location, (stripos($location, $domain) + strlen($domain) + 1));
                }
                $folders     = explode('/', $location);
                $locationDir = $saveDir;

                foreach ($folders as $folder) {
                    if (!empty($folder)) {
                        $locationDir = $locationDir . DIRECTORY_SEPARATOR . $folder;
                        if (!file_exists($locationDir)) {
                            mkdir($locationDir);
                        }
                    }
                }

                file_put_contents($locationDir . DIRECTORY_SEPARATOR . $fileName, $this->response->getBody());
            }
        }

        if (null !== $dom) {
            foreach ($tags as $tag) {
                switch ($tag) {
                    case 'title':
                        $title = $dom->getElementsByTagName('title');

                        $this->elements['title'] = (null !== $title->item(0)) ?
                            trim($title->item(0)->nodeValue) : null;
                        break;

                    case 'meta':
                        $meta = $dom->getElementsByTagName('meta');

                        if (null !== $meta->item(0)) {
                            foreach ($meta as $m) {
                                if ($m->hasAttribute('name') && $m->hasAttribute('content')) {
                                    if (!isset($this->elements['meta'])) {
                                        $this->elements['meta'] = [];
                                    }
                                    $this->elements['meta'][] = [
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
                                if (!isset($this->elements['a'])) {
                                    $this->elements['a'] = [];
                                }

                                $href = ($a->hasAttribute('href') ? $a->getAttribute('href') : null);

                                if ((null !== $href) && ($this->isValidHref($href))) {
                                    if (substr($href, 0, strlen($baseUrl)) == $baseUrl) {
                                        $href = substr($href, strlen($baseUrl));
                                    }
                                    $url = substr($this->url, strlen($baseUrl));

                                    if (substr($href, 0, 1) == '/') {
                                        $href = $baseUrl . $href;
                                    } else if (substr($href, 0, 2) == './') {
                                        $href = $baseUrl . $url . substr($href, 1);
                                    } else if (strpos($href, '../') !== false) {
                                        $depth  = substr_count($url, '/');
                                        $levels = substr_count($href, '../');
                                        if ($depth > $levels) {
                                            for ($i = 0; $i < $levels; $i++) {
                                                $url = substr($url, 0, strrpos($url, '/'));
                                            }
                                            $href = $baseUrl . $url . '/' . str_replace('../', '', $href);
                                        } else {
                                            $href = $baseUrl . '/' . str_replace('../', '', $href);
                                        }
                                    } else if ((stripos($href, $domain) === false) && (substr(strtolower($href), 0, 4) != 'http')) {
                                        $href = $baseUrl . '/' . $href;
                                    }

                                    //if ((substr($href, 0, strlen($baseUrl)) == $baseUrl) &&
                                    if ((stripos($href, $domain) !== false) && !in_array($href, $this->children) && ($this->url != $href)) {
                                        $this->children[] = $href;
                                    }
                                }

                                if ($a->nodeValue != '') {
                                    $value = $a->nodeValue;
                                } else {
                                    $imgs  = $a->getElementsByTagName('img');
                                    $value = (null !== $imgs->item(0)) ? '[image]' : null;
                                }

                                $this->elements['a'][] = array(
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
                                if (!isset($this->elements['img'])) {
                                    $this->elements['img'] = [];
                                }
                                $this->elements['img'][] = [
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
                                $this->elements[$tag][] = $e->nodeValue;
                            }
                        }
                }
            }
        }

        return $this->elements;
    }

    public function getElements()
    {
        return $this->elements;
    }

    public function hasChildren()
    {
        return (count($this->children) > 0);
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getParent()
    {
        return $this->parent;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function isParsed()
    {
        return (null !== $this->response);
    }

    public function __toString()
    {
        return $this->url;
    }

    protected function isValidHref($href)
    {
        return (($href != '') &&
            (substr($href, 0, 1) != '#') &&
            (substr($href, 0, 1) != '?') &&
            (substr(strtolower($href), 0, 7) != 'mailto:') &&
            (substr(strtolower($href), 0, 4) != 'tel:'));
    }

}