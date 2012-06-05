<?php
set_time_limit(180);

//Get file names from Jquerys ajax call
$listingsFile = "listings/".$_POST['listings'];
$productsFile = "products/".$_POST['products'];

//listings related variables
$listingsTxt = fopen($listingsFile, "r");
$listingsBlob = fread($listingsTxt, filesize($listingsFile));
$listingsArray =  preg_split("/\\n/", $listingsBlob);
$listingsGroupedByManuf = array();

//products related variables
$productsTxt = fopen($productsFile, "r");
$productsBlob = fread($productsTxt, filesize($productsFile));
$productsArray =  preg_split("/\\n/", $productsBlob);
$prodsGroupedByManuf = array();

//other global variables
$wordsToIgnore = array('canada', 'ca', 'usa', 'uk', 'ltd', 'gmbh', 'international', 'group', 'co', 'electronics', 'zoom', 'inc', '', 'supplied', 'by', 'electronic', 'consumer', 'communication', 'camera', 'cameras', 'digital', 'technology', 'computer', 'computers', 'direct', 'technologies', 'entertainment', 'corp', 'photo', 'the', 'solutions', 'system' ,'systems', 'division', 'phones', 'Technik');
$listingIDCount = 0;
$showItemsWithoutMatches = 0;
$output = array();
$numberOfMatches = array();


/******* a function to replace any odd charactors with a space, remove -, and split on spaces *********/
function scrubStr($nameStr, $exceptions){

	if($exceptions != ''){
		if(is_array($exceptions)){
			foreach($exceptions as $exception){
				$newNameStr = preg_replace('/'.$exception.'/', ' ', $nameStr); 				//remove model name from prdouct description/title
			}
		}else{
			$newNameStr = preg_replace('/'.$exceptions.'/', ' ', $nameStr); 				//remove model name from prdouct description/title
		}
	}
	$newNameStr = preg_replace('/-\s|\s-|\.|,|"|\'|\(|\)|\&|\+|\//', ' ', $nameStr); 		//remove common charactors that cause miss matches
	$newNameStr = preg_replace('/-/', '', $newNameStr); 									// remove -'s. ie. tuff-lov becomes tufflov
	$newNameStr = strtolower($newNameStr);
	$nameArray = preg_split('/\s/', $newNameStr);
	
	return $nameArray;
}

/******* group all product listings by manufacturer *********/
foreach($listingsArray as $i){
	if($i != null){
		$listings=json_decode($i, true);								//split appart JSON variable
		$listings['listingID'] = $listingIDCount; 						//add a product listin ID so the same listing doesn't get matched twice.
		$manufNameLC = strtolower($listings['manufacturer']); 			//change manufacturer's name in lower case
		
		$manufStr = scrubStr($manufNameLC, '');							//run through funtion to remove odd charactors and brake the str into an array of little str's
		
		foreach($manufStr as $menufWord){
			if(!in_array($menufWord, $wordsToIgnore)){
				if (!isset($listingsGroupedByManuf[$menufWord])){
					$listingsGroupedByManuf[$menufWord] = array();
				}
				array_push($listingsGroupedByManuf[$menufWord], $listings); 
			}
		}
		$listingIDCount++;
	}
}

/******* Parse the products file and split out all items and group under there manufacturers **********/
foreach($productsArray as $i){
	if($i != null){
		$prod=json_decode($i, true);
		if (!isset($prodsGroupedByManuf[$prod['manufacturer']])){
			$prodsGroupedByManuf[$prod['manufacturer']] = array();
		}
		array_push($prodsGroupedByManuf[$prod['manufacturer']], $prod); 
	}
}

