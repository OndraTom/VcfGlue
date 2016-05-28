<?php

namespace Totem\Parsers;

/**
 * Parser for structures with delimiter.
 *
 * @author oto
 */
class DelimiterParser
{
	/**
	 * Columns delimiter.
	 *
	 * @var string
	 */
	protected $delimiter;


	/**
	 * End of the line.
	 *
	 * @var string
	 */
	protected $rowEnd = "\n";


	/**
	 * Path to the file.
	 *
	 * @var string
	 */
	protected $filePath;


	/**
	 * Columns for extraction.
	 *
	 * @var array
	 */
	protected $columns = [];


	/**
	 * File rows.
	 *
	 * @var array
	 */
	protected $rows = [];


	/**
	 * Flag for first row as header.
	 *
	 * @var bool
	 */
	protected $isFirstRowHeader = true;


	/**
	 * Regular expressions for file ignored lines.
	 *
	 * @var array
	 */
	protected $ignoredLinesDetections = [];


	/**
	 * @param	string	$filePath	Path to the file.
	 * @param	string	$delimiter	Columns delimiter.
	 * @throws	ParserException
	 */
	public function __construct($filePath, $delimiter)
	{
		$this->checkFile($filePath);

		$this->filePath		= $filePath;
		$this->delimiter	= $delimiter;
	}


	/**
	 * Checks if the file is real.
	 *
	 * @param	string	$filePath	Path to the file.
	 * @throws	ParserException
	 */
	protected function checkFile($filePath)
	{
		if (!file_exists($filePath))
		{
			throw new ParserException('File "' . $filePath . '" doesn\'t exist.');
		}
	}
	
	
	/**
	 * Returns parsed rows columns.
	 * 
	 * @return array
	 */
	public function getColumns()
	{
		return $this->columns;
	}


	/**
	 * Sets columns for extraction.
	 *
	 * @param array $columns
	 */
	public function setColumns(array $columns)
	{
		$this->columns = $columns;
	}


	/**
	 * Sets columns delimiter.
	 *
	 * @param string $delimiter
	 */
	public function setDelimiter($delimiter)
	{
		$this->delimiter = $delimiter;
	}


	/**
	 * Sets row end.
	 *
	 * @param string $rowEnd
	 */
	public function setRowEnd($rowEnd)
	{
		$this->rowEnd = $rowEnd;
	}


	/**
	 * Sets the first line is header flag.
	 *
	 * @param bool $isFirstRowHeader
	 */
	public function setIsFirstRowHeader($isFirstRowHeader)
	{
		$this->isFirstRowHeader = (bool) $isFirstRowHeader;
	}


	/**
	 * Returns file rows.
	 *
	 * @return array
	 */
	public function getRows()
	{
		return $this->rows;
	}


	/**
	 * Sets regular expressions for ignored lines detection.
	 *
	 * @param array $detections
	 */
	public function setIgnoredLinesDetections(array $detections)
	{
		$this->ignoredLinesDetections = $detections;
	}


	/**
	 * Checks if the file line should be ignored.
	 *
	 * @param	string	$line	File line.
	 * @return	bool
	 */
	protected function isIgnoredLine($line)
	{
		foreach ($this->ignoredLinesDetections as $detection)
		{
			if ($detection($line))
			{
				return true;
			}
		}

		return false;
	}


	/**
	 * Returns mapped columns.
	 *
	 * Maps extracted columns names to file columns indexes.
	 *
	 * @param	array	$header		Header columns.
	 * @return	array
	 * @throws	ParserException
	 */
	protected function getColumnsMap(array $header)
	{
		$map = [];

		foreach ($this->columns as $name => $column)
		{
			$found = false;

			foreach ($header as $i => $fileColumn)
			{
				if ($fileColumn == $column)
				{
					$map[$name]	= $i;
					$found		= true;

					break;
				}
			}

			if (!$found)
			{
				throw new ParserException('Column "' . $column . '" is not defined in the file.');
			}
		}

		return $map;
	}


	/**
	 * Returns extracted columns.
	 *
	 * @param	array	$fileRow		File columns.
	 * @param	array	$columnsMap		Columns map.
	 * @return	array
	 */
	protected function extractColumns(array $fileRow, array $columnsMap)
	{
		$row = [];

		foreach ($columnsMap as $name => $position)
		{
			$row[$name] = $fileRow[$position];
		}

		return $row;
	}


	/**
	 * Chops the file row and sanitize the column values.
	 *
	 * @param	string	$fileRow
	 * @return	array
	 */
	protected function getChoppedRow($fileRow)
	{
		return array_map(
				function ($column) {

					return trim($column);

				},
				explode($this->delimiter, $fileRow)
		);
	}


	/**
	 * Returns file rows (chopped).
	 *
	 * @return array
	 * @throws ParserException
	 */
	public function parse()
	{
		ini_set("auto_detect_line_endings", true);

		$parsedRows		= [];
		$columnsMap		= null;
		$firstLineRead	= false;
		$fileRows		= file($this->filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

		if ($fileRows === false)
		{
			throw new ParserException('Cannot open file for parsing.');
		}

		foreach ($fileRows as $fileRow)
		{
			// Line shouldn't be empty nor ignored.
			if (trim($fileRow) && !$this->isIgnoredLine($fileRow))
			{
				$fileRowChopped = $this->getChoppedRow($fileRow);

				// Managing header.
				if ($this->isFirstRowHeader && !$firstLineRead)
				{
					if (empty($this->columns))
					{
						$this->columns = array_combine($fileRowChopped, $fileRowChopped);
					}

					$columnsMap = $this->getColumnsMap($fileRowChopped);

					// Skipping the first line.
					$firstLineRead = true;
					continue;
				}

				$parsedRows[] = $columnsMap ? $this->extractColumns($fileRowChopped, $columnsMap) : $fileRowChopped;
			}
		}

		$this->rows = $parsedRows;

		return $parsedRows;
	}
}