<?php

namespace percipiolondon\shortlink\services;

use Craft;
use craft\base\Component;
use craft\errors\ElementNotFoundException;
use craft\errors\MissingComponentException;
use craft\events\ModelEvent;

use Exception;
use percipiolondon\shortlink\records\ShortlinkRecord;
use percipiolondon\shortlink\Shortlink;
use percipiolondon\shortlink\models\ShortlinkModel;
use percipiolondon\shortlink\elements\ShortlinkElement;
use Throwable;
use yii\base\ExitException;

/**
 * @author    percipiolondon
 * @package   Timeloop
 * @since     4.0.0
 */
class ShortlinkService extends Component
{
    public function getShortLink(int $elementId): array|string|null
    {
        $shortlink = ShortlinkRecord::findOne(['ownerId' => $elementId]);

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
        return $this->_generateUri($allowedChars, $length);
    }

    /**
     * @throws ExitException
     */
    public function onAfterSaveEntry(ModelEvent $event): void
    {
        $request = Craft::$app->getRequest();

        $shortlink = [
            'shortlinkUri' => $request->getBodyParam('shortlink-uri'),
            'redirectType' => $request->getBodyParam('shortlink-redirect-type'),
        ];

        $this->saveShortlink($event->sender, $shortlink);

        //Craft::dd($request->getBodyParams());
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
        $shortlink->ownerId = $entry->id;
        $shortlink->shortlinkUri = $post['shortlinkUri'] ?? null;
        $shortlink->httpCode = $post['redirectType'] ?? null;
        $shortlink->shortlinkStatus = ShortlinkElement::STATUS_ACTIVE;

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
}
