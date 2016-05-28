<?php

namespace Totem\Tools;

use ZipArchive;

/**
 * @author oto
 */
class VcfAnnoIn extends VcfGlue
{
	/**
	 * Result file extension.
	 */
	const RESULT_FILE_EXT = '.annoin';
	
	/**
	 * Result files ZIP name.
	 */
	const ZIP_NAME = 'annoin';
	
	
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
	 * Output files contents.
	 *
	 * @var array
	 */
	protected $outputFiles = [];
	
	
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
			foreach ($parsedFile->getRows() as $row)
			{
				$this->addResultRow($fileName, $row);
			}
		}
	}
	
	
	protected function addResultRow($fileName, array $row)
	{
		$ref		= $row['REF'];
		$alt		= $row['ALT'];
		$refLength	= strlen($ref);
		$altLength	= strlen($alt);
		$end		= $row['POS'];
		
		if ($refLength > $altLength)
		{
			$ref = $this->substractNts($ref, $alt);
			$alt = '-';
			$end += strlen($ref) - 1;
		}
		else if ($refLength < $altLength)
		{
			$alt = $this->substractNts($alt, $ref);
			$ref = '-';
		}
		
		$row['END'] = $end;
		$row['REF'] = $ref;
		$row['ALT'] = $alt;
		
		if (!isset($this->result[$fileName]))
		{
			$this->result[$fileName] = [];
		}
		
		$this->result[$fileName][] = $row;
	}
	
	
	protected function substractNts($minuend, $subtrahend)
	{
		if (strlen($subtrahend) != 1)
		{
			throw new GlueException('Subrahend doesnt have one nt.');
		}
		
		if (substr($minuend, 0, 1) != $subtrahend)
		{
			throw new GlueException('First minuend nt is not equal to subtrahend.');
		}
		
		return substr($minuend, 1);
	}


	/**
	 * Saves the processes data - prepares the output.
	 */
	public function save()
	{
		foreach ($this->result as $fileName => $rows)
		{
			foreach ($rows as $row)
			{
				$row = $this->getOrderedRow($row);
				
				if (!isset($this->outputFiles[$fileName]))
				{
					$this->outputFiles[$fileName] = implode("\t", array_keys($row)) . "\n";
				}
				
				$this->outputFiles[$fileName] .= implode("\t", $row) . "\n";
			}
		}
	}
	
	
	protected function getOrderedRow(array $row)
	{
		$orderedRow = [
			'CHROM' => $row['CHROM'],
			'POS'	=> $row['POS'],
			'END'	=> $row['END'],
			'REF'	=> $row['REF'],
			'ALT'	=> $row['ALT']
		];
		
		unset($row['CHROM'], $row['POS'], $row['END'], $row['REF'], $row['ALT']);
		
		return array_merge($orderedRow, $row);
	}


	/**
	 * Downloads the result.
	 */
	public function download()
	{
		$this->downloadZip();
		
		die;
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

		foreach ($this->outputFiles as $fileName => $content)
		{
			$file = tempnam(sys_get_temp_dir(), $fileName);

			file_put_contents($file, $content);

			$tmpFiles[] = $file;

			$zip->addFile($file, $fileName . self::RESULT_FILE_EXT);
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
}