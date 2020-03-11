<?php
namespace App\Service\Downloader;

use \Madcoda\Youtube\Youtube;

class YoutubeAPI
{
    private $_youtube;

    public function __construct()
    {
        $apiKey = $_ENV['YT_API_KEY'];
        $this->_youtube = new Youtube([
            'key' => $apiKey,
            'referer' => '212.91.114.82'
        ]);
    }

    public function getClient()
    {
        return $this->_youtube;
    }

    public function getId($url)
    {
        preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $url, $match);
        return isset($match[1]) ? $match[1] : false;
    }

    public function getPlaylistId($url)
    {
        parse_str(parse_url($url, PHP_URL_QUERY ), $ytQuery);
        return isset($ytQuery['list']) ? $ytQuery['list'] : false;
    }
}