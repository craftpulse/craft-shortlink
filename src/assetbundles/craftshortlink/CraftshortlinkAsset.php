<?php
/**
 * craft-shortlink plugin for Craft CMS 3.x
 *
 * A plugin to use your own subdomain as a url shortener
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 Percipio
 */

namespace percipioglobal\craftshortlink\assetbundles\craftshortlink;

use Craft;
use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Percipio
 * @package   Craftshortlink
 * @since     1.0.0
 */
class CraftshortlinkAsset extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@percipioglobal/craftshortlink/assetbundles/craftshortlink/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/Craftshortlink.js',
        ];

        $this->css = [
            'css/Craftshortlink.css',
        ];

        parent::init();
    }
}
