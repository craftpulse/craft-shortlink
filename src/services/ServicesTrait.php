<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\services;

use craftpulse\shortlink\services\Redirects;
use craftpulse\shortlink\services\Routes;
use craftpulse\shortlink\services\Statistics;

/**
 * @author    CraftPulse
 * @package   Shortlink
 * @since     1.0.0
 *
 * @property Redirects $redirects
 * @property Routes $routes
 * @property Statistics $statistics
 */
trait ServicesTrait
{
    public static function config(): array
    {
        return [
            'components' => [
                'redirects' => Redirects::class,
                'routes' => Routes::class,
                'statistics' => Statistics::class,
            ]
        ];
    }

    // Public Methods
    // =========================================================================

    /**
     * Returns the redirects service
     *
     * @return Redirects The redirects service
     * @throws InvalidConfigException
     */
    public function getRedirects(): Redirects
    {
        return $this->get('redirects');
    }

    /**
     * Returns the routes service
     *
     * @return Routes The routes service
     * @throws InvalidConfigException
     */
    public function getRoutes(): Routes
    {
        return $this->get('routes');
    }

    /**
     * Returns the routes service
     *
     * @return Routes The routes service
     * @throws InvalidConfigException
     */
    public function getStatistics(): Statistics
    {
        return $this->get('statistics');
    }
}
