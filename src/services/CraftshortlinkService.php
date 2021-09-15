<?php
/**
 * craft-shortlink plugin for Craft CMS 3.x
 *
 * A plugin to use your own subdomain as a url shortener
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 Percipio
 */

namespace percipioglobal\craftshortlink\services;

use percipioglobal\craftshortlink\Craftshortlink;

use Craft;
use craft\base\Component;

/**
 * @author    Percipio
 * @package   Craftshortlink
 * @since     1.0.0
 */
class CraftshortlinkService extends Component
{
    // Public Methods
    // =========================================================================

    /*
     * @return mixed
     */
    public function exampleService()
    {
        $result = 'something';
        // Check our Plugin's settings for `someAttribute`
        if (Craftshortlink::$plugin->getSettings()->someAttribute) {
        }

        return $result;
    }
}
