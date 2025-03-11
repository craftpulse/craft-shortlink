<?php
/**
 * Shortlink plugin for Craft CMS
 *
 * @link      https://craft-pulse.com
 * @copyright Copyright (c) 2025 CraftPulse
 */

namespace craftpulse\shortlink\services;

use Craft;
use craft\fieldlayoutelements\TextField;

use craftpulse\shortlink\Shortlink;
use craftpulse\shortlink\models\SettingsModel;
use craftpulse\shortlink\fieldlayoutelements\DropdownField;

use yii\base\Component;

/**
 * Routes Service
 *
 * @author    CraftPulse
 * @package   Shortlink
 */
class Routes extends Component
{
    // Private Properties
    // =========================================================================
    /**
     * @var SettingsModel
     */
    private SettingsModel $settings;

    // Public Methods
    // =========================================================================

    public function init(): void
    {
        $this->settings = Shortlink::$plugin->settings;
    }

    public function generateRedirectOptions(): array
    {
        return [
            [
                'label' => Craft::t('shortlink', '301 Moved Permanently'),
                'value' => '301',
            ],
            [
                'label' => Craft::t('shortlink', '302 Found'),
                'value' => '302',
            ],
            [
                'label' => Craft::t('shortlink', '307 Temporary Redirect'),
                'value' => '307',
            ],
            [
                'label' => Craft::t('shortlink', '308 Permanent Redirect'),
                'value' => '308',
            ],
            [
                'label' => Craft::t('shortlink', '410 Gone'),
                'value' => '410',
            ],
        ];
    }

    public function generateMatchOptions(): array
    {
        return [
            [
                'label' => Craft::t('shortlink', 'Exact Match'),
                'value' => 'exact',
            ],
            [
                'label' => Craft::t('shortlink', 'Regex Match'),
                'value' => 'regex',
            ],
        ];
    }

    public function generateOriginOptions(): array
    {
        $origins = [];

        foreach ($this->settings->domainNames as $domainName) {
            $domain = $domainName['domain'];

            // making sure we have a domain already, there is standard an empty row.
            if(!empty($domain)) {
                $origins[] = [
                    'label' => $domain,
                    'value' => $this->sanitizeDomain($domain),
                ];
            }
        }

        return $origins;
    }

    public function sanitizeDomain(string $domain): ?string {
        // Strip all the unnecessary data to create a value.
        // @TODO - maybe we should keep the domain suffix?
        preg_match('/(?:https?:\/\/)?(?:www\.)?([^\/.]+)/', $domain, $matches);
        $name = $matches[1];

        return $name;
    }

    /**
     * @return array[]|null
     */
    public function createFields(): ?array
    {
        $fields = [
            [
                'class' => DropdownField::class,
                'attribute' => 'origin',
                'label' => Craft::t('shortlink', 'Origin'),
                'instructions' => Craft::t('shortlink', 'What origin this redirect should come from.'),
                'mandatory' => true,
                'options' => $this->generateOriginOptions(),
                'width' => '100%',
            ],
            [
                'class' => TextField::class,
                'attribute' => 'uriPattern',
                'name' => 'uriPattern',
                'instructions' => Craft::t('shortlink', 'Enter the URL pattern that Retour should match. This matches against the path only'),
                'label' => Craft::t('shortlink', 'URI pattern'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => true,
                'width' => '100%',
            ],
            [
                'class' => TextField::class,
                'attribute' => 'destination',
                'name' => 'destination',
                'instructions' => Craft::t('shortlink', 'Enter the destination URL that should be redirected to. This can either be a fully qualified URL or a relative URL.'),
                'label' => Craft::t('shortlink', 'Destination'),
                'inputType' => 'text',
                'mandatory' => true,
                'required' => true,
                'width' => '100%',
            ],
            [
                'class' => DropdownField::class,
                'attribute' => 'matchType',
                'label' => Craft::t('shortlink', 'Match Type'),
                'instructions' => Craft::t('shortlink', 'What type of matching should be done with the Legacy URL Pattern.'),
                'mandatory' => true,
                'options' => $this->generateMatchOptions(),
                'width' => '100%',
            ],
            [
                'class' => DropdownField::class,
                'attribute' => 'httpCode',
                'label' => Craft::t('shortlink', 'HTTP Code'),
                'instructions' => Craft::t('shortlink', 'Select whether the redirect should be permanent or temporary.'),
                'mandatory' => true,
                'options' => $this->generateRedirectOptions(),
                'width' => '100%',
            ]
        ];

        return $fields;
    }
}
