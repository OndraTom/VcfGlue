<?php

use Totem\Parsers\DelimiterParser;

abstract class VcfGlue
{
	const COLUMN_SEPARATOR = "\t";
	
	const RESULT_FILE_NAME = 'result';
	
	
	protected $headerWrote = false;
	
	
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
	
	
	public function __construct(array $files)
	{
		$this->loadParsedFiles($files);
		$this->setHeaders();
	}
	
	
	protected function setHeaders()
	{
		ob_end_clean();
		header('Content-type: text/plain');
		header('Content-Disposition: attachment; filename=' . static::RESULT_FILE_NAME);
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
	
	
	protected function loadParsedFiles(array $files)
	{	
		$filesCount = count($files['name']);
		
		for ($i = 0; $i < $filesCount; $i++)
		{	
			$parsedFile = new DelimiterParser($files['tmp_name'][$i], self::COLUMN_SEPARATOR);
			
			if (empty($parsedFile->parse()))
			{
				throw new GlueException('File ' . $files['name'][$i] . ' has no rows.');
			}
			
			if (!$this->isFileHeaderValid(array_keys($parsedFile->gerRows()[0])))
			{
				throw new GlueException('File ' . $files['name'][$i] . ' has invalid header.');
			}
			
			$this->files[$files['name'][$i]] = $parsedFile->parse();
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
		echo $content;
	}


	protected function saveRow(array $row)
	{	
		if (!$this->headerWrote)
		{
			$this->write(implode("\t", array_keys($row)) . "\n");
			
			$this->headerWrote = true;
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