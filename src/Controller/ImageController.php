<?php

namespace App\Controller;

use GuzzleHttp\Client;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ImageController extends AbstractController
{


     #[Route("/")]


    public function index(Request $request): Response
    {
        $images = [];
        $totalSize = 0;

        if ($request->isMethod('POST')) {
            $url = $request->request->get('url');

            if ($url) {
                $client = new Client();
                $response = $client->request('GET', $url);
                $html = $response->getBody()->getContents();

                $crawler = new Crawler($html);
                $imageNodes = $crawler->filter('img');

                foreach ($imageNodes as $imageNode) {
                    $src = $imageNode->getAttribute('src');
                    if (!filter_var($src, FILTER_VALIDATE_URL)) {
                        $src = $this->getAbsoluteUrl($src, $url);
                    }
                    $imageSize = $this->getImageSize($src);
                    $images[] = [
                        'src' => $src,
                        'size' => $imageSize,
                    ];
                    $totalSize += $imageSize;
                }
            }
        }

        return $this->render('image/index.html.twig', [
            'images' => $images,
            'totalSize' => $totalSize,
        ]);
    }

    private function getAbsoluteUrl(string $src, string $baseUrl): string
    {
        if (parse_url($src, PHP_URL_HOST)) {
            return $src;
        }
        $base = rtrim($baseUrl, '/');
        return $base . '/' . ltrim($src, '/');
    }

    private function getImageSize(string $url): int
    {
        try {
            $client = new Client();
            $response = $client->head($url);
            return $response->getHeaderLine('Content-Length') ?: 0;
        } catch (\Exception $e) {
            return 0;
        }
    }
}
