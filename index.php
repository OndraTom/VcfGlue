<?php

/**
 * Totem tool for merging the Variant Callers output files.
 * 
 * @author oto
 */

// Core files import.
require_once(__DIR__ . '/parser/ParserException.php');
require_once(__DIR__ . '/parser/DelimiterParser.php');
require_once(__DIR__ . '/glue/GlueException.php');
require_once(__DIR__ . '/glue/VcfGlue.php');
require_once(__DIR__ . '/glue/VcSampleGlue.php');
require_once(__DIR__ . '/glue/SamplesGlue.php');
require_once(__DIR__ . '/glue/VcfAnnoIn.php');
require_once(__DIR__ . '/glue/VcfGlueFactory.php');

use Totem\Tools\VcfGlueFactory;

// Info messages.
$messages = '';

// Process the form when it's sent.
if (isset($_FILES['files']) && isset($_POST['mergeType']))
{	
	try
	{
		// Create Glue instance (by type).
		$glue = VcfGlueFactory::create($_POST['mergeType'], $_FILES['files']);
	
		// Process, save, download.
		$glue->process();
		$glue->save();
		$glue->download();
	}
	// Make error message.
	catch (Exception $e) 
	{
		$messages .= '<div class="message alert alert-danger">Error: ' . $e->getMessage() . '</div>' . "\n";
	}
}

?>

<!DOCTYPE html>
<html lang="cs" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<link rel="stylesheet" href="./css/bootstrap.min.css">
		<link rel="stylesheet" href="./css/my.css">
		<title>VcfGlue</title>
	</head>
	<body>
		<div class="container">
			<h1>VcfGlue</h1>
			
			<?php echo $messages ?>
			
			<div class="panel panel-default grey">
				<div class="panel-body">
					<form method="post" enctype="multipart/form-data" id="glue-form">
						<div class="form-group">
							<label for="files">Files: </label>
							<input id="files" class="form-control file" name="files[]" type="file" multiple />
						</div>
						<div class="form-group">
							<label for="merge-type">Merge type: </label>
							<select name="mergeType" id="merge-type" class="form-control">
								<option value="vcSample"<?php echo isset($_POST['mergeType']) && $_POST['mergeType'] == 'vcSample' ? ' selected="selected"' : '' ?>>VC sample</option>
								<option value="samples"<?php echo isset($_POST['mergeType']) && $_POST['mergeType'] == 'samples' ? ' selected="selected"' : '' ?>>Samples</option>
								<option value="annoin"<?php echo isset($_POST['mergeType']) && $_POST['mergeType'] == 'annoin' ? ' selected="selected"' : '' ?>>Annoin</option>
							</select>
						</div>
						<button type="submit" class="btn btn-default">Process</button>
					</form>
				</div>
			</div>
			
			<div class="content-footer">Totem | <?php echo date('Y') ?></div>
		</div>
		
		<script src="./js/jquery-2.2.3.min.js"></script>
		<script src="./js/my.js"></script>
	</body>
</html>