<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2026 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
//
// Extract collocates for a keyword
// (c) copyright Jenny List 2007 - 2026
//
// usage:  php ./collocates-for-keyword.php [keyword] (optional)[count=500]
// example: php ./collocates-for-keyword.php foo 100
//
// keyword is the word searched for, count is optional, defaults to 500 and is the  number of results you want.
//
// returns: the contents of a CSV file to stdout, with 2 columns: collocate, and % frequency.
// You can pipe this with the > character to a file if you wish.

// Keyword comes from the command line
$keyword = strtolower($argv[1]);

// number of output lines
if(isset($argv[2])){
	$count = intval($argv[2]);
}
else{
	$count = 500;
}

// This line you'll need to edit to suit your system.
$path_to_corpus = '/path/to/your/corpus'; // directory where the processed corpus lives


$filesuffix = "-collocates.json"; //the files we are looking for all end with this
$filesuffixlength = strlen($filesuffix);
$output = array();

$wordpath = $path_to_corpus . '/' . $keyword;  //find the corpus directory for the keyword
//echo $wordpath . "\n";
if(file_exists($wordpath)){
	$d = dir($wordpath);
	while (false !== ($entry = $d->read())) { //step through all the corpus files for this keyword
		if (substr($entry, -1*$filesuffixlength) == $filesuffix){
			//echo $entry . "\n";
			$json = file_get_contents($wordpath . '/' . $entry); 
			$collocates = json_decode($json);
			//print_r($sources);
			foreach ($collocates as $instance => $frequency){ // step through instances
				if(stristr($instance," ") === FALSE){
					if(!isset($output[$instance])){
						$output[$instance] = $frequency;
					}
					else{
						$output[$instance] +=$frequency;
					}
				}
			}
		}
	}
	$d->close();
}

arsort($output);
$sum = array_sum($output);

//Push CSV data to stdout
$line=1;
foreach($output as $word=>$frequency){
	$percent = round($frequency/$sum,5);
	if($percent > 0){
		echo '"' . $word . '","' . 100*$percent . '"' . "\n";
	}
	if($line==$count){
		break;
	}
	else{
		$line++;
	}
}



?>
