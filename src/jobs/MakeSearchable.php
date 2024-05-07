<?php

namespace rias\scout\jobs;

use craft\base\Element;
use craft\queue\BaseJob;
use rias\scout\Scout;
use rias\scout\ScoutIndex;

class MakeSearchable extends BaseJob
{
    /** @var int */
    public $id;

    /** @var int */
    public $siteId;

    /** @var string */
    public $indexName;

    /** @var bool */
    public $propagate = true;

    public function execute($queue): void
    {
        if (!$element = $this->getElement()) {
            return;
        }

        $this->getEngine()->update($element);

        if ($this->propagate) {
            $element->searchableRelations();
        }
    }

    protected function defaultDescription(): string
    {
        if (!$element = $this->getElement()) {
            return '';
        }

        return sprintf(
            'Indexing “%s” in “%s”',
            ($element->title ?? $element->id),
            $this->indexName
        );
    }

    /**
     * We use this method instead of setting a prop in the constructor,
     * because Yii will serialize the entire class into the queue table,
     * including the gigantic element prop.
     *
     * @return Element
     */
    private function getElement()
    {
        return $this->getIndex()
            ->criteria
            ->id($this->id)
            ->siteId($this->siteId)
            ->one();
    }

    protected function getEngine()
    {
        return Scout::$plugin->getSettings()->getEngine($this->getIndex());
    }

    protected function getIndex()
    {
        return Scout::$plugin->getSettings()->getIndices()->first(function (ScoutIndex $scoutindex) {
            $element = \Craft::$app->getElements()->getElementById($this->id);
            return $scoutindex->elementType === get_class($element) && $scoutindex->indexName === $this->indexName;
        });
    }
}
