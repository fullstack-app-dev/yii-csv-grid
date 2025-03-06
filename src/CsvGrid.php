<?php
/**
 * @link https://github.com/drcsystems
 * @copyright Copyright (c) 2025 DRC Systems
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace drcsystems\csvgrid;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\data\ActiveDataProvider;
use yii\di\Instance;
use yii\i18n\Formatter;

class CsvGrid extends Component
{
    public ?ActiveDataProvider $dataProvider = null;
    public $query;
    public int $batchSize = 100;
    public array $columns = [];
    public bool $showHeader = true;
    public bool $showFooter = false;
    public string $emptyCell = '';
    public string $nullDisplay = '';
    public ?int $maxEntriesPerFile = null;
    public array $csvFileConfig = [];
    public array $resultConfig = [];
    private array|Formatter $_formatter;
    private ?array $batchInfo = null;

    public function init(): void
    {
        parent::init();
        if ($this->dataProvider === null && $this->query !== null) {
            $this->dataProvider = new ActiveDataProvider([
                'query' => $this->query,
                'pagination' => ['pageSize' => $this->batchSize],
            ]);
        }
    }

    public function getFormatter(): Formatter
    {
        if (!is_object($this->_formatter)) {
            $this->_formatter = $this->_formatter ?? Yii::$app->getFormatter();
            $this->_formatter = Instance::ensure($this->_formatter, Formatter::class);
        }
        return $this->_formatter;
    }

    public function setFormatter(array|Formatter $formatter): void
    {
        $this->_formatter = $formatter;
    }

    protected function initColumns(array $model): void
    {
        if (empty($this->columns)) {
            $this->guessColumns($model);
        }
        foreach ($this->columns as $i => $column) {
            if (is_string($column)) {
                $column = $this->createDataColumn($column);
            } else {
                $column = Yii::createObject(array_merge(['class' => DataColumn::class, 'grid' => $this], $column));
            }
            if (!$column->visible) {
                unset($this->columns[$i]);
                continue;
            }
            $this->columns[$i] = $column;
        }
    }

    protected function guessColumns(array|object $model): void
    {
        foreach ($model as $name => $value) {
            $this->columns[] = (string) $name;
        }
    }

    protected function createDataColumn(string $text): DataColumn
    {
        if (!preg_match('/^([^:]+)(:(\w*))?(:(.*))?$/', $text, $matches)) {
            throw new InvalidConfigException('Invalid column format.');
        }
        return Yii::createObject([
            'class' => DataColumn::class,
            'grid' => $this,
            'attribute' => $matches[1],
            'format' => $matches[3] ?? 'raw',
            'label' => $matches[5] ?? null,
        ]);
    }

    public function export(): ExportResult
    {
        $result = Yii::createObject(array_merge(['class' => ExportResult::class], $this->resultConfig));
        $columnsInitialized = false;
        $csvFile = null;
        $rowIndex = 0;

        while (($data = $this->batchModels()) !== false) {
            [$models, $keys] = $data;
            if (!$columnsInitialized) {
                $this->initColumns(reset($models));
                $columnsInitialized = true;
            }
            foreach ($models as $index => $model) {
                if (!is_object($csvFile)) {
                    $csvFile = $result->newCsvFile($this->csvFileConfig);
                    if ($this->showHeader) {
                        $csvFile->writeRow($this->composeHeaderRow());
                    }
                }
                $csvFile->writeRow($this->composeBodyRow($model, $keys[$index] ?? $index, $rowIndex));
                $rowIndex++;
            }
            $this->gc();
        }
        if (is_object($csvFile)) {
            if ($this->showFooter) {
                $csvFile->writeRow($this->composeFooterRow());
            }
            $csvFile->close();
        }
        return $result;
    }

    protected function batchModels(): array|false
    {
        if ($this->batchInfo === null) {
            if ($this->query !== null && method_exists($this->query, 'batch')) {
                $this->batchInfo = ['queryIterator' => $this->query->batch($this->batchSize)];
            } else {
                $this->batchInfo = ['pagination' => $this->dataProvider->getPagination(), 'page' => 0];
            }
        }
        if (isset($this->batchInfo['queryIterator'])) {
            $iterator = $this->batchInfo['queryIterator'];
            $iterator->next();
            return $iterator->valid() ? [$iterator->current(), []] : false;
        }
        if (isset($this->batchInfo['pagination'])) {
            $pagination = $this->batchInfo['pagination'];
            $page = $this->batchInfo['page'];
            if ($pagination === false || $pagination->pageCount === 0) {
                return $page === 0 ? [$this->dataProvider->getModels(), $this->dataProvider->getKeys()] : false;
            }
            if ($page < $pagination->pageCount) {
                $pagination->setPage($page);
                $this->dataProvider->prepare(true);
                $this->batchInfo['page']++;
                return [$this->dataProvider->getModels(), $this->dataProvider->getKeys()];
            }
        }
        return false;
    }

    protected function composeHeaderRow(): array
    {
        return array_map(fn($column) => $column->renderHeaderCellContent(), $this->columns);
    }

    protected function composeFooterRow(): array
    {
        return array_map(fn($column) => $column->renderFooterCellContent(), $this->columns);
    }

    protected function composeBodyRow($model, $key, int $index): array
    {
        return array_map(fn($column) => $column->renderDataCellContent($model, $key, $index), $this->columns);
    }

    protected function gc(): void
    {
        if (!gc_enabled()) gc_enable();
        gc_collect_cycles();
    }
}
