<?php

namespace percipiolondon\shortlink\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\Db;
use craft\helpers\ElementHelper;

use Illuminate\Support\Collection;
use percipiolondon\shortlink\Shortlink;
use percipiolondon\shortlink\helpers\UrlHelper;
use percipiolondon\shortlink\models\ShortlinkModel;
use percipiolondon\shortlink\elements\ShortlinkElement;

use DateTime;
use Exception;
use Throwable;
use yii\base\ExitException;
use yii\base\InvalidConfigException;
use yii\base\InvalidRouteException;
use yii\caching\TagDependency;
use yii\web\NotFoundHttpException;

/**
 * @author    percipiolondon
 * @package   Shortlink
 * @since     4.0.0
 */
class ShortlinkService extends Component
{

    // Constants
    // ==================

    public const CACHE_KEY = 'shortlink_redirect_';
    public const GLOBAL_ROUTES_CACHE_TAG = 'shortlink_routes';

    // Protected Properties
    // =========================================================================

    /**
     * @var null|array
     */
    protected ?array $cachedRedirects = null;

    public function getShortlinkById(int $id): ?ShortlinkElement
    {
        /* @noinspection PhpIncompatibleReturnTypeInspection */
        return Craft::$app->getElements()->getElementById($id, ShortlinkElement::class);
    }

    /**
     * @throws ExitException
     * @throws Exception
     */
    public function generateShortlink(): array|string|null
    {
        $settings = Shortlink::$plugin->getSettings();
        $allowedChars = implode('', $this->_defineFormat($settings->casing, $settings->alphaNumeric));
        $length = random_int($settings->minLength, $settings->maxLength);
        $shortlink = $this->_generateUri($allowedChars, $length);
        $uris = $this->_fetchUris();

        if ($uris->isEmpty()) {
            return $shortlink;
        } else {
            do {
                $shortlink = $this->_generateUri($allowedChars, $length);
            } while ($uris->contains(function($element) use ($shortlink) {
                return $element['shortlinkUri'] === $shortlink;
            }));
        }

        return $shortlink;
    }

    /**
     * Handle shortlink redirects
     *
     * @throws InvalidConfigException|Exception
     */
    public function handleRedirect(): void
    {
        $request = Craft::$app->getRequest();
        // Only handle site requests, no live previews or console requests
        if ($request->getIsSiteRequest() && !$request->getIsLivePreview() && !$request->getIsConsoleRequest() && !$request->getIsCpRequest()) {
            $host = urldecode($request->getHostInfo());
            $path = urldecode($request->getUrl());
            $url = urldecode($request->getAbsoluteUrl());

            $baseUrls = [];
            $shortlinkUrls = [];
            $sites = Craft::$app->getSites()->allSites;
            $shortlinks = Shortlink::$settings->shortlinkUrls;

            // host returns including trailing slash, make sure we check for that too if it's not site in the SITE_URL
            $needle = [
                $host,
                $host . '/',
            ];
            // add all baseUrls to an array in case of multisite
            foreach($sites as $site) {
                $baseUrls[] = $site->baseUrl;
            }

            // add all shortlinkUrls to an array to check if it's set
            foreach($shortlinks as $shortlink) {
                $shortlinkUrls[] = $shortlink['shortlinkUrl'];
            }

            // check if our hostname is not one of the existing Craft sites and a url from our settings, if so redirect
            if(!array_intersect($needle, $baseUrls) && array_intersect($needle, $shortlinkUrls)) {

                // check if query string should be stripped or not
                if (!Shortlink::$settings->redirectQueryString) {
                    $path = UrlHelper::stripQueryString($path);
                    $url = UrlHelper::stripQueryString($url);
                }

                // Redirect if we find a match, otherwise let Craft handle it
                $redirect = $this->findShortlinkMatch($path);
                $this->doRedirect($url, $path, $host, $redirect);
            }
        }
    }

    /**
     * @param string $path
     * @param null $siteId
     *
     * @return array|null
     */
    public function findShortlinkMatch(string $path, $siteId = null): ?array
    {
        // Search for it in the cache
        $redirect = $this->getRedirectFromCache($path);
        if($redirect) {
            $this->saveRedirectToCache($path, $redirect);

            return $redirect;
        }

        // Search shortlink elements
        $redirect = $this->getShortlinkRedirect($path, $siteId);

        if($redirect) {
            $this->saveRedirectToCache($path, $redirect);
            return $redirect;
        }

        // Search without the querystring
        $redirect = $this->getShortlinkRedirect(UrlHelper::stripQueryString($path), $siteId);
        if($redirect) {
            $this->saveRedirectToCache($path, $redirect);
            return $redirect;
        }

        // Search the static shortlinks
        $redirect = $this->getStaticShortlinkRedirect(UrlHelper::stripQueryString($path), $siteId);
        if($redirect) {
            $this->saveRedirectToCache($path, $redirect);
            return $redirect;
        }

        return null;
    }

