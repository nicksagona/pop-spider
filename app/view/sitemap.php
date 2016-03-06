<?php echo '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL; ?>
<urlset
    xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9
            http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">
<!-- created with pop-spider https://github.com/nicksagona/pop-spider -->
<?php if (count($urls) > 0): ?>
<?php foreach ($urls['200'] as $url => $value): ?>
    <url>
        <loc><?php echo $url; ?></loc>
        <changefreq>monthly</changefreq>
        <priority><?php echo number_format(round((($depth - (substr_count($url, '/') - 2)) / $depth), 2), 2); ?></priority>
    </url>
<?php endforeach; ?>
<?php endif; ?>
</urlset>
