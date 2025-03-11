<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\elements\conditions;

use Craft;
use craft\elements\conditions\ElementCondition;

/**
 * Route condition
 *
 * @author      CraftPulse
 * @package     Shortlink
 *
 */
class RouteCondition extends ElementCondition
{
    protected function selectableConditionRules(): array
    {
        return array_merge(parent::conditionRuleTypes(), [
            // ...
        ]);
    }
}