    /**
     * @param string $path
     * @param null $siteId
     * @return mixed
     */
    public function getShortlinkRedirect(string $path, $siteId = null): mixed
    {
        // strip the forward slash of our path
        $path = ltrim($path, '/');
        $query = (new Query)
            ->from('{{%shortlink_routes}}')
            ->where([
                'and',
                ['shortlinkUri' => $path]
            ])
            ->andWhere(['not', ['ownerId' => null]])
            ->limit(1);

        return $query->one();
    }

    /**
     * @param string $path
     * @param null $siteId
     * @return mixed
     */
    public function getStaticShortlinkRedirect(string $path, $siteId = null): mixed
    {
        // strip the forward slash of our path
        $path = ltrim($path, '/');
        $query = (new Query)
            ->from('{{%shortlink_routes}}')
            ->where([
                'and',
                ['shortlinkUri' => $path],
                ['ownerId' => null],
                ['ownerRevisionId' => null]
            ])
            ->limit(1);

        return $query->one();
    }

    /**
     * @param Entry $entry
     * @throws ElementNotFoundException
     * @throws MissingComponentException
     * @throws Throwable
     */
    public function onAfterSaveEntry(Entry $entry): void
    {
        $request = Craft::$app->getRequest();

        $shortlink = [
            'shortlinkUri' => $request->getBodyParam('shortlink-uri'),
            'redirectType' => $request->getBodyParam('shortlink-redirect-type'),
        ];

        $this->saveShortlink($entry, $shortlink);
    }

