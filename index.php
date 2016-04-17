<!DOCTYPE html>
<html lang="cs" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<title>VcfGlue</title>
	</head>
	<body>
		<h1>VcfGlue</h1>
		
		<form method="post" enctype="multipart/form-data">
			<div>
				<label for="files">Files: </label>
				<input id="files" name="files[]" type="file" multiple />
			</div>
			<div>
				<label for="merge-type">Merge type: </label>
				<select name="mergeType" id="merge-type">
					<option value="vcSample"<?php echo isset($_POST['mergeType']) && $_POST['mergeType'] == 'vcSample' ? ' selected="selected"' : '' ?>>VC sample</option>
					<option value="samples"<?php echo isset($_POST['mergeType']) && $_POST['mergeType'] == 'samples' ? ' selected="selected"' : '' ?>>Samples</option>
				</select>
			</div>
			<div>
				<input type="submit" value="process" />
			</div>
		</form>
	</body>
</html>


<?php

require_once(__DIR__ . '/parser/ParserException.php');
require_once(__DIR__ . '/parser/DelimiterParser.php');
require_once(__DIR__ . '/glue/GlueException.php');
require_once(__DIR__ . '/glue/VcfGlue.php');
require_once(__DIR__ . '/glue/VcSampleGlue.php');
require_once(__DIR__ . '/glue/SamplesGlue.php');

if (isset($_FILES['files']) && isset($_POST['mergeType']))
{	
	try
	{
		if ($_POST['mergeType'] == 'vcSample')
		{
			$glue = new VcSampleGlue($_FILES['files']);
		}
		else
		{
			$glue = new SamplesGlue($_FILES['files']);
		}
	
		$glue->process();
		$glue->save();
	}
	catch (Exception $e) 
	{
		echo '<div>Error: ' . $e->getMessage() . '</div>';
	}
}