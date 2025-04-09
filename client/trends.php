<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

//timeline.php

//Sample of top n words for bubble cloud
$sample = 25;

//Counter for unique IDs
$uniqueid = 1;

//Maximum number of words to compare
$maxKeywords = 5;
$tooManyWords = false;

$duplicateKeyWord = false;

//Start by generating a time 4 weeks ago
//$timestamp = strtotime("-27 days"); //today is also a day
//Then get the date supplied or use the default. 
if(!isset($_REQUEST['startDate'])){ $startDate = date('Y-m-d',strtotime("-27 days")); }else{ $startDate = date('Y-m-d',strtotime($_REQUEST['startDate'])); }
if(!isset($_REQUEST['endDate'])){ $endDate = date('Y-m-d',time()); }else{ $endDate = date('Y-m-d',strtotime($_REQUEST['endDate'])); }

//Search phrases
if(isset($keywords)){
    //We have a keyword array!
    foreach($keywords as $key=>$keyword){
        $keywords[$key] = strtolower(htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'));
    }
}
elseif(!isset($_REQUEST['keywords'])){
	$keywords = array();
}
else{
	$keywords =  explode(',',htmlspecialchars($_REQUEST['keywords'], ENT_QUOTES, 'UTF-8'));
}

//Check for too many keywords from the address
if(count($keywords)>$maxKeywords){ //don't add the term, display a "too many words" box below.
    $tooManyWords = true;
    $keywords = array_slice($keywords,0,$maxKeywords);
}


//Sort the keywords to maintain cannonical URLs
sort($keywords);

//New search phrase
if(isset($_REQUEST['addkeyword']) && trim($_REQUEST['addkeyword']) != ''){
    if(count($keywords)>$maxKeywords){ //don't add the term, display a "too many words" box below.
	$tooManyWords = true;
    }
    elseif(in_array(strtolower(htmlspecialchars($_REQUEST['addkeyword'], ENT_QUOTES, 'UTF-8')),$keywords)){
        $duplicateKeyWord = true;
    }
    else{
	    $keywords[] = strtolower(htmlspecialchars($_REQUEST['addkeyword'], ENT_QUOTES, 'UTF-8'));
	    sort($keywords);
	    $linkURL = str_replace("//","/",$localSitePath . implode("/",$keywords) . "?startDate=$startDate&endDate=$endDate");
	    header("Location: " . $linkURL);
	    exit;
    }
}

//Strip any empty keywords
foreach($keywords as $key=>$word){
	if(trim($word)==''){
		unset($keywords[$key]);
	}
}

//Strip any noise words
foreach($keywords as $key=>$word){
	if(in_array($word,$noisewords)){
		unset($keywords[$key]);
	}
}

//Make array of dates for duration.
$days = array();
$timestamp = strtotime($startDate);
$endTimestamp = strtotime($endDate);
for ($weekday=1; $timestamp<=$endTimestamp; $weekday++){
    $day = date('Y-m-d',$timestamp);
    $days[] = $day;
    $timestamp += (24*60*60);
}

$frequencies = array();
$collocates = array();
$filecache = array();
foreach($keywords as $keyword){
	$collocates[$keyword] = array();
	$frequencies[$keyword] = array();
	foreach($days as $day){
		$timestamp = strtotime($day);
		$filepath = $corpuspath . str_replace(" ","/",$keyword) . "/";
		$filenameroot = date('M',$timestamp) . "-" . date('Y',$timestamp) . "-"  . str_replace(" ","-",$keyword);
		$collocatefilename = $filenameroot . "-collocates.json";
		$frequencyfilename = $filenameroot . "-frequencies.json";
		
		if(!isset($filecache[$collocatefilename])){
			@$collocatefile = json_decode(file_get_contents($filepath . $collocatefilename),true);
			$filecache[$collocatefilename] = 1;
			if(is_array($collocatefile)){
                            foreach($collocatefile as $collocate=>$collocatefrequency){
				if(!isset($collocates[$keyword][$collocate])){
					$collocates[$keyword][$collocate] = $collocatefrequency;
				}
				else{
					$collocates[$keyword][$collocate] += $collocatefrequency;
				}
                            }
			}

		}
		if(!isset($filecache[$frequencyfilename])){
			@$frequencyfile = json_decode(file_get_contents($filepath . $frequencyfilename),true);
			$filecache[$frequencyfilename] = $frequencyfile;
		}
		else{
			$frequencyfile = $filecache[$frequencyfilename];
		}
		$todaysfrequency = $frequencyfile[date('j',$timestamp)];
		if(!isset($frequencies[$keyword][$word])){
			$frequencies[$keyword][$day] = $todaysfrequency;
		}
		else{
			$frequencies[$keyword][$day] .= $todaysfrequency;
		}
	}
}


//Sort the collocate arrays, cut to sample size, remove noise
$newcollocates = array();
foreach($collocates as $keyword =>$carray){
	arsort($carray);
	$temp = array();
	$i=0;
	foreach($carray as $key=>$value){
		if(!in_array($word,$noisewords)){
			$temp[$key] = $value;
			if($i==$sample){
				break;
			}
			else{
				$i++;
			}
		}
	}
	$newcollocates[$keyword] = $temp;
}
$collocates = $newcollocates;


//Function to make an HTML set of lists from corpus data in an array
//Will need to limit to <6
//Source is the source data, id ids the id of the containing div
function listTimelineData($source,$id){
	global $uniqueid,$localSitePath;

	global $startDate,$endDate,$keywords;

	$keywordList = array_keys($source);
	$datelist = array_keys($source[$keywordList[0]]);

	echo '<div class="row">' . "\n";
        echo '<form method="get" class="form-inline" role="form">' . "\n"; 
	echo '  <div class="col-xs-5 col-sm-3 col-md-3 form-group">' . "\n"; 
	echo '  <input type="date" name="startDate" class="form-control " value="' . $startDate . '"> <label for="startDate">Start date</label>' . "\n"; 
	echo '  </div>' . "\n"; 
	echo '  <div class="col-xs-5 col-sm-3 col-md-3 form-group">' . "\n"; 
	echo '  <input type="date" name="endDate" class="form-control " value="' . $endDate . '"> <label for="endDate">End date</label>' . "\n"; 
	echo '  </div>' . "\n"; 
	echo '  <div class="col-xs-5 col-sm-3 col-md-3">';
	echo '  <input type="text" name="addkeyword" class="form-control " placeholder="e.g. spending cuts"> <label for="addkeyword">Add another word</label>' . "\n"; 
	echo '  </div>' . "\n"; 
	echo '  <div class="col-xs-2 col-sm-3 col-md-1 form-group">' . "\n"; 
	echo '  <button type="submit" class="btn btn-primary btn-sm"><span class="glyphicon glyphicon-plus"></span> Update</button>' . "\n"; 
	echo '  </div>' . "\n"; 
	echo '</form>' . "\n";
	echo "</div>\n";

	//Make data lists
	echo '<div class="row" id="' . $id . '">' . "\n"; // tab-content
	foreach($source as $keyword=>$data){
		echo '<div class="col-md-2" id="' . str_replace(" ","-",$keyword) . '-' . time() . '-' . $uniqueid . '">' . "\n";
		echo '<h2>' . $keyword . '</h2>' . "\n";
		echo '<ul>' . "\n";
		foreach($data as $item=>$figure){
			echo '<li>' . $item . ':' . $figure . '</li>' . "\n";
		}
		echo '</ul>' . "\n";
		echo "</div>\n";
		$uniqueid++;
	}
	echo "</div>\n";

	//Make add/remove word row
	echo '<div class="row">' . "\n";
	echo '<div class="col-xs-12 col-sm-12 col-md-12">';
	
	foreach($source as $keyword=>$data){
		$thisKeywordList = $keywordList;
		$thisKey = array_search ($keyword,$thisKeywordList);		
		unset($thisKeywordList[$thisKey]);
		echo ' <b>' . $keyword . "</b>\n"; 
                $linkURL = $localSitePath . implode("/",$thisKeywordList) . "?startDate=$startDate&endDate=$endDate ";
		echo '<a href="' . $linkURL . '" class="btn btn-primary btn-sm">' . "\n"; 
		echo '<span class="glyphicon glyphicon-remove-circle"></span> Remove ' . "\n"; 
		echo '</a> ' . "\n"; 
		echo "\n"; 
	}
	echo "</div>\n";
	echo "</div>\n";
}



//Function to make an HTML set of lists from corpus data in an array
//Source is the source data, id ids the id of the containing div
function listcorpusdata($source,$id){
	global $uniqueid,$keywords,$localSitePath,$maxKeywords,$startDate,$endDate;

	$ids = array();
	$first=1;
	echo '<div class="row toprule">' . "\n";
		echo "<div class=\"col-md-8\">\n";
			echo '<div class="row ">' . "\n";
			echo "<div class=\"col-md-12\">\n";
			echo 'Collocates (Words that frequently appear alongsde) of each selected search term)' . "\n</div>\n";
			echo "</div>\n";
			echo '<div class="row">' . "\n";
				echo "<div class=\"col-md-12\">\n";
				echo '<ul class="nav nav-tabs" >' . "\n";
				foreach($source as $keyword=>$data){
					$ids[$keyword] = str_replace(" ","-",$keyword) . '-' . time() . '-' . $uniqueid;
					$uniqueid++;
					if($first==1){
						$active= ' class="active"';
						$first=0;
					}
					else{
						$active='';
					}
					echo '<li' . $active . ' role="presentation"><a href="#' . $ids[$keyword] . '" data-toggle="tab">' . str_replace(" ","<br>",$keyword) . "</a></li>\n"; 
				}
				echo "</ul>\n";
				echo "</div>\n";
			echo "</div>\n";


			$active=" active";
			echo '<div class="row">';
				echo '<div class="tab-content col-md-12" id="' . $id . '">' . "\n"; 
				foreach($source as $keyword=>$data){
					echo '<div class="tab-pane' . $active . '" id="' . $ids[$keyword] . '">' . "\n";
					if($active == ' active'){ $active=''; }
					echo '<h2>' . $keyword . '</h2>' . "\n";
					//echo '<ul>' . "\n";
					echo '<table class="table table-striped">' . "\n";
					echo '<tr><th>Collocate</th><th>Index</th><th>Actions</th></tr>' . "\n";
					foreach($data as $item=>$figure){
						if(count($keywords)<$maxKeywords){
							$theseKeywords = $keywords;
							$theseKeywords[] = $item;
							sort($theseKeywords);
							echo '<tr><td>' . $item . '</td><td>' . $figure . '</td><td><a href="' . $staticSiteRoot . implode("/",$theseKeywords) . '?startDate=' . $startDate . '&endDate=' . $endDate . '">Add to the list</a>, or <a href="' . $staticSiteRoot . $item . '?startDate=' . $startDate . '&endDate=' . $endDate . '">view individually</a>.</td></tr>' . "\n";
						}
						else{
							echo '<li><span>' . $item . ':' . $figure . '</span></li>' . "\n";
						}
					}
					echo '</table>' . "\n";
					//echo '</ul>' . "\n";
					echo "</div>\n";
				}
				echo "</div>\n";
			echo "</div>\n";
		echo "</div>\n";
		//echo "<div class=\"col-md-4\">\n";
		//include('./adverts/half-page.php');
		//echo "</div>\n";
	echo "</div>\n";
}

//Page title & meta data
$keywordText = implode(", ",$keywords);
if(count($keywords)>1){
	$comma = strrpos($keywordText,",");
	$keywordText = substr_replace ($keywordText,", and",$comma,1);
}
$pageTitle = "Word trends for $keywordText";
$pageH1 = "Word trends for $keywordText";
$pageDescription = "Trends and collocates for " . $keywordText;
$canonicalLink = $siteDomainName . $localSitePath . implode("/",$keywords);

$keywordList = array_keys($frequencies);
$datelist = array_keys($frequencies[$keywordList[0]]);

?><!DOCTYPE html>
<html>
<head>
<title><?php echo $pageTitle; ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo $pageDescription; ?>">
<link rel="canonical" href="<?php echo $canonicalLink; ?>">
<link rel="stylesheet" href="<?php echo $staticSiteRoot; ?>css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo $staticSiteRoot; ?>css/wordtrendanalysis.css">
<script src="<?php echo $staticSiteRoot; ?>js/bootstrap.min.js"></script>
<script src="<?php echo $staticSiteRoot; ?>js/d3.min.js"></script>
<script src="<?php echo $staticSiteRoot; ?>js/wordtrendanalysis.js"></script>
</head>
<body>
    <header>
	    <div class="navbar navbar-default navbar-fixed-top" role="navigation">
	      <div class="container">
		<div class="navbar-header">
		  <a class="navbar-brand" href="<?php echo $htmlSiteRoot; ?>">Word trend analysis tool</a>
		</div>
	      </div>
	    </div>
    </header>
<noscript>
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 bg-danger">
<b>Attention!</b> Your browser does not have Javascript enabled. You are seeing this message and the raw datasets below because this site uses Javascript to turn the raw data into graphical visualisations.
		</div>
	</div>
</noscript>
<div class="row">
	<div class="col-xs-12 col-sm-12 col-md-12"><h1><?php echo $pageH1 ?></h1>
	Daily frequencies between <span itemprop="temporal" content="<?php echo date('c',strtotime($datelist[0])) . '/' . date('c',strtotime($datelist[count($datelist)-1])); ?>"><time datetime="<?php echo $datelist[0]; ?>"><?php echo $datelist[0]; ?></time> and <time datetime="<?php echo $datelist[count($datelist)-1];?>"><?php echo $datelist[count($datelist)-1]; ?></time></span>.</p>
	</div>
</div><?php

if($tooManyWords===true){

?>
		<div id="maxWarning" class="col-xs-12 col-sm-12 col-md-12 bg-danger">
<b>Attention!</b> The maximum number of words or phrases that can be compared on this site is <?php echo $maxKeywords; ?>.
 Please remove one you have already selected before adding another.
<div class="input-sm" style="position:relative; margin-left:auto; margin-right:auto;"><button onclick="document.getElementById('maxWarning').style.display='none';">OK</button></div>
		</div><?php

}

?>
<section>
<?php 

listTimelineData($frequencies,"frequency-data");

?>
</section>
<section>
<?php 

listcorpusdata($collocates,'collocate-data');

?>
</section>
<footer id="footer">
      <div class="container">
        <p class="muted credit"></p>
      </div>
</footer>
<script>

//Graph drawing
var frequencyDataObject = document.getElementById('frequency-data');
var frequencyDataDimensions = frequencyDataObject.getBoundingClientRect();
var graphWidth = frequencyDataDimensions.width;

var data = htmlDataToGraphDataObject('frequency-data');
var xy_chart = d3_xy_chart()
    .width(graphWidth)
    .height(300)
    .xlabel("Date")
    .ylabel("Frequency") ;
var svg = d3.select('#frequency-data').append("svg")
    .datum(data)
    .call(xy_chart) ;
//End of graph drawing

</script>
    </div><!-- /.container -->
</body>
</html>

