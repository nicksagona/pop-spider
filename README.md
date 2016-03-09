pop-spider
==========

``pop-spider`` is a simple CLI-driven web spider for SEO analysis that uses components from the Pop PHP Framework.
It parses SEO-pertinent data from a website and produces a HTML-based report of what was parsed as well as an
sitemap.xml file.

RELEASE INFORMATION
-------------------
pop-spider 2.0.1 Release  
March 9, 2016

INSTALLATION
------------

    $ composer create-project --no-dev nicksagona/pop-spider pop-spider

QUICK USE
---------

    $ cd pop-spider/script
    $ ./spider crawl http://www.mydomain.com/

OVERVIEW
--------
By default, the spider parses the following elements and their
SEO-pertinent attributes:

* title
* meta
    + name
    + content
* a
    + href
    + title
    + rel
    + name
    + value
* img
    + src
    + title
    + alt
* h1
* h2
* h3

You can parse additional tags via the `--tags=` option.

    $ ./spider help				                Display this help screen.
    $ ./spider crawl <url> [--dir=] [--tags=]	Crawl the URL.
    
    The optional [--dir=] parameter allows you to set the output directory for the results report.
    The optional [--tags=] parameter allows you to set additional tags to scan for in a comma-separated list.
    
    Example:
    
    $ ./spider crawl http://www.mydomain.com/ --dir=seo-report --tags=b,u

