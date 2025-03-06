<?php
/**
 * @link https://github.com/drcsystems/yiicsvgrid
 * @copyright Copyright (c) 2025 DRC Systems
 * @license [New BSD License](http://www.opensource.org/licenses/bsd-license.php)
 */

namespace drcsystems\yiicsvgrid;

use Yii;
use yii\base\Exception;
use yii\base\BaseObject;
use yii\helpers\FileHelper;
use yii\web\Response;
use ZipArchive;

/**
 * ExportResult represents CSV export result.
 *
 * @see CsvGrid
 *
 * @property string $dirName Temporary files directory name
 * @property string $resultFileName Result file name
 *
 * @author DRC Systems
 * @since 1.1
 */
class ExportResult extends BaseObject
{
    public string $basePath = '@runtime/csv-grid';
    public string $fileBaseName = 'data';
    public array $csvFiles = [];
    public ?callable $archiver = null;
    public bool $forceArchive = false;

    private ?string $_dirName = null;
    private ?string $_resultFileName = null;

    public function __destruct()
    {
        $this->delete();
    }

    public function getDirName(): string
    {
        return $this->_dirName ??= Yii::getAlias($this->basePath) . DIRECTORY_SEPARATOR . uniqid('', true);
    }

    public function setDirName(string $dirName): void
    {
        $this->_dirName = $dirName;
    }

    public function getResultFileName(): string
    {
        if ($this->_resultFileName === null && !empty($this->csvFiles)) {
            if (count($this->csvFiles) > 1 || $this->forceArchive) {
                $this->_resultFileName = $this->archiveFiles(array_map(fn($file) => $file->name, $this->csvFiles));
            } else {
                $this->_resultFileName = reset($this->csvFiles)->name;
            }
        }
        return $this->_resultFileName;
    }

    public function newCsvFile(array $config = []): CsvFile
    {
        $selfFileName = sprintf('%s-%03d.csv', $this->fileBaseName, count($this->csvFiles) + 1);
        $file = Yii::createObject(array_merge(['class' => CsvFile::class], $config));
        $file->name = $this->getDirName() . DIRECTORY_SEPARATOR . $selfFileName;
        $this->csvFiles[] = $file;
        return $file;
    }

    public function delete(): bool
    {
        if ($this->_dirName) {
            $this->csvFiles = [];
            FileHelper::removeDirectory($this->_dirName);
            return true;
        }
        return false;
    }

    public function copy(string $destinationFileName): bool
    {
        return copy($this->getResultFileName(), $this->prepareDestinationFileName($destinationFileName));
    }

    public function move(string $destinationFileName): bool
    {
        $destination = $this->prepareDestinationFileName($destinationFileName);
        $result = rename($this->getResultFileName(), $destination);
        $this->delete();
        return $result;
    }

    public function saveAs(string $file, bool $deleteTempFile = true): bool
    {
        return $deleteTempFile ? $this->move($file) : $this->copy($file);
    }

    public function send(?string $name = null, array $options = []): Response
    {
        $response = Yii::$app->getResponse();
        $response->on(Response::EVENT_AFTER_SEND, [$this, 'delete']);
        return $response->sendFile($this->getResultFileName(), $name, $options);
    }

    protected function prepareDestinationFileName(string $destinationFileName): string
    {
        $destination = Yii::getAlias($destinationFileName);
        FileHelper::createDirectory(dirname($destination));
        return $destination;
    }

    protected function archiveFiles(array $files): string
    {
        if ($this->archiver) {
            return ($this->archiver)($files, $this->getDirName());
        }

        $archiveFileName = $this->getDirName() . DIRECTORY_SEPARATOR . $this->fileBaseName . '.zip';
        $zip = new ZipArchive();

        if ($zip->open($archiveFileName, ZipArchive::CREATE) !== true) {
            throw new Exception('Unable to create ZIP archive.');
        }

        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();

        return $archiveFileName;
    }
}