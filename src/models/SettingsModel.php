<?php

namespace percipiolondon\shortlink\models;

use craft\base\Model;
use craft\behaviors\EnvAttributeParserBehavior;

/**
 *
 * @author    percipiolondon
 * @package   Shortlink
 * @since     1.0.0
 *
 */
class SettingsModel extends Model
{
    // Public properties
    // =================

    /**
     * @var bool
     */
    public bool $allowCustom = true;

    /**
     * @var string The public-facing name of the plugin
     */
    public string $pluginName = 'Shortlink';

    /**
     * @var string
     */
    public string $alphaNumeric = 'alphaNumeric';

    /**
     * @var string
     */
    public string $casing = 'mixed';

    /**
     * @var int
     */
    public int $minLength = 6;

    /**
     * @var int
     */
    public int $maxLength = 20;

    /**
     * @var string
     */
    public string $redirect = '301';

    /**
     * @var bool
     */
    public bool $redirectQueryString = false;

    /**
     * @var string
     */
    public string $redirectBehavior = 'homepage';


    // Public Methods
    // ==============

    /**
     * @inheritdoc
     */
    protected function defineBehaviors(): array
    {
        return [
            'parser' => [
                'class' => EnvAttributeParserBehavior::class,
                'attributes' => ['allowCustom', 'alphaNumeric', 'casing', 'minLength', 'maxLength'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['alphaNumeric', 'redirectBehavior', 'casing', 'pluginName', 'redirect'], 'string'],
            ['maxLength', 'integer', 'min' => 6, 'max' => 20],
            ['minLength', 'integer', 'min' => 6, 'max' => 10],
            [['allowCustom', 'redirectQueryString'], 'boolean'],
            [['allowCustom', 'alphaNumeric', 'redirectBehavior', 'casing', 'maxLength', 'minLength', 'redirect', 'redirectQueryString'], 'required'],
        ];
    }
}
