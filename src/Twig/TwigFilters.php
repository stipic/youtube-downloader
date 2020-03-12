<?php

namespace App\Twig;

use App\Entity\Queue;
use App\Repository\QueueRepository;
use App\Service\MessageHandler\DownloadMessageHandler;
use Twig\TwigFilter;
use Twig\Environment;
use Twig\Extension\AbstractExtension;


class TwigFilters extends AbstractExtension
{
    private $_queueRepo;

    public function __construct(
        QueueRepository $queueRepository
    )
    {
        $this->_queueRepo = $queueRepository;
    }

    public function getFilters()
    {
        return [
            new TwigFilter(
                'formatSeconds',
                [
                    $this,
                    'TWIG_formatSeconds',
                ],
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFilter(
                'onHoldOrder',
                [
                    $this,
                    'TWIG_onHoldOrder',
                ],
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFilter(
                'queueGetTitle',
                [
                    $this,
                    'TWIG_queueGetTitle',
                ],
                [
                    'is_safe' => ['html'],
                ]
            ),
        ];
    }

    public function TWIG_queueGetTitle($ytInfo)
    {
        $ytInfo = json_decode($ytInfo);
        return isset($ytInfo->snippet->title) ? $ytInfo->snippet->title : '';
    }

    public function TWIG_formatSeconds($seconds)
    {
        return gmdate("i:s", $seconds);
    }

    public function TWIG_onHoldOrder($queueId)
    {
        $onHoldBefore = $this->_queueRepo->findQueueNumberForQueue($queueId);
        return (int) $onHoldBefore['num'];
    }
}