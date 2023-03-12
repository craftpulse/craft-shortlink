<?php

namespace percipiolondon\shortlink\twigextensions;

use craft\elements\Entry;
use percipiolondon\shortlink\elements\ShortlinkElement;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ShortlinkTwigExtension extends AbstractExtension
{
    public function getName(): string
    {
        return 'shortlink';
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('getShortlink', [$this, 'getShortlink']),
        ];
    }

    public function getShortlink(int $entryId): ?string
    {
        $shortlink = ShortlinkElement::findOne(['ownerId' => $entryId]);

        if ($shortlink) {
            return $shortlink->shortlinkUri;
        }

        return null;
    }
}
