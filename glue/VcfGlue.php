<?php

namespace Totem\Tools;

use Totem\Parsers\DelimiterParser;

/**
 * The main ancestor of Glues.
 *
 * @author oto
 */
abstract class VcfGlue
{
	/**
	 * Input files columns separator.
	 */
	const COLUMN_SEPARATOR = "\t";

	/**
	 * Name of the result file.
	 *
	 * Should be overwritten in descendants.
	 */
	const RESULT_FILE_NAME = 'result';


	/**
	 * Indicator of the header write.
	 *
	 * @var bool
	 */
	protected $headerWrote = false;


	/**
	 * List of position columns.
	 *
	 * @var arraz
	 */
	protected $positionColumns = [
		'CHROM',
		'POS',
		'REF',
		'ALT'
	];


	/**
	 * List of mandatory columns.
	 *
	 * @var array
	 */
	protected $mandatoryColumns = [
		'CHROM',
		'POS',
		'REF',
		'ALT'
	];


	/**
	 * Array of parsed input files.
	 *
	 * @var DelimiterParser[]
	 */
	protected $files = [];


	/**
	 * Processed data.
	 *
	 * @var array
	 */
	protected $result = [];


	/**
	 * Input files will be parsed in construction.
	 *
	 * @param	array	$files	Input files.
	 * @throws	ParserException
	 * @throws	GlueException
	 */
	public function __construct(array $files)
	{
		$this->loadParsedFiles($files);
	}


	/**
	 * Returns the row key.
	 *
	 * Row key is composed from position columns.
	 *
	 * @param	array	$row	Input file row.
	 * @return	string
	 */
	protected function getRowKey(array $row)
	{
		$key = '';

		foreach ($this->positionColumns as $positionColumn)
		{
			$key .= $row[$positionColumn];
		}

		return $key;
	}


	/**
	 * Parses input files and saves them into the property.
	 *
	 * @param	array	$files	Input files.
	 * @throws	GlueException
	 * @throws	ParserException
	 */
	protected function loadParsedFiles(array $files)
	{
		if (empty($files) || array_sum($files['size']) <= 0)
		{
			throw new GlueException('No files provided.');
		}

		$filesCount = count($files['name']);

		for ($i = 0; $i < $filesCount; $i++)
		{
			$parsedFile = new DelimiterParser($files['tmp_name'][$i], self::COLUMN_SEPARATOR);

			$parsedFile->parse();

			if (!empty($parsedFile->getRows()) && !$this->isFileHeaderValid(array_keys($parsedFile->getRows()[0])))
			{
				throw new GlueException('File ' . $files['name'][$i] . ' has invalid header.');
			}

			$this->files[$files['name'][$i]] = $parsedFile;
		}

		if (empty($this->files))
		{
			throw new GlueException('No files parsed.');
		}
	}


	/**
	 * Checks if the given header has valid columns.
	 *
	 * @param	array	$header		Examined header.
	 * @return	bool
	 */
	protected function isFileHeaderValid(array $header)
	{
		foreach ($this->mandatoryColumns as $column)
		{
			if (!in_array($column, $header))
			{
				return false;
			}
		}

		return true;
	}


	/**
	 * Processes the input files - prepares the results.
	 */
	abstract public function process();


	/**
	 * Saves the processes data - prepares the output.
	 */
	abstract public function save();


	/**
	 * Downloads the result.
	 */
	abstract public function download();
}