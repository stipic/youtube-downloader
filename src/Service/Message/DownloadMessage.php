<?php
namespace App\Service\Message;

use Symfony\Component\Serializer\Annotation\Groups;

class DownloadMessage
{
    /**
     * @Groups({"data"})
     */
    private $_youtubeInfo;

    /**
     * @Groups({"data"})
     */
    private $_url;

    /**
     * @Groups({"data"})
     */
    private $_videoId;

    /**
     * @Groups({"data"})
     */
    private $_location;

    /**
     * @Groups({"data"})
     */
    private $_userId;

    public function __construct(
        $url = null, 
        $videoId = null, 
        $youtubeInfo = null, 
        $location = null,
        $userId = null
    )
    {
        $this->_youtubeInfo = $youtubeInfo;
        $this->_url = $url;
        $this->_videoId = $videoId;
        $this->_location = $location;
        $this->_userId = $userId;
    }
    
    public function getYoutubeInfo()
    {
        return $this->_youtubeInfo;
    }

    public function getUrl()
    {
        return $this->_url;
    }

    public function getVideoId()
    {
        return $this->_videoId;
    }

    public function getLocation()
    {
        return $this->_location;
    }

    public function getUserId()
    {
        return $this->_userId;
    }
}
