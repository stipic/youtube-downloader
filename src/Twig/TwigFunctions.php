<?php

namespace App\Twig;

use App\Entity\Queue;
use Twig\TwigFunction;
use Twig\Extension\AbstractExtension;
use App\Service\MessageHandler\DownloadMessageHandler;

class TwigFunctions extends AbstractExtension
{
    public function getFunctions()
    {
        return [

            new TwigFunction(
                'runIndexToDesc',
                [
                    $this,
                    'TWIG_runIndexToDesc',
                ]
            ),
        ];
    }

    public function TWIG_runIndexToDesc(Queue $queue)
    {
        return DownloadMessageHandler::runIndexToDesc($queue);
    }
}
