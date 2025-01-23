<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\helpers\Db;

use craftpulse\shortlink\Shortlink;
use craftpulse\shortlink\elements\Route;
use craftpulse\shortlink\helpers\UrlHelper;
use craftpulse\shortlink\records\RouteRecord;

use DateTime;
use yii\db\Exception;

/**
 * Statistics Service
 *
 * @author    CraftPulse
 * @package   Shortlink
 * @since     1.0.0
 */
class Statistics extends Component
{
    /**
     * Increment the hitCount and update lastCount
     */
    public function increment(string $host, string $path): void
    {
        if($path) {
            // @TODO move to element queries
            $patternCondition = ['uriPattern' => UrlHelper::cleanPath($path)];
            $originCondition = ['origin' => Shortlink::$plugin->routes->sanitizeDomain($host)];

            $result = (new Query())
                ->from(RouteRecord::tableName())
                ->where(
                    [
                        'and',
                        $originCondition,
                        $patternCondition,
                    ]
                )->one();

            $result['hitCount'] = (int) $result['hitCount'] + 1;
            $result['lastUsed'] = Db::prepareDateForDb(new DateTime());

            $this->saveRouteElement($result);
        }
    }

    private function saveRouteElement(array $result): bool
    {
        $route = new Route($result);
        return Craft::$app->elements->saveElement($route);
    }
}
