<?php

use Totem\Parsers\DelimiterParser;

abstract class VcfGlue
{
	const COLUMN_SEPARATOR = "\t";
	
	const RESULT_FILE_NAME = 'result';
	
	
	protected $resultFilePath;
	
	
	protected $positionColumns = [
		'CHROM',
		'POS',
		'REF',
		'ALT'
	];
	
	
	protected $mandatoryColumns = [
		'CHROM',
		'POS',
		'REF',
		'ALT'
	];
	
	
	protected $files = [];
	
	
	protected $result = [];
	
	
	public function __construct($directory, $extension)
	{
		$this->loadParsedFiles($directory, $extension);
		
		$this->resultFilePath = $this->getSanitizedDirName($directory) . static::RESULT_FILE_NAME;
	}
	
	
	protected function getSanitizedDirName($dirName)
	{
		if (substr($dirName, -1) != DIRECTORY_SEPARATOR)
		{
			$dirName .= DIRECTORY_SEPARATOR;
		}
		
		return $dirName;
	}
	
	
	protected function getRowKey(array $row)
	{
		$key = '';
		
		foreach ($this->positionColumns as $positionColumn)
		{
			$key .= $row[$positionColumn];
		}
		
		return $key;
	}
	
	
	protected function loadParsedFiles($directory, $extension)
	{	
		if (!file_exists($directory) || !is_dir($directory))
		{
			throw new GlueException('Invalid directory');
		}
		
		foreach (new DirectoryIterator($directory) as $file)
		{
			if ($file->isFile() && $file->getExtension() == $extension)
			{
				$parsedFile = new DelimiterParser($file->getRealPath(), self::COLUMN_SEPARATOR);
				
				if (empty($parsedFile->parse()))
				{
					throw new GlueException('File ' . $file->getBasename() . ' has no rows.');
				}
				
				if (!$this->isFileHeaderValid(array_keys($parsedFile->gerRows()[0])))
				{
					throw new GlueException('File ' . $file->getBasename() . ' has invalid header.');
				}
				
				$this->files[$file->getBasename()] = $parsedFile->parse();
			}
		}
		
		if (empty($this->files))
		{
			throw new GlueException('No files parsed.');
		}
	}
	
	
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
	
	
	protected function write($content)
	{
		if (!file_put_contents($this->resultFilePath, $content, FILE_APPEND))
		{
			throw new GlueException('Saving failed.');
		}
	}


	protected function saveRow(array $row)
	{	
		if (!file_exists($this->resultFilePath))
		{
			$this->write(implode("\t", array_keys($row)) . "\n");
		}
		
		$this->write(implode("\t", $row) . "\n");
	}
	
	
	public function getResult()
	{
		return $this->result;
	}
	
	
	abstract public function process();
	
	
	abstract public function save();
}