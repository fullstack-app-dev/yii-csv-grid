<?php

declare(strict_types=1);

namespace drcsystems\yiicsvgrid;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQueryInterface;
use yii\helpers\ArrayHelper;
use yii\helpers\Inflector;

class DataColumn extends Column
{
    public ?string $attribute = null;
    public ?string $label = null;
    public $value = null;
    public string $format = 'raw';

    public function renderHeaderCellContent(): string
    {
        if ($this->header !== null || ($this->label === null && $this->attribute === null)) {
            return parent::renderHeaderCellContent();
        }

        $provider = $this->grid->dataProvider;
        
        if ($this->label === null) {
            if ($provider instanceof ActiveDataProvider && $provider->query instanceof ActiveQueryInterface) {
                $modelClass = $provider->query->modelClass;
                $model = new $modelClass();
                $label = $model->getAttributeLabel($this->attribute);
            } else {
                $models = $provider->getModels();
                $model = reset($models);
                $label = ($model instanceof Model) ? $model->getAttributeLabel($this->attribute) : Inflector::camel2words($this->attribute);
            }
        } else {
            $label = $this->label;
        }
        
        return (string) $label;
    }

    public function getDataCellValue($model, $key, int $index): ?string
    {
        if ($this->value !== null) {
            return is_string($this->value) 
                ? ArrayHelper::getValue($model, $this->value) 
                : call_user_func($this->value, $model, $key, $index, $this);
        }
        
        return $this->attribute !== null ? ArrayHelper::getValue($model, $this->attribute) : null;
    }

    public function renderDataCellContent($model, $key, int $index): string
    {
        if ($this->content === null) {
            $value = $this->getDataCellValue($model, $key, $index);
            return $value === null ? $this->grid->nullDisplay : $this->grid->formatter->format($value, $this->format);
        }

        return parent::renderDataCellContent($model, $key, $index);
    }
}
