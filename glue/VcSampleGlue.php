<?php

namespace Totem\Tools;

use ZipArchive;

/**
 * Sample pieces glue.
 *
 * @author oto
 */
class VcSampleGlue extends VcfGlue
{
	/**
	 * Result file extension.
	 */
	const RESULT_FILE_EXT = '.csv';

	/**
	 * Result files ZIP name.
	 */
	const ZIP_NAME = 'vc_samples';


	/**
	 * List of the file columns.
	 *
	 * [
	 *		sample => [
	 *			fileName => [columns],
	 *			...
	 *		],
	 *		...
	 * ]
	 *
	 * @var array
	 */
	protected $fileColumns = [];


	/**
	 * Flag of the processed gluing.
	 *
	 * @var bool
	 */
	protected $isProcessed = false;


	/**
	 * Output files contents.
	 *
	 * @var array
	 */
	protected $outputFiles = [];


	/**
	 * Separates the input file name into sample and prefix.
	 *
	 * @param	string	$fileName	File name.
	 * @return	array	[sample, prefix]
	 * @throws	GlueException
	 */
	protected function getFileNameParts($fileName)
	{
		$parts = explode('.', $fileName);

		if (count($parts) < 2)
		{
			throw new GlueException('Invalid file name - sample hasn\'t been provided.');
		}

		return [
			array_shift($parts),
			implode('.', $parts)
		];
	}


	/**
	 * Adds the file row into the result.
	 *
	 * It also fills the file columns array.
	 *
	 * @param	string	$sample		Sample name.
	 * @param	string	$name		File name.
	 * @param	array	$row		File row.
	 */
	protected function addResultRow($sample, $name, array $row)
	{
		if (!isset($this->result[$sample]))
		{
			$this->result[$sample] = [];
		}

		$rowKey = $this->getRowKey($row);

		if (!isset($this->result[$sample][$rowKey]))
		{
			$this->result[$sample][$rowKey] = [];
		}

		$this->result[$sample][$rowKey][$name] = $row;
	}


	/**
	 * Processes the files data.
	 *
	 * It prepares the result.
	 *
	 * @throws GlueException
	 */
	public function process()
	{
		foreach ($this->files as $fileName => $parsedFile)
		{
			list($sample, $name) = $this->getFileNameParts($fileName);

			$this->addFileColumns($sample, $name, $parsedFile->getColumns());
			
			foreach ($parsedFile->getRows() as $row)
			{
				$this->addResultRow($sample, $name, $row);
			}
		}

		$this->isProcessed = true;
	}
	
	
	/**
	 * Inserts the new next file columns.
	 * 
	 * @param	string	$sample
	 * @param	string	$name
	 * @param	array	$columns
	 */
	protected function addFileColumns($sample, $name, array $columns)
	{
		if (!isset($this->fileColumns[$sample]))
		{
			$this->fileColumns[$sample] = [];
		}

		if (!isset($this->fileColumns[$name]))
		{
			$this->fileColumns[$sample][$name] = $columns;
		}
	}


	/**
	 * Adds file row into the output.
	 *
	 * It's gluing file columns consecutively.
	 *
	 * @param	array	$outputRow	The result row.
	 * @param	array	$fileRow	File row.
	 * @param	string	$prefix		File columns prefix.
	 */
	protected function addOutputRow(array &$outputRow, array $fileRow, $prefix)
	{
		foreach ($fileRow as $column => $value)
		{
			if (!in_array($column, $this->positionColumns))
			{
				$outputRow[$prefix . '_' . $column] = $value;
			}
		}
	}


	/**
	 * Returns empty file columns.
	 *
	 * @param	string	$sample		Sample name.
	 * @param	string	$fileName	File name.
	 * @return	string
	 */
	protected function getEmptyFileRow($sample, $fileName)
	{
		$rows = [];

		foreach ($this->fileColumns[$sample][$fileName] as $column)
		{
			$rows[$column] = 'N/A';
		}

		return $rows;
	}


	/**
	 * Returns position columns.
	 *
	 * Takes first file columns on position and returns its data.
	 *
	 * @param	array	$keyFiles	Files data on particular position.
	 * @return	array
	 */
	protected function getKeyPositionColumns(array $keyFiles)
	{
		$columns = [];

		foreach ($keyFiles as $fileName => $fileRow)
		{
			foreach ($this->positionColumns as $column)
			{
				$columns[$column] = $fileRow[$column];
			}

			return $columns;
		}

		return $columns;
	}


	/**
	 * Saves the result data.
	 *
	 * Prepares the output.
	 *
	 * @throws GlueException
	 */
	public function save()
	{
		if (!$this->isProcessed)
		{
			throw new GlueException('Cannot save unprocessed data.');
		}

		if (empty($this->result))
		{
			throw new GlueException('Result is empty.');
		}

		foreach ($this->result as $sample => $positions)
		{
			$this->outputFiles[$sample] = '';

			foreach ($positions as $key => $files)
			{
				$outputRow = $this->getKeyPositionColumns($files);

				foreach ($this->fileColumns[$sample] as $fileName => $columns)
				{
					$fileRow = isset($this->result[$sample][$key][$fileName])
							? $this->result[$sample][$key][$fileName]
							: $this->getEmptyFileRow($sample, $fileName);

					$this->addOutputRow(
							$outputRow,
							$fileRow,
							$fileName
					);
				}

				$outputRow['VC_DETECTION_COUNT']	= count($this->result[$sample][$key]);
				$outputRow['SAMPLE']				= $sample;

				if ($this->outputFiles[$sample] == '')
				{
					$this->outputFiles[$sample] .= implode("\t", array_keys($outputRow)) . "\n";
				}

				$this->outputFiles[$sample]	.= implode("\t", $outputRow) . "\n";
			}
		}
	}


	/**
	 * Downloads single result file.
	 *
	 * @param	string	$fileName	Result file name.
	 * @param	string	$content	Result file content.
	 */
	protected function downloadFile($fileName, $content)
	{
		ob_end_clean();

		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename=' . $fileName);

		echo $content;
	}


	/**
	 * Downloads result files in ZIP.
	 */
	protected function downloadZip()
	{
		$zipName	= tempnam(sys_get_temp_dir(), self::ZIP_NAME);
		$tmpFiles	= [$zipName];
		$zip		= new ZipArchive;

		$zip->open($zipName, ZipArchive::CREATE);

		foreach ($this->outputFiles as $sample => $content)
		{
			$sampleFile = tempnam(sys_get_temp_dir(), $sample);

			file_put_contents($sampleFile, $content);

			$tmpFiles[] = $sampleFile;

			$zip->addFile($sampleFile, $sample . self::RESULT_FILE_EXT);
		}

		$zip->close();

		header('Content-Type: application/zip');
		header('Content-disposition: attachment; filename=vc_samples.zip');
		header('Content-Length: ' . filesize($zipName));

		readfile($zipName);

		// Remove all created files.
		foreach ($tmpFiles as $tmpFile)
		{
			unlink($tmpFile);
		}
	}


	/**
	 * Envokes the result files download.
	 *
	 * @throws GlueException
	 */
	public function download()
	{
		$filesCount = count($this->outputFiles);

		if (!$filesCount)
		{
			throw new GlueException('No files for download.');
		}

		if ($filesCount == 1)
		{
			$sample = array_keys($this->outputFiles)[0];

			$this->downloadFile($sample . self::RESULT_FILE_EXT, $this->outputFiles[$sample]);
		}
		else
		{
			$this->downloadZip();
		}

		die;
	}
}