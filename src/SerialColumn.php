<?php
/**
 * @link https://github.com/drcsystems/yiicsvgrid
 * @copyright Copyright (c) 2025 DRC Systems
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace drcsystems\yiicsvgrid;

/**
 * SerialColumn displays a column of row numbers (1-based).
 *
 * To add a SerialColumn to the {@see CsvGrid}, add it to the {@see CsvGrid::$columns} configuration as follows:
 *
 * ```php
 * 'columns' => [
 *     [
 *         'class' => 'drcsystems\yiicsvgrid\SerialColumn',
 *     ],
 *     // ...
 * ]
 * ```
 * 
 */
class SerialColumn extends Column
{
    /**
     * {@inheritdoc}
     */
    public $header = '#';


    /**
     * {@inheritdoc}
     */
     public function renderDataCellContent($model, $key, int $index): int
    {
        return $index + 1;
    }
}