/******* matches products with listings  **********/
foreach($prodsGroupedByManuf as $manufacturersProds){   								//loop through all manufacturers
	foreach($manufacturersProds as $products){											//loop through all products for current manuf
		$productsManuf = scrubStr($products['manufacturer'], '');
		if(isset($products['family'])){
			$productsFamilies = scrubStr($products['family'], '');
			$productsModles = scrubStr($products['model'], $productsFamilies);
		}else{
			$productsModles = scrubStr($products['model'], '');
		}
		$output[$products['product_name']] = array();
		foreach($productsManuf as $manuf){												//if a manufacturer has more then one word in tehre name loop through the listing under each word
			if(isset($listingsGroupedByManuf[strtolower($manuf)])){						//check if there are any listings that have the current manufacturer
				foreach($listingsGroupedByManuf[strtolower($manuf)] as $listings){		//loop throug all listings under current manuf
					$matches = 0;
					$matchLength = 0;
					$listingTitle = strtolower($listings['title']);
					$listingTitle = preg_replace('/-/', '', $listingTitle);
	
					for($i = 0; $i < count($productsModles); $i++){							//find a match for the model info
						$pattern = '';
						if(preg_match('/\d/', $productsModles[$i]) && $i == 0){				//if a listing only has 1 keyword and that keyword ends in a number then the listing must match the keyword with a space or nothing infront of it and anything but a digit behind it
							$pattern .= '\s'.$productsModles[$i].'\D|^'.$productsModles[$i].'\D';
						}else if(preg_match('/\d/', $productsModles[$i])){					//if the keyword ends with a number then its match must have a non number char behind it
							$pattern .= $productsModles[$i].'\D';
						}else if($i == 0){													//if this is the first keyword for a listing then there cant be anything infront of it but a space
							$pattern .= '\s'.$productsModles[$i].'|^'.$productsModles[$i];
						}else{																//else just match the keyword
							$pattern .= $productsModles[$i];
						}
						if(preg_match('/'.$pattern.'/', $listingTitle)){					//check if the model number for current listing is in the title of any listings
							$matches++;
							$matchLength += strlen($productsModles[$i]);
						}
					}
					if($matches == count($productsModles)){									//if all they keywords for a product match the move on to checking the products family name
						$numOfFamilyMatches = 0;
						foreach($productsFamilies as $family){	
							if(preg_match('/'.$family.'/', $listingTitle)){					//check each part of the family name
								$numOfFamilyMatches++;
							}
						}
						if($numOfFamilyMatches == count($numOfFamilyMatches)){				//if all parts of the family name match then move on
							if(isset($numberOfMatches[$listings['listingID']]) && $products['product_name'] != $numberOfMatches[$listings['listingID']]['name']){ 	//check if there is already a match for this product
								if($matchLength >= $numberOfMatches[$listings['listingID']]['matchLength']){						//if there is already a match for this product check what match has more charactors in common
									for($i = 0; $i < count($output[$numberOfMatches[$listings['listingID']]['name']]); $i++){
										$outputListings = $output[$numberOfMatches[$listings['listingID']]['name']];
										if(isset($outputListings[$i])){
											$outputListings = $outputListings[$i];
											if($outputListings['listingID'] == $listings['listingID']){
												if(isset($outputListings)){ 														//check if there is still a listing incase there is an instace a listing matched 3 products
													unset($output[$numberOfMatches[$listings['listingID']]['name']][$i]);
													$outputListings = null;
													$i = count($output[$numberOfMatches[$listings['listingID']]['name']]) + 1;
												}
												if($matchLength > $numberOfMatches[$listings['listingID']]['matchLength']){			//if both matches were the same length then dont re-add the product
													unset($numberOfMatches[$listings['listingID']]);
													$numberOfMatches[$listings['listingID']] = array('name' => $products['product_name'], 'matchLength' => $matchLength);
													array_push($output[$products['product_name']], $listings);						//adds the matched lisitngs to the current products array
												}
											}
										}
									}
								}
							}else{
								$numberOfMatches[$listings['listingID']] = array('name' => $products['product_name'], 'matchLength' => $matchLength);
								array_push($output[$products['product_name']], $listings);						//adds the matched lisitngs to the current products array
							}
						}
					}
				}
			}
		}
	}
}

//********** Display all results as formated text ****************//
echo "<a href='promptDownload.php'><button>Download</button></a><br /><br />";
echo "<div id='output'>";
foreach($output as $products => $listings){
	echo $products.'<br />';
	foreach($listings as $listing){
		echo "---- title: ".$listing['title'].", manufacturer: ".$listing['manufacturer'].", currency: ".$listing['currency'].", price: <span class='price'>".$listing['price']."</span><br />";
	}
	echo "<br />";
}
echo "</div>";


//********** saves results as JSON strings txt file ****************//
$fh = fopen('output.txt', 'w') or die("can't open file");
foreach($output as $products => $listings){
	for($i = 0; $i < count($listings); $i++){
		unset($listings[$i]['listingID']);
	}
	fwrite($fh, '{"product_name":"'.$products.'","listings":"'.json_encode($listings).'"'."\n");	
}
fclose($fh);

?>	