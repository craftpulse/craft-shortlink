<?php

namespace percipiolondon\shortlink\services;

use Craft;
use craft\base\Component;
use craft\db\Query;
use craft\elements\Entry;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\helpers\ElementHelper;
use craft\helpers\UrlHelper;

use Illuminate\Support\Collection;
use percipiolondon\shortlink\Shortlink;
use percipiolondon\shortlink\models\ShortlinkModel;
use percipiolondon\shortlink\elements\ShortlinkElement;

use Exception;
use Throwable;
use yii\base\ExitException;

/**
 * @author    percipiolondon
 * @package   Shortlink
 * @since     4.0.0
 */
class ShortlinkService extends Component
{
    public function getShortLink(Entry $element): array|string|null
    {
        $shortlink = ShortlinkElement::findOne(['ownerId' => $element->id]);

        if (!is_null($shortlink)) {
            return $shortlink->shortlinkUri;
        }

        return $this->generateShortlink();
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
     */
    public function handleRedirect(): void
    {
        $request = Craft::$app->getRequest();
        // Only handle site requests, no live previews or console requests
        if ($request->getIsSiteRequest() && !$request->getIsLivePreview() && !$request->getIsConsoleRequest()) {
                $host = urldecode($request->getHostInfo());
                $path = urldecode($request->getUrl());
                $url = urldecode($request->getAbsoluteUrl());

            $baseUrls = [];
            $sites = Craft::$app->getSites()->allSites;

            // host returns including trailing slash, make sure we check for that too if it's not site in the SITE_URL
            $needle = [
                $host,
                $host . '/',
            ];
            // add all baseUrls to an array in case of multisite
            foreach($sites as $site) {
                $baseUrls[] = $site->baseUrl;
            }

            // check if our hostname is one of the existing Craft sites, if so don't try to redirect
            if(array_intersect($needle, $baseUrls)) {
                // check if query string should be stripped or not
                if (!Shortlink::$settings->redirectQueryString) {
                    $path = UrlHelper::stripQueryString($path);
                    $url = UrlHelper::stripQueryString($url);
                }

                // Redirect if we find a match, otherwise let Craft handle it
                $redirect = $this->findShortlinkMatch($path);
                $this->doRedirect($url, $path, $redirect);
            }
        }
        Craft::dd('the-path');
    }

    /**
     * @param string $path
     * @param null $siteId
     *
     * @return array|null
     */
    public function findShortlinkMatch(string $path, $siteId = null): ?array
    {
        // Need to add multisite functionality
        return $this->getShortlinkRedirect($path, $siteId);
    }

    /**
     * @param string $path
     * @param $siteId
     * @return mixed|null
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
     * @throws ExitException
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
     * @throws \yii\base\Exception
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
     * @throws Exception
     */
    private function _setShortlinkFromPost(): ShortlinkElement
    {
        $request = Craft::$app->getRequest();
        $shortlinkId = $request->getParam('shortlinkId');

        if ($shortlinkId) {
            $shortlink = $this->getShortlinkById($shortlinkId);

            if (!$shortlink) {
                throw new Exception (Craft::t('shortlink', 'No shortlink with the ID “{id}”', ['id' => $shortlinkId]));
            }
        } else {
            $shortlink = new ShortlinkElement();
        }

        return $shortlink;
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
     * @param string $chars
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

    private function _fetchUris(): Collection
    {
        $query = (new Query)
            ->from('{{%shortlink_routes}}');

        return $query->collect();
    }

    public function doRedirect(string $url, string $path, ?array $redirect): bool
    {
        $response = Craft::$app->getResponse();
        if ($redirect !== null) {
            $ownerUrl = Entry::find()->id($redirect['ownerId'])->one();
            $siteId = $redirect['siteId'] ?? null;
            if($siteId !== null) {
                $siteId = (int)$siteId;
            }
            Craft::dd(UrlHelper::siteUrl($ownerUrl->uri, null, null, $siteId));
        }

        // add query string redirects in here

        return false;
    }
}
