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
use craft\errors\ExitException;
use craft\db\Query;

use craftpulse\shortlink\records\RouteRecord;
use craftpulse\shortlink\Shortlink;
use craftpulse\shortlink\helpers\UrlHelper;

use Illuminate\Support\Collection;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;

/**
 * Redirects Service
 *
 * @author    CraftPulse
 * @package   Shortlink
 */
class Redirects extends Component
{
    // Public Methods
    // =========================================================================
    /**
     * @return void
     * @throws Exception
     * @throws ExitException
     * @throws InvalidConfigException
     * @throws InvalidRouteException|\yii\base\ExitException
     */
    public function handleRedirect(): void
    {
        $request = Craft::$app->getRequest();

        $host = urldecode($request->getHostInfo());
        $path = urldecode($request->getUrl());

        $domain = $this->checkDomains($host);

        // the domain should be part of the domains in our settings before we do anything
        if($domain->isNotEmpty()) {

            $domainProperties = $domain->first();
            // Make sure we cast this as bool
            $preserveQuerystring = (bool)$domainProperties['preserveQuerystring'];

            // @TODO add multisite support
            if(!$preserveQuerystring) {
                $path = UrlHelper::stripQuerystring($path);
            }

            // Redirect if we find a route match, otherwise let Craft handle it.
            $redirect = $this->matchRoute($host, $path);
            // If $redirect is null, try again without the Querystring
            if (!$redirect) {
                $redirect = $this->matchRoute($host, $path, false);
            }

            // Go to the homepage if someone hits the root of the shortlink domain and we didn't resolve a route
            if ($path === '/') {
                $this->doHomepageRedirect($host);
            }

            $this->doRedirect($domainProperties, $redirect);
        }
    }

    // Private Methods
    // =========================================================================

    /**
     * @param string $host
     * @return void
     * @throws Exception
     * @throws InvalidRouteException
     */
    private function doHomepageRedirect(string $host): void
    {
        $destination = UrlHelper::siteUrl('/', null, null, null);
        Craft::$app->getResponse()->redirect($destination)->send();
    }

    /**
     * @param array $host
     * @param array|null $redirect
     * @return void
     * @throws Exception
     * @throws ExitException
     * @throws InvalidConfigException|\yii\base\ExitException
     */
    private function doRedirect(array $host, ?array $redirect): void
    {
        $response = Craft::$app->getResponse();

        if (!is_null($redirect)) {
            $destination = $redirect['destination'];

            // We do not have the URL of our primary site yet
            // @TODO add multisite support (need to add SiteID support)
            $destination = UrlHelper::siteUrl($destination, null, null, $redirect['siteId']);

            if((bool)$host['preserveQuerystring']) {
                $request = Craft::$app->getRequest();
                $queryString = UrlHelper::combineQueryStringsFromUrls($destination, $request->getUrl());

                if (!empty($queryString)) {
                    $destination = strtok($destination, '?') . '?' . $queryString;
                }
            }

            // @TODO if httpCode === 410 --> Craft error page needs to render

            // Sanitize the URL
            $destination = UrlHelper::sanitizeUrl($destination);

            // Redirect the request
            $response->redirect($destination, (int) $redirect['httpCode']);

            try {
                Craft::$app->end();
            } catch (ExitException $error) {
                Craft::error($error->getMessage(), __METHOD__);
            }
        }
    }

    /**
     * @param string $host
     * @param string $path
     * @param bool $useQuerystring
     * @param int|null $siteId
     * @return array|null
     */
    private function matchRoute(string $host, string $path, bool $useQuerystring = true, ?int $siteId = null): ?array {

        // Strip the QueryString when useQueryString is false
        if (!$useQuerystring) {
            $path = UrlHelper::stripQuerystring($path);
        }

        // @TODO - move to our "Routes" Service
        if (is_null($siteId)) {
            $currentSite = Craft::$app->getSites()->currentSite;
            if ($currentSite) {
                $siteId = $currentSite->id;
            } else {
                $primarySite = Craft::$app->getSites()->primarySite;
                $siteId = $primarySite->id;
            }
        }

        $redirect = $this->getExactRoute($host, $path, $siteId);

        if ($redirect) {
            // @TODO Increment the hitCount and update lastUsed
            Shortlink::$plugin->statistics->increment($host, $path);

            return $redirect;
        }

        $redirects = $this->getRegExRoutes(null, $siteId, 'regex', true);
        $redirect = $this->resolveRegExRoute($path, $redirects, $siteId);

        // Return the redirect
        return $redirect;
    }

    /**
     * @param string $host
     * @param string $path
     * @param string $siteId
     * @return array|null
     */
    private function getExactRoute(string $host, string $path, string $siteId): ?array
    {
        // @TODO add caching for performance
        // @TODO offload this to our element queries
        // Search route elements
        $originCondition = ['origin' => Shortlink::$plugin->routes->sanitizeDomain($host)];
        $routeCondition = ['matchType' => 'exact'];
        $pathCondition = ['uriPattern' => UrlHelper::cleanPath($path)];

        $query = (new Query())
            ->from(RouteRecord::tableName())
            ->where(
                [
                    'and',
                    $originCondition,
                    $routeCondition,
                    $pathCondition,
                ]
            )
            ->limit(1);

        $result = $query->one();

        if($result) {
            $result['siteId'] = $siteId;
        }

        return $result;
    }

    /**
     * @param int|null $limit
     * @param int|null $siteId
     * @param string $type
     * @return array
     */
    protected function getRegExRoutes(int $limit = null, int $siteId = null, string $type, bool $enabledOnly = false): array
    {
        // Query the db table
        $query = (new Query())
            ->from([RouteRecord::tableName()])
            ->orderBy('matchType ASC');

        if ($limit) {
            $query->limit($limit);
        }

        $query->andWhere(['matchType' => $type]);

        return $query->all();
    }

    protected function resolveRegExRoute(string $path, array $redirects, $siteId = null): ?array
    {
        // Iterate through our array
        foreach ($redirects as $redirect) {
            $regexMatch = '`' . $redirect['uriPattern'] . '`i';

            try {
                if(preg_match($regexMatch, $path) === 1) {
                    Shortlink::$plugin->statistics->increment($redirect['origin'], $redirect['uriPattern']);
                    $redirect['destination'] = preg_replace($regexMatch, $redirect['destination'], $path);
                }

                // @TODO add multisite functionality
                if ($redirect) {
                    $redirect['siteId'] = $siteId;
                }
                return $redirect;

            } catch (\Exception $error) {
                Craft::error('Invalid Regex Route: ' . $regexMatch, __METHOD__);
            }
        }

        // @TODO add multisite functionality


        return null;
    }

    /**
     * @param string $host
     * @return array|null
     */
    private function generateHostNeedle(string $host): ?array
    {
        if (!empty($host)) {
            return [
                $host,
                $host[-1] === '/' ? $host = rtrim($host, '/') : $host .= '/',
            ];
        } else {
            return null;
        }
    }

    /**
     * @param string $host
     * @return Collection|null
     */
    private function checkDomains(string $host): ?Collection
    {
        // Create a needle, just to make sure we search with trailing slash too
        $needle = $this->generateHostNeedle($host);

        if (!$needle) return null;

        // Fetch the urls from our settings (which is an array)
        return Collection::make(Shortlink::$plugin->settings->domainNames)->filter(function (array $value) use ($needle) {
            return in_array($needle, $value['domain']);
        });
    }
}
