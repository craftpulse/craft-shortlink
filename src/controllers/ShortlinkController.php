<?php

namespace percipiolondon\shortlink\controllers;

use craft\web\Controller;
use percipiolondon\shortlink\Shortlink;
use yii\web\BadRequestHttpException;

class ShortlinkController extends Controller
{
    /**
     * @throws BadRequestHttpException
     */
    public function actionRegenerateShortlink(): ?string
    {
        $this->requireCpRequest();
        return Shortlink::getInstance()->generator->generateShortlink();
    }
}
