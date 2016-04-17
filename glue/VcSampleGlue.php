<?php

class VcSampleGlue extends VcfGlue
{
	const RESULT_FILE_NAME = 'vc_sample_merge.csv';
	
	
	protected $sample;
	
	
	protected $fileColumns = [];
	
	
	protected $isProcessed = false;
	
	
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
	
	
	protected function getEmptyFileRows($fileName)
	{
		$rows = [];
		
		foreach ($this->fileColumns[$fileName] as $column)
		{
			$rows[$column] = 'N/A';
		}
		
		return $rows;
	}
	
	
	protected function getKeyPositionColumns($keyFiles)
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
	
	
	public function save()
	{
		if (!$this->isProcessed)
		{
			throw new GlueException('Cannote save unprocessed data.');
		}
		
		//$this->deleteResultFile();
		
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
					$this->addOutputRow($outputRow, $this->getEmptyFileRows($fileName), $fileName);
				}
			}
			
			$outputRow['VC_DETECTION_COUNT']	= count($this->result[$key]);
			$outputRow['SAMPLE']				= $this->sample;
			
			$this->saveRow($outputRow);
		}
	}
}