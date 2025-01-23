<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\models;

use Craft;
use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;
/**
 * Class SettingsModel
 *
 * @author      CraftPulse
 * @package     Shortlink
 * @since       1.0.0
 */
class SettingsModel extends Model
{
    /**
     * The domains to use when checking for shortlink redirects.
     *
     * [
     *     [
     *         'domain' => '',
     *         'preserveQuerystring' => true,
     *     ],
     * ]
     *
     * @var array[]
     */
    public array $domainNames = [
        [
            'domain' => '',
            'preserveQuerystring' => true,
        ],
    ];
}
