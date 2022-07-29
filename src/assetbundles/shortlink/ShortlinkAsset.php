<?php

namespace percipiolondon\shortlink\assetbundles\shortlink;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 *
 * @author    percipiolondon
 * @package   Shortlink
 * @since     1.0.0
 *
 */
class ShortlinkAsset extends AssetBundle
{
    // Public Methods
    // ==============

    /**
     * Initialises the bundle
     */
    public function init(): void
    {
        $this->sourcePath = "@percipiolondon/timeloop/web/assets/dist";

        $this->depends = [
            CpAsset::class,
        ];

        parent::init();
    }
}