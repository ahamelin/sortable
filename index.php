<!DOCTYPE html>
<html>
<head>
	<link href="styles.css" rel="stylesheet" type="text/css">
	<script src="http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.js"></script>
	
	<script type="text/javascript">

		function getOutput(){
			var productsTxt = $("#selectProducts option:selected").text()
			var listingsTxt = $("#selectListings option:selected").text()
			
			alert("it can take a few minutes for the report to appear");
			$.ajax({  type: "POST",  url: "serverSide.php",  data: { products: productsTxt, listings: listingsTxt }}).done(function( msg ) {  
					document.getElementById("output").innerHTML = msg;
				});
		}
	</script>
	
</head>
<body>
<?php
$fileTypes = array("listings", "products");
$files = array();
$uploadInfo;

/******* check if there are any files to be upload and upload them *********/
foreach($fileTypes as $type){
	if(isset($_FILES[$type]['name'][0]) && $_FILES[$type]['name'][0] != ''){
		$fileName = $_FILES[$type]['name'][0];
		$path = $type.'/'.$fileName;
		if(preg_match('/\.txt$/', $fileName)){
			if(move_uploaded_file($_FILES[$type]['tmp_name'][0], $path)) {
				$uploadInfo .= "The file ".basename($fileName)." has been uploaded<br />";
			} else{
				$uploadInfo .= "There was an error uploading the file, please try again!<br />";
			}
		}else{
			$uploadInfo .= "Your file ".$fileName." was not uploaded because it is not a txt file<br />";
		}
	}
}

/******* Look through listings and products folders for txt files to add to the drop down lists *********/
foreach($fileTypes as $folder){
	$files[$folder] = array();
	if ($handle = opendir($folder)) {
		while (false !== ($fileName = readdir($handle))) {
			if (preg_match ('/.txt$/', $fileName)){
				array_push($files[$folder], $fileName); 
			}
		}
		closedir($handle);
	}
}

?>
<!-------------- Create the main page ----------------->
	<h1>Hello <img id='logoImg' src='images/sortable-logo.gif' alt='Sortable' />!</h1>
	<hr>
	<div id='contentBgrd'>
		<div id='content'>
			<form enctype='multipart/form-data' method='post'>
				<h3>Below is an easy way to add new 'listing' and 'product' files:</h3>
				<p>Upload a products file here: <input name="products[]" type="file" /><br />
				Upload a listings file here: <input name="listings[]" type="file" /></p>
				<?php
					if(isset($uploadInfo) && $uploadInfo != ''){
						echo "<p>".$uploadInfo."</p>";
					}
				?>
				<input type="submit" value="upload files" />		
			</form>
			<hr>
			<h3>Please select a products file and a listings file to compare</h3>
			<p>
				Product files: <select id='selectProducts' name='products'>
					<option id='0'>Please select one</option>
					<?php
						foreach($files['products'] as $txtFile){
							echo "<option id='".$txtFile."' name='".$txtFile."'>".$txtFile."</option>";
						}
					?>
				</select>
				Listing file: <select id='selectListings' name='listings'>
					<option id='0'>Please select one</option>
					<?php
						foreach($files['listings'] as $txtFile){
							echo "<option id='".$txtFile."' name='".$txtFile."'>".$txtFile."</option>";
						}
					?></select>
				<button id='run' onclick='getOutput()'> Run Report </button>
			</p>
			<hr>
			<div id='output'>the output and an option to download a txt file will be displayed here</div>
		</div>
	</div>
</body>
</html>