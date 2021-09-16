<?php
/**
 * craft-shortlink plugin for Craft CMS 3.x
 *
 * A plugin to use your own subdomain as a url shortener
 *
 * @link      https://percipio.london
 * @copyright Copyright (c) 2021 Percipio
 */

namespace percipioglobal\craftshortlink\fields;

use percipioglobal\craftshortlink\Craftshortlink as Plugin;
use percipioglobal\craftshortlink\assetbundles\craftshortlinkfield\CraftshortlinkFieldAsset;

use Craft;
use craft\base\ElementInterface;
use craft\base\Field;
use craft\helpers\Db;
use yii\db\Schema;
use craft\helpers\Json;

/**
 * @author    Percipio
 * @package   Craftshortlink
 * @since     1.0.0
 */
class CraftshortlinkField extends Field
{
    // Public Properties
    // =========================================================================

    public $shortLinkLength = '6';
    public $shortLinkFormat = 'alphanumeric';
    public $shortLinkTextOnly = null;
    public $shortLinkBase = null;


    // Static Methods
    // =========================================================================


    public static function displayName(): string
    {
        return Craft::t('craft-shortlink', 'Short Link');
    }

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        $rules = parent::rules();
        return $rules;
    }

    /**
     * @inheritdoc
     */
    public function getContentColumnType(): string
    {
        return Schema::TYPE_STRING;
    }

    /**
     * @inheritdoc
     */
    public function normalizeValue($value, ElementInterface $element = null)
    {
        return $value;
    }

    /**
     * @inheritdoc
     */
    public function serializeValue($value, ElementInterface $element = null)
    {
        return parent::serializeValue($value, $element);
    }

    /**
     * @inheritdoc
     */
    public function getSettingsHtml()
    {
        // Render the settings template
        return Craft::$app->getView()->renderTemplate(
            'craft-shortlink/_components/fields/CraftshortlinkField_settings',
            [
                'field' => $this
            ]
        );
    }

    private function generateShortLink($settings,$shortLink){
        // get options
        $shortLinkLength = $settings->shortLinkLength;
        $shortLinkFormat = $settings->shortLinkFormat;
        // remove any spaces / foreign chars
        $shortLink = str_replace(' ', '', $shortLink); // removes all spaces
        $shortLink = preg_replace('/[^A-Za-z0-9\-]/', '', $shortLink); // Removes special chars
        // generate allowed char arrays
        $formats = array();
        $formats[] = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $formats[] = 'abcdefghjkmnpqrstuvwxyz';
        $formats[] = '1234567890';

        if(!$shortLink){
            foreach ($formats as $format) {
                $shortLink .= $format[array_rand(str_split($format))];
            }
             while(strlen($shortLink) < $shortLinkLength) {
                 $randomFormat = $formats[array_rand($formats)];
                 $shortLink .= $randomFormat[array_rand(str_split($randomFormat))];
            }
        }
        return $shortLink;
    }

    /**
     * @inheritdoc
     */
    public function getInputHtml($value, ElementInterface $element = null): string
    {
        // Register our asset bundle
        Craft::$app->getView()->registerAssetBundle(CraftshortlinkFieldAsset::class);

        // Get our id and namespace
        $id = Craft::$app->getView()->formatInputId($this->handle);
        $namespacedId = Craft::$app->getView()->namespaceInputId($id);

        // Variables to pass down to our field JavaScript to let it namespace properly
        $jsonVars = [
            'id' => $id,
            'name' => $this->handle,
            'namespace' => $namespacedId,
            'prefix' => Craft::$app->getView()->namespaceInputId(''),
            ];
        $jsonVars = Json::encode($jsonVars);
        Craft::$app->getView()->registerJs("$('#{$namespacedId}-field').CraftshortlinkCraftshortlinkField(" . $jsonVars . ");");

        // Render the input template
        return Craft::$app->getView()->renderTemplate(
            'craft-shortlink/_components/fields/CraftshortlinkField_input',
            [
                'name' => $this->handle,
                'value' => $this->generateShortLink($this, $value),
                'field' => $this,
                'id' => $id,
                'namespacedId' => $namespacedId
            ]
        );
    }
}
