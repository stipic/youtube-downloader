<?php
namespace App\Service\Downloader;

use \Madcoda\Youtube\Youtube;

class YoutubeAPI
{
    private $_youtube;

    private $_videos = [];

    public function __construct()
    {
        $apiKey = $_ENV['YT_API_KEY'];
        $this->_youtube = new Youtube([
            'key' => $apiKey,
            'referer' => '212.91.114.82'
        ]);
    }

    public function getPlaylistVideos($playlistId, $pageToken = null)
    {
        $config = [
            'playlistId' => $playlistId,
            'part' => 'id, snippet, contentDetails, status',
            'maxResults' => 50 // to je max
        ];
        
        if($pageToken != null)
        {
            $config['pageToken'] = $pageToken;
        }

        $playlist = $this->_youtube->getPlaylistItemsByPlaylistIdAdvanced($config, TRUE);

        $this->_videos = array_merge($playlist['results'], $this->_videos);

        if(count($this->_videos) < $playlist['info']['totalResults'])
        {
            $nextPageToken = $playlist['info']['nextPageToken'];
            $this->getPlaylistVideos($playlistId, $nextPageToken);
        }

        //@todo AKO uleti neka OGROMNA playlista od 1000+ videa treba handleati to da ne spamam yt api zbog quote limita.

        return $this->_videos;
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