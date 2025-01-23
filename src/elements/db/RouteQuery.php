<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\elements\db;

use Craft;
use craft\elements\db\ElementQuery;

/**
 * Route query
 *
 * @author      CraftPulse
 * @package     Shortlink
 * @since       1.0.0
 */
class RouteQuery extends ElementQuery
{
    protected function beforePrepare(): bool
    {
        $this->joinElementTable('shortlink_routes');

        $this->query->select([
            'shortlink_routes.origin',
            'shortlink_routes.uriPattern',
            'shortlink_routes.destination',
            'shortlink_routes.matchType',
            'shortlink_routes.httpCode',
            'shortlink_routes.hitCount',
            'shortlink_routes.lastUsed',
        ]);

        return parent::beforePrepare();
    }
}
