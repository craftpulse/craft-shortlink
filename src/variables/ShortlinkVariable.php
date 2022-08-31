<?php

namespace percipiolondon\shortlink\variables;

use nystudio107\pluginvite\variables\ViteVariableInterface;
use nystudio107\pluginvite\variables\ViteVariableTrait;

/**
 *
 * @author    percipiolondon
 * @package   ShortlinkElement
 * @since     1.0.0
 * @property VitePluginService  $vite
 * @property TimeloopService $timeloop
 *
 */
class ShortlinkVariable implements ViteVariableInterface
{
    use ViteVariableTrait;
}
