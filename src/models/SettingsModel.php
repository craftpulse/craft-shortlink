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
    public string $redirectType = '301';

    /**
     * @var array
     */
    public array $shortlinkUrls = [];

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
                'attributes' => ['allowCustom', 'alphaNumeric', 'casing', 'maxLength', 'minLength'],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    public function defineRules(): array
    {
        return [
            [['alphaNumeric', 'casing', 'pluginName', 'redirectBehavior', 'redirectType'], 'string'],
            ['maxLength', 'integer', 'min' => 6, 'max' => 20],
            ['minLength', 'integer', 'min' => 4, 'max' => 10],
            [['allowCustom', 'redirectQueryString'], 'boolean'],
            [['allowCustom', 'alphaNumeric', 'casing', 'maxLength', 'minLength', 'redirectBehavior', 'redirectQueryString', 'redirectType'], 'required'],
        ];
    }
}
