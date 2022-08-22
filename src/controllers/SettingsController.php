<?php

namespace percipiolondon\shortlink\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;

use percipiolondon\shortlink\Shortlink;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;

/**
 *
 * @author    percipiolondon
 * @package   Shortlink
 * @since     1.0.0
 *
 */
class SettingsController extends Controller
{
    /**
     * Settings display
     *
     *
     * @return Response The rendered result
     * @throws NotFoundHttpException
     * @throws ForbiddenHttpException
     */
    public function actionPlugin(): Response
    {
        $variables = [];
        $pluginName = Shortlink::$settings->pluginName;
        $templateTitle = Craft::t('shortlink', 'Plugin Settings');

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'plugin';
        $variables['settings'] = Shortlink::$settings;

        // Render the template
        return $this->renderTemplate('shortlink/settings/shortlink-settings', $variables);
    }

    /**
     * Saves a plugin’s settings.
     *
     * @return Response|null
     * @throws NotFoundHttpException if the requested plugin cannot be found
     * @throws BadRequestHttpException
     * @throws MissingComponentException
     */
    public function actionSavePluginSettings(): ?Response
    {
        $this->requirePostRequest();
        $pluginHandle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $plugin = Craft::$app->getPlugins()->getPlugin($pluginHandle);

        if ($plugin === null) {
            throw new NotFoundHttpException('Plugin not found');
        }

        $settings = [
            'allowCustom' => Craft::$app->getRequest()->getBodyParam('allowCustom'),
            'alphaNumeric' => Craft::$app->getRequest()->getBodyParam('alphaNumeric'),
            'redirectBehavior' => Craft::$app->getRequest()->getBodyParam('redirectBehavior'),
            'casing' => Craft::$app->getRequest()->getBodyParam('casing'),
            'maxLength' => Craft::$app->getRequest()->getBodyParam('maxLength'),
            'minLength' => Craft::$app->getRequest()->getBodyParam('minLength'),
            'redirect' => Craft::$app->getRequest()->getBodyParam('redirect'),
            'redirectQueryString' => Craft::$app->getRequest()->getBodyParam('redirectQueryString'),
        ];

        if(!Craft::$app->getPlugins()->savePluginSettings($plugin, $settings)) {
            Craft::$app->getSession()->setError(Craft::t('app', "Couldn't save plugin settings."));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));

        return $this->redirectToPostedUrl();
    }
}
