<?php

namespace percipiolondon\shortlink\controllers;

use Craft;
use craft\errors\MissingComponentException;
use craft\web\Controller;

use percipiolondon\shortlink\elements\ShortlinkElement;
use percipiolondon\shortlink\records\ShortlinkRecord;
use percipiolondon\shortlink\Shortlink;

use yii\web\BadRequestHttpException;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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

    public function actionDashboard(): Response
    {
        $variables = [];
        $pluginName = Shortlink::$settings->pluginName;
        $templateTitle = Craft::t('shortlink', 'Dashboard');

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'dashboard';

        // Render the template
        return $this->renderTemplate('shortlink/dashboard', $variables);
    }

    public function actionCustomShortlinks(): Response
    {
        $variables = [];
        $pluginName = Shortlink::$settings->pluginName;
        $templateTitle = Craft::t('shortlink', 'Custom Shortlinks');

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'custom-shortlinks';
        $variables['currentSiteId'] = Craft::$app->getSites()->getCurrentSite()->id;
        $variables['shortlinks'] = ShortlinkRecord::findAll(['ownerId' => null]);

        // Render the template
        return $this->renderTemplate('shortlink/custom-shortlinks', $variables);
    }

    public function actionCustomShortlinksAdd(): Response
    {
        $variables = [];
        $pluginName = Shortlink::$settings->pluginName;
        $templateTitle = Craft::t('shortlink', 'Custom Shortlinks');

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'custom-shortlinks';
        $variables['shortlink'] = null;

        // Render the template
        return $this->renderTemplate('shortlink/custom-shortlinks/form', $variables);
    }

    public function actionCustomShortlinksEdit(int $shortlinkId): Response
    {
        $variables = [];
        $pluginName = Shortlink::$settings->pluginName;
        $templateTitle = Craft::t('shortlink', 'Custom Shortlinks');
        $shortlink = ShortlinkElement::findOne($shortlinkId);

        if (is_null($shortlink)) {
            throw new NotFoundHttpException(Craft::t('shortlink', 'Shortlink does not exist'));
        }

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'custom-shortlinks';
        $variables['shortlink'] = $shortlink;

        // Render the template
        return $this->renderTemplate('shortlink/custom-shortlinks/form', $variables);
    }

    public function actionCustomShortlinksSave(): Response
    {
        $this->requireLogin();
        $this->requirePostRequest();

        $request = Craft::$app->getRequest();

        $shortlinkId = $request->getBodyParam('shortlinkId');

        if ($shortlinkId) {
            $shortlink = ShortlinkElement::findOne($shortlinkId);
        } else {
            $shortlink = new ShortlinkElement();
        }

        $shortlink->shortlinkUri = $request->getBodyParam('shortlinkUri') ?? null;
        $shortlink->destination = $request->getBodyParam('destination') ?? null;
        $shortlink->httpCode = $request->getBodyParam('httpCode') ?? null;

        $success = Craft::$app->getElements()->saveElement($shortlink);

        if ($success) {
            return $this->redirect('/admin/shortlink/custom-shortlinks');
        }

        $variables = [];
        $pluginName = Shortlink::$settings->pluginName;
        $templateTitle = Craft::t('shortlink', 'Custom Shortlinks');

        $variables['fullPageForm'] = true;
        $variables['pluginName'] = $pluginName;
        $variables['title'] = $templateTitle;
        $variables['docTitle'] = "{$pluginName} - {$templateTitle}";
        $variables['selectedSubnavItem'] = 'custom-shortlinks';
        $variables['shortlink'] = $shortlink;

        return $this->renderTemplate('staff-management/benefits/policy/form', $variables);
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
            'allowCustom' => Craft::$app->getRequest()->getBodyParam('allowCustom') === '1',
            'alphaNumeric' => Craft::$app->getRequest()->getBodyParam('alphaNumeric'),
            'redirectBehavior' => Craft::$app->getRequest()->getBodyParam('redirectBehavior'),
            'casing' => Craft::$app->getRequest()->getBodyParam('casing'),
            'maxLength' => (int) Craft::$app->getRequest()->getBodyParam('maxLength'),
            'minLength' => (int) Craft::$app->getRequest()->getBodyParam('minLength'),
            'redirectType' => Craft::$app->getRequest()->getBodyParam('redirectType'),
            'redirectQueryString' => Craft::$app->getRequest()->getBodyParam('redirectQueryString') === '1',
            'shortlinkUrls' => Craft::$app->getRequest()->getBodyParam('shortlinkUrls'),
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
