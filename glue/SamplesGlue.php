<?php

class SamplesGlue extends VcfGlue
{
	const RESULT_FILE_NAME = 'samples_merge.bravo';
	
	
	protected $columns = [];
	
	
	protected function areValidColumns(array $columns)
	{
		if (count($columns) != count($this->columns))
		{
			return false;
		}
		
		for ($i = 0; $i < count($columns); $i++)
		{
			if ($columns[$i] != $this->columns[$i])
			{
				return false;
			}
		}
		
		return true;
	}
	
	
	protected function addResultRow(array $row)
	{	
		$rowKey = $this->getRowKey($row);
		
		if (!isset($this->result[$rowKey]))
		{
			$this->result[$rowKey] = [];
		}
		
		$this->result[$rowKey][] = $row;
	}
	
	
	public function process()
	{
		foreach ($this->files as $fileName => $rows)
		{
			if (count($rows))
			{
				if (empty($this->columns))
				{
					$this->columns = array_keys($rows[0]);
				}
				else if (!$this->areValidColumns(array_keys($rows[0])))
				{
					throw new GlueException('Files have incopatible headers');
				}
				
				foreach ($rows as $row)
				{
					$this->addResultRow($row);
				}
			}
		}
	}
	
	
	public function save()
	{
		foreach ($this->result as $key => $rows)
		{
			$samplesCount = count($rows);
			
			foreach ($rows as $row)
			{
				$row['SAMPLES_COUNT'] = $samplesCount;
				
				$this->saveRow($row);
			}
		}
	}
}