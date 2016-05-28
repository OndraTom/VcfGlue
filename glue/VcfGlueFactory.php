<?php

namespace Totem\Tools;

/**
 * VcfGlue objects factory.
 * 
 * @author oto
 */
class VcfGlueFactory
{
	/**
	 * Glues types.
	 */
	const VC_SAMPLE = 'vcSample';
	const SAMPLES	= 'samples';
	const ANNOIN	= 'annoin';
	
	
	/**
	 * Making instances is forbidden.
	 */
	private function __construct() {}
	
	
	/**
	 * Creates and returns the VcfGlue instance.
	 * 
	 * @param	type	$glueType	Glue type.
	 * @param	array	$files		Input files.
	 * @return	VcfGlue
	 * @throws	ParserException
	 * @throws	GlueException
	 */
	public static function create($glueType, array $files)
	{
		switch ($glueType)
		{
			case self::VC_SAMPLE:
				return new VcSampleGlue($files);
				
			case self::SAMPLES:
				return new SamplesGlue($files);
				
			case self::ANNOIN:
				return new VcfAnnoIn($files);
				
			default:
				throw new GlueException('Unknown VcfGlue type "' . $glueType . '"');
		}
	}
}