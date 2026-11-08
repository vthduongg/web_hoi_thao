<?php

declare(strict_types=1);

namespace App\Controllers\Public;

use App\Controllers\Controller;
use App\Core\Response;
use App\Repositories\PageRepository;
use App\Services\PublicPageContext;

/** /sitemap.xml (trang công khai của hội thảo hiện hành) và /robots.txt cho máy tìm kiếm. */
final class SitemapController extends Controller
{
    public function sitemap(): Response
    {
        $conference = $this->services->conferences->current() ?? $this->notFound();
        $paths = ['/'];
        foreach ((new PageRepository($this->services->db))->publicPagesForConference($conference->id) as $page) {
            $paths[] = '/' . $page->slug;
        }
        $paths[] = '/past-conferences';

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($paths as $path) {
            $xml .= '  <url><loc>' . htmlspecialchars(PublicPageContext::absoluteUrl($path), ENT_XML1) . "</loc></url>\n";
        }

        return Response::text($xml . "</urlset>\n", 'application/xml; charset=UTF-8');
    }

    public function robots(): Response
    {
        return Response::text("User-agent: *\nDisallow: /admin\nSitemap: " . PublicPageContext::absoluteUrl('/sitemap.xml') . "\n");
    }
}
