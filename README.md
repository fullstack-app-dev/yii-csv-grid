# CSV Data Export Extension for Yii2

This extension provides the ability to export data to a CSV file using Yii2.

## Installation

The preferred way to install this extension is through [Composer](http://getcomposer.org/download/).

Run the following command:

```sh
composer require --prefer-dist drcsystems/yiicsvgrid
```

Or add the following to the `require` section of your `composer.json` file:

```json
"drcsystems/yiicsvgrid": "*"
```

## Usage

### Basic Example

Export data to a CSV file using `CsvGrid`:

```php
use drcsystems\yiicsvgrid\CsvGrid;
use yii\data\ArrayDataProvider;

$exporter = new CsvGrid([
    'dataProvider' => new ArrayDataProvider([
        'allModels' => [
            ['name' => 'Product A', 'price' => '100'],
            ['name' => 'Product B', 'price' => '200'],
        ],
    ]),
    'columns' => [
        ['attribute' => 'name'],
        ['attribute' => 'price', 'format' => 'decimal'],
    ],
]);
$exporter->export()->saveAs('/path/to/file.csv');
```

### Export Using ActiveDataProvider

```php
use drcsystems\yiicsvgrid\CsvGrid;
use yii\data\ActiveDataProvider;

$exporter = new CsvGrid([
    'dataProvider' => new ActiveDataProvider([
        'query' => Item::find(),
        'pagination' => ['pageSize' => 100],
    ]),
]);
$exporter->export()->saveAs('/path/to/file.csv');
```

### Export Using QueryInterface

```php
use drcsystems\yiicsvgrid\CsvGrid;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'batchSize' => 200,
]);
$exporter->export()->saveAs('/path/to/file.csv');
```

## Sending File to Browser

You can send the CSV file to the browser for download:

```php
use drcsystems\yiicsvgrid\CsvGrid;
use yii\data\ActiveDataProvider;
use yii\web\Controller;

class ItemController extends Controller
{
    public function actionExport()
    {
        $exporter = new CsvGrid([
            'dataProvider' => new ActiveDataProvider([
                'query' => Item::find(),
            ]),
        ]);
        return $exporter->export()->send('items.csv');
    }
}
```

## Splitting Result into Multiple Files

If exporting a large dataset, you may need to split it into multiple files:

```php
use drcsystems\yiicsvgrid\CsvGrid;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'maxEntriesPerFile' => 60000,
]);
$exporter->export()->saveAs('/path/to/archive-file.zip');
```

## Customizing Output Format

You can customize the delimiter, row separator, and enclosure:

```php
use drcsystems\yiicsvgrid\CsvGrid;

$exporter = new CsvGrid([
    'query' => Item::find(),
    'csvFileConfig' => [
        'cellDelimiter' => "\t",
        'rowDelimiter' => "\n",
        'enclosure' => '',
    ],
]);
$exporter->export()->saveAs('/path/to/file.txt');
```

