<?php

namespace percipiolondon\shortlink\assetbundles\shortlink;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;
use craft\web\assets\vue\VueAsset;

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
        $this->sourcePath = "@percipiolondon/shortlink/web/assets/dist";

        $this->depends = [
            CpAsset::class,
            VueAsset::class,
        ];

        $this->js = [
        ];

        $this->css = [
        ];

        parent::init();
    }
}
