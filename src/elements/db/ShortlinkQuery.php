<?php

namespace percipiolondon\shortlink\elements\db;

use craft\base\Element;
use percipiolondon\shortlink\elements\ShortlinkElement;

use craft\elements\db\ElementQuery;
use craft\helpers\Db;

class ShortlinkQuery extends ElementQuery
{
    // Properties
    // =========================================================================

    public mixed $ownerId = null;
    public mixed $shortlinkUri = null;
    public mixed $destination = null;
    public mixed $httpCode = null;
    public mixed $hitCount = null;
    public mixed $lastUsed = null;

    // Public Methods
    // =========================================================================

    public function ownerId($value): static
    {
        $this->ownerId = $value;
        return $this;
    }

    public function shortlinkUri($value): static
    {
        $this->shortlinkUri = $value;
        return $this;
    }

    public function destination($value): static
    {
        $this->destination = $value;
        return $this;
    }

    public function httpCode($value): static
    {
        $this->httpCode = $value;
        return $this;
    }

    public function hitCount($value): static
    {
        $this->hitCount = $value;
        return $this;
    }

    public function lastUsed($value): static
    {
        $this->lastUsed = $value;
        return $this;
    }

    protected function beforePrepare(): bool
    {
        $this->joinElementTable('shortlink_routes');

        $this->query->select([
            'shortlink_routes.*',
        ]);

        if ($this->ownerId) {
            $this->subQuery->andWhere(Db::parseParam('shortlink_routes.ownerId', $this->ownerId));
        }

        if ($this->shortlinkUri) {
            $this->subQuery->andWhere(Db::parseParam('shortlink_routes.shortlinkUri', $this->shortlinkUri));
        }

        if ($this->destination) {
            $this->subQuery->andWhere(Db::parseParam('shortlink_routes.destination', $this->destination));
        }

        if ($this->httpCode) {
            $this->subQuery->andWhere(Db::parseParam('shortlink_routes.httpCode', $this->httpCode));
        }

        if ($this->hitCount) {
            $this->subQuery->andWhere(Db::parseParam('shortlink_routes.hitCount', $this->hitCount));
        }

        if ($this->lastUsed) {
            $this->subQuery->andWhere(Db::parseParam('shortlink_routes.lastUsed', $this->lastUsed));
        }

        return parent::beforePrepare();
    }

    // Protected Methods
    // =========================================================================

    protected function statusCondition(string $status): mixed
    {
        return match ($status) {
            ShortlinkElement::STATUS_ACTIVE => [
                'shortlink_routes.status' => ShortlinkElement::STATUS_ACTIVE,
            ],
            ShortlinkElement::STATUS_INACTIVE => [
                'shortlink_routes.status' => ShortlinkElement::STATUS_INACTIVE,
            ],
            default => parent::statusCondition($status),
        };
    }
}
