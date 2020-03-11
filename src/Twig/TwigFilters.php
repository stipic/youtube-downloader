<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\Environment;
use Twig\Extension\AbstractExtension;


class TwigFilters extends AbstractExtension
{
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
            )
        ];
    }

    public function TWIG_formatSeconds($seconds)
    {
        return gmdate("i:s", $seconds);
    }
}