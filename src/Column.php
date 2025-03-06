<?php

/**
 * @link https://github.com/drcsystems/yii-csv-grid
 * @copyright Copyright (c) 2025 DRC Systems
 * @license [BSD-3-Clause](http://www.opensource.org/licenses/BSD-3-Clause)
 */

namespace drcsystems\yiicsvgrid;

use yii\base\BaseObject;

/**
 * Column is the base class of all {@see CsvGrid} column classes.
 *
 * @author Jitendra Yadav <jitendra@example.com>
 * @since 1.0
 */
class Column extends BaseObject
{
    /**
     * @var CsvGrid The exporter object that owns this column.
     */
    public CsvGrid $grid;

    /**
     * @var string|null The header cell content.
     */
    public ?string $header = null;

    /**
     * @var string|null The footer cell content.
     */
    public ?string $footer = null;

    /**
     * @var callable|null This callable will be used to generate the content of each cell.
     * The signature of the function should be: `function ($model, $key, $index, $column): string`.
     * Where `$model`, `$key`, and `$index` refer to the model, key, and index of the row currently being rendered.
     * `$column` is a reference to the {@see Column} object.
     */
    public ?callable $content = null;

    /**
     * @var bool Whether this column is visible. Defaults to true.
     */
    public bool $visible = true;

    /**
     * Renders the header cell content.
     * The default implementation simply renders {@see header}.
     * This method may be overridden to customize the rendering of the header cell.
     *
     * @return string The rendering result.
     */
    public function renderHeaderCellContent(): string
    {
        return !empty($this->header) ? $this->header : $this->grid->emptyCell;
    }

    /**
     * Renders the footer cell content.
     * The default implementation simply renders {@see footer}.
     * This method may be overridden to customize the rendering of the footer cell.
     *
     * @return string The rendering result.
     */
    public function renderFooterCellContent(): string
    {
        return !empty($this->footer) ? $this->footer : $this->grid->emptyCell;
    }

    /**
     * Renders the data cell content.
     *
     * @param mixed $model The data model.
     * @param mixed $key The key associated with the data model.
     * @param int $index The zero-based index of the data model among the models array returned by {@see CsvGrid::$dataProvider}.
     *
     * @return string The rendering result.
     */
    public function renderDataCellContent($model, $key, int $index): string
    {
        return $this->content !== null
            ? call_user_func($this->content, $model, $key, $index, $this)
            : $this->grid->emptyCell;
    }
}
