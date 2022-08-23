<?php

declare(strict_types=1);

namespace App\Twig;

use App\Service\OwnWordpress;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class WordpressExtension extends AbstractExtension
{
    private $wordpress;

    public function __construct(OwnWordpress $wp)
    {
        $this->wordpress = $wp;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getOwnWpMenu', [$this->wordpress, 'getOwnMenu']),
        ];
    }
}
