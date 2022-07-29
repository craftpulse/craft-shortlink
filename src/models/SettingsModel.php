<?php

namespace percipiolondon\shortlink\models;

use craft\base\Model;

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
}