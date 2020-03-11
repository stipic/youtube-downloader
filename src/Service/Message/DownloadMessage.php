<?php
namespace App\Service\Message;

use Symfony\Component\Serializer\Annotation\Groups;

class DownloadMessage
{
    /**
     * @Groups({"data"})
     */
    private $_queueId;

    public function __construct(
        $queueId = null
    )
    {
        $this->_queueId = $queueId;
    }

    public function getQueueId()
    {
        return $this->_queueId;
    }
}