    /**
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws MissingComponentException
     * @throws Exception
     */
    public function saveShortlink($entry = null, array $post): bool
    {
        $session = Craft::$app->getSession();

        $shortlink = $this->_setShortlinkFromPost();
        $shortlink->shortlinkUri = $post['shortlinkUri'] ?? null;
        $shortlink->httpCode = $post['redirectType'] ?? null;
        $shortlink->shortlinkStatus = ShortlinkElement::STATUS_ACTIVE;

        if(!ElementHelper::isDraftOrRevision($entry)) {
            $shortlink->ownerId = $entry->id;
            $shortlink->ownerRevisionId = null;
        } else {
            $shortlink->ownerId = null;
            $shortlink->ownerRevisionId = $entry->id;
        }

        if (!Craft::$app->getElements()->saveElement($shortlink)) {
            $session->setError(Craft::t('shortlink', 'Could not save the shortlink.'));
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidConfigException
     * @throws \yii\base\Exception
     */
    public function doRedirect(string $url, string $path, string $host, ?array $redirect): bool
    {
        $response = Craft::$app->getResponse();
        if ($redirect !== null) {
            // check if destination is full url or path, if path use siteUrl UrlHelper and find the attached entry, else use link as is.

            if(!$redirect['destination']) {
                $ownerUrl = Entry::find()->id($redirect['ownerId'])->one();
                $siteId = $redirect['siteId'] ?? null;
                if($siteId !== null) {
                    $siteId = (int)$siteId;
                }

                $destination = UrlHelper::siteUrl($ownerUrl->uri, null, null, $siteId);
            } else {
                $destination = UrlHelper::isAbsoluteUrl($redirect['destination']) ? $redirect['destination'] : UrlHelper::siteUrl($redirect['destination'], null, null, $siteId);
            }

            $httpCode = $redirect['httpCode'];

            // Increase the stats
            $this->incrementStatistics($redirect, true);

            // add query string redirects in here
            if (Shortlink::$settings->redirectQueryString) {
                $request = Craft::$app->getRequest();
                $queryString = UrlHelper::combineQueryStringsFromUrls($destination, $request->getUrl());
                if(!empty($queryString)) {
                    $destination = strtok($destination, '?') . '?' . $queryString;
                }
            }

            // Sanitize the url
            $destination = UrlHelper::sanitizeUrl($destination);

            $response->redirect($destination, $httpCode)->send();
            try {
                Craft::$app->end();
            } catch (ExitException $e) {
                Craft::error($e->getMessage(), __METHOD__);
            }
        } else {
            $behavior = Shortlink::$settings->redirectBehavior;

            match ($behavior) {
                '404' => $this->doErrorRedirect(),
                'homepage' => $this->doHomepageRedirect($host),
            };
        }

        return false;
    }

    public function doErrorRedirect(): bool
    {
        $response = Craft::$app->getResponse();
        $errorHandler = Craft::$app->getErrorHandler();
        $errorHandler->exception = new NotFoundHttpException();
        try {
            $response = Craft::$app->runAction('templates/render-error');
        } catch (InvalidRouteException | \yii\console\Exception $e) {
            Craft::error($e->getMessage(), __METHOD__);
        }
        $response->redirect()->send();

        return false;
    }

    /**
     * @throws Exception
     */
    public function doHomepageRedirect(string $host): bool
    {
        // need to make sure we don't get an infinite loop
        $response = Craft::$app->getResponse();
        $sites = Craft::$app->getSites()->allSites;
        $baseUrls = [];
        // host returns including trailing slash, make sure we check for that too if it's not site in the SITE_URL
        $needle = [
            $host,
            $host . '/',
        ];

        // add all baseUrls to an array in case of multisite
        foreach($sites as $site) {
            $baseUrls[] = $site->baseUrl;
        }

        if(array_intersect($needle, $baseUrls)) {
            // only handles main site redirects for now
            $destination = UrlHelper::siteUrl('/', null, null, null);
            $response->redirect($destination)->send();
        }

        return false;
    }

    /**
     * @param string $path
     *
     * @return bool|array
     */
    public function getRedirectFromCache(string $path): bool|array
    {
        $cache = Craft::$app->getCache();
        $cacheKey = $this::CACHE_KEY . md5($path);
        $redirect = $cache->get($cacheKey);
        Craft::info(
            Craft::t(
                'shortlink',
                'Cached redirect hit for {path}',
                ['path' => $path]
            ),
            __METHOD__
        );

        return $redirect;
    }

    /**
     * @param string $path
     * @param array $redirect
     */
    public function saveRedirectToCache(string $path, array $redirect): void
    {
        $cache = Craft::$app->getCache();
        $cacheKey = $this::CACHE_KEY . md5($path);

        // Create the dependency tags
        $dependency = new TagDependency([
            'tags' => [
                $this::GLOBAL_ROUTES_CACHE_TAG
            ]
        ]);
        $cache->set($cacheKey, $redirect, null, $dependency);
        Craft::info(
            Craft::t(
                'shortlink',
                'Cached redirect saved for {path}',
                ['path' => $path]
            ),
            __METHOD__
        );
    }

    /**
     * Increment the hit count of the shortlink
     *
     * @param ?array $redirect
     * @param bool $handled
     * @param null $siteId
     */
    public function incrementStatistics(?array $redirect, bool $handled = false, $siteId = null): void
    {
        $shortlink = ShortlinkElement::findOne($redirect['id']);
        $shortlink->hitCount++;
        $shortlink->lastUsed = Db::prepareDateForDb(new DateTime());
        try {
            Craft::$app->getElements()->saveElement($shortlink);
        } catch (ElementNotFoundException | Exception | Throwable $e) {}
    }

    /**
     * @param string $casing
     * @param string $alphaNumeric
     * @return array
     */
    private function _defineFormat(string $casing = 'mixed', string $alphaNumeric = 'alphaNumeric'): array {
        // define the charset
        $formats = [];

        if($alphaNumeric === 'numeric' || $alphaNumeric === 'alphaNumeric') {
            $formats[] = '1234567890';
        }

        if(($casing === 'lowercase' || $casing === 'mixed') && ($alphaNumeric === 'alphaNumeric' || $alphaNumeric === 'alpha')) {
            $formats[] = 'abcdefghjkmnpqrstuvwxyz';
        }

        if(($casing === 'uppercase' || $casing === 'mixed') && ($alphaNumeric === 'alphaNumeric' || $alphaNumeric === 'alpha')) {
            $formats[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        }

        return $formats;
    }

    /**
     * @return Collection
     * @throws Exception
     */
    private function _fetchUris(): Collection
    {
        $query = (new Query)
            ->from('{{%shortlink_routes}}');

        return $query->collect();
    }

    /**
     * @param string $characters
     * @param int $length
     * @return string
     * @throws Exception
     */
    private function _generateUri(string $characters, int $length): string {
        // define the charset
        $uri = '';

        for ($i = 0; $i < $length; $i++) {
            $position = random_int(0, strlen($characters) - 1);
            $uri .= $characters[$position];
        }

        return $uri;
    }

    /**
     * @return ShortlinkElement
     * @throws Exception
     */
    private function _setShortlinkFromPost(Entry $entry): ShortlinkElement
    {
        if(!ElementHelper::isDraftOrRevision($entry)) {
            $shortlink = ShortlinkElement::findOne(['ownerId' => $entry->id]);
        } else {
            $shortlink = ShortlinkElement::findOne(['ownerRevisionId' => $entry->id]);
        }

        if(is_null($shortlink)) {
            $shortlink = new ShortlinkElement();
        }

        return $shortlink;
    }
}
