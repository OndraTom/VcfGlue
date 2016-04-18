<?php

/**
 * Sample pieces glue.
 * 
 * @author oto
 */
class VcSampleGlue extends VcfGlue
{
	/**
	 * Name of the output file.
	 */
	const RESULT_FILE_NAME = 'vc_sample_merge.csv';
	
	
	/**
	 * Name of the sample.
	 * 
	 * @var string
	 */
	protected $sample;
	
	
	/**
	 * List of the file columns.
	 * 
	 * [
	 *		fileName => [columns],
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
	 * Separates the input file name into sample and prefix.
	 * 
	 * @param	string	$fileName	File name.
	 * @return	array
	 * @throws	GlueException
	 */
	protected function getFileNameParts($fileName)
	{
		$parts = explode('_', $fileName);
		
		if (count($parts) < 2)
		{
			throw new GlueException('Invalid file name - sample hasn\'t been provided.');
		}
		
		return [
			array_pop($parts),
			implode('_', $parts)
		];
	}
	
	
	/**
	 * Adds the file row into the result.
	 * 
	 * It also fills the file columns array.
	 * 
	 * @param	string	$name	File name.
	 * @param	array	$row	File row.
	 */
	protected function addResultRow($name, array $row)
	{
		if (!isset($this->fileColumns[$name]))
		{
			$this->fileColumns[$name] = array_keys($row);
		}
		
		$rowKey = $this->getRowKey($row);
				
		if (!isset($this->result[$rowKey]))
		{
			$this->result[$rowKey] = [];
		}
		
		$this->result[$rowKey][$name] = $row;
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
		foreach ($this->files as $fileName => $rows)
		{	
			list($name, $sample) = $this->getFileNameParts($fileName);
			
			if (!isset($this->sample))
			{
				$this->sample = $sample;
			}
			else if ($this->sample != $sample)
			{
				throw new GlueException('File sample mismatch.');
			}
			
			foreach ($rows as $row)
			{
				$this->addResultRow($name, $row);
			}
		}
		
		$this->isProcessed = true;
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
	 * @param	string	$fileName	File name.
	 * @return	string
	 */
	protected function getEmptyFileRow($fileName)
	{
		$rows = [];
		
		foreach ($this->fileColumns[$fileName] as $column)
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
			throw new GlueException('Cannote save unprocessed data.');
		}
		
		foreach ($this->result as $key => $files)
		{
			$outputRow = $this->getKeyPositionColumns($files);
			
			foreach ($this->fileColumns as $fileName => $columns)
			{
				if (isset($this->result[$key][$fileName]))
				{
					$fileRow = $this->result[$key][$fileName];
					
					$this->addOutputRow($outputRow, $fileRow, $fileName);
				}
				else
				{
					$this->addOutputRow($outputRow, $this->getEmptyFileRow($fileName), $fileName);
				}
			}
			
			$outputRow['VC_DETECTION_COUNT']	= count($this->result[$key]);
			$outputRow['SAMPLE']				= $this->sample;
			
			$this->saveRow($outputRow);
		}
	}
}