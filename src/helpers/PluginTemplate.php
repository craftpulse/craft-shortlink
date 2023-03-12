<?php

namespace percipiolondon\shortlink\helpers;

use Craft;
use craft\helpers\Template;

use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use yii\base\Exception;

class PluginTemplate
{
    /**
     * Render a plugin template
     *
     * @param string $templatePath
     * @param array $params
     *
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public static function renderPluginTemplate(
        string $templatePath,
        array $params = [],
    ): string {
        try {
            $htmlText = Craft::$app->view->renderTemplate('shortlink/' . $templatePath, $params);
        } catch (Exception $e) {
            $htmlText = Craft::t(
                'shortlink',
                'Error rendering `{template}` -> {error}',
                ['template' => $templatePath, 'error' => $e->getMessage()]
            );
            Craft::error($htmlText, __METHOD__);
        }

        return Template::raw($htmlText);
    }
}
