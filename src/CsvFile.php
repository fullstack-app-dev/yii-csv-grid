<?php

/**
 * @link https://github.com/drcsystems/yii-csv-grid
 * @copyright Copyright (c) 2025 DRC Systems
 * @license [BSD-3-Clause](http://www.opensource.org/licenses/BSD-3-Clause)
 */

namespace drcsystems\yiicsvgrid;

use yii\base\BaseObject;
use yii\base\Exception;
use yii\helpers\FileHelper;

/**
 * CsvFile represents the CSV file.
 *
 * Example:
 * ```php
 * use drcsystems\yiicsvgrid\CsvFile;
 *
 * $csvFile = new CsvFile(['name' => '/path/to/file.csv']);
 * foreach (Item::find()->all() as $item) {
 *     $csvFile->writeRow($item->attributes);
 * }
 * $csvFile->close();
 * ```
 *
 * @author Jitendra Yadav <jitendra@example.com>
 * @since 1.0
 */
class CsvFile extends BaseObject
{
    /**
     * @var string The path of the file.
     */
    public string $name;

    /**
     * @var string Delimiter between the CSV file rows.
     */
    public string $rowDelimiter = "\r\n";

    /**
     * @var string Delimiter between the CSV file cells.
     */
    public string $cellDelimiter = ',';

    /**
     * @var string The cell content enclosure.
     */
    public string $enclosure = '"';

    /**
     * @var int The count of entries written into the file.
     */
    public int $entriesCount = 0;

    /**
     * @var bool|string Whether to write Byte Order Mark (BOM) at the beginning of the file.
     */
    public bool|string $writeBom = false;

    /**
     * @var resource|null File resource handler.
     */
    protected $fileHandler = null;

    /**
     * Destructor.
     * Ensures the opened file is closed.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Opens the related file for writing.
     *
     * @throws Exception on failure.
     * @return bool Success.
     */
    public function open(): bool
    {
        if ($this->fileHandler === null) {
            FileHelper::createDirectory(dirname($this->name));
            $this->fileHandler = fopen($this->name, 'w+');
            if ($this->fileHandler === false) {
                throw new Exception('Unable to create/open file "' . $this->name . '".');
            }
        }
        return true;
    }

    /**
     * Closes the related file if it was opened.
     *
     * @return bool Success.
     */
    public function close(): bool
    {
        if ($this->fileHandler !== null) {
            fclose($this->fileHandler);
            $this->fileHandler = null;
        }
        return true;
    }

    /**
     * Deletes the associated file.
     *
     * @return bool Success.
     */
    public function delete(): bool
    {
        $this->close();
        if (file_exists($this->name)) {
            unlink($this->name);
        }
        return true;
    }

    /**
     * Writes the given row data into the file in CSV format.
     *
     * @param array $rowData The raw data as an array.
     * @return int The number of bytes written.
     */
    public function writeRow(array $rowData): int
    {
        if ($this->writeBom !== false && $this->entriesCount === 0) {
            $bom = is_string($this->writeBom) ? $this->writeBom : pack('CCC', 0xef, 0xbb, 0xbf);
            $this->writeContent($bom);
        }
        $result = $this->writeContent($this->composeRowContent($rowData));
        $this->entriesCount++;
        return $result;
    }

    /**
     * Composes the given data into the CSV row.
     *
     * @param array $rowData Data to be composed.
     * @return string CSV formatted row.
     */
    protected function composeRowContent(array $rowData): string
    {
        $securedRowData = array_map(fn($content) => $this->encodeValue($content), $rowData);

        return ($this->entriesCount > 0 ? $this->rowDelimiter : '') . implode($this->cellDelimiter, $securedRowData);
    }

    /**
     * Secures the given value so it can be written in a CSV cell.
     *
     * @param mixed $value Value to be secured.
     * @return string Secured value.
     */
    protected function encodeValue(mixed $value): string
    {
        $value = (string)$value;

        if ($this->enclosure === '') {
            return $value;
        }

        return $this->enclosure . str_replace($this->enclosure, str_repeat($this->enclosure, 2), $value) . $this->enclosure;
    }

    /**
     * Writes the given content into the file.
     *
     * @param string $content Content to be written.
     * @return int The number of bytes written.
     * @throws Exception on failure.
     */
    protected function writeContent(string $content): int
    {
        $this->open();
        $bytesWritten = fwrite($this->fileHandler, $content);
        if ($bytesWritten === false) {
            throw new Exception('Unable to write to file "' . $this->name . '".');
        }
        return $bytesWritten;
    }
}
