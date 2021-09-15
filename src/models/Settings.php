<?php
/**
 * craft-shortlink plugin for Craft CMS 3.x
 *
 * A plugin to use your own subdomain as a url shortener
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 Percipio
 */

namespace percipioglobal\craftshortlink\models;

use percipioglobal\craftshortlink\Craftshortlink;

use Craft;
use craft\base\Model;

/**
 * @author    Percipio
 * @package   Craftshortlink
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $shortlinkSiteFrom = null;
    public $shortlinkSiteTo = null;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {

    }
}
