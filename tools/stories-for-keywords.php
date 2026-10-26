<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2026 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
//
// Extract stories from the corpus which match 3 or more words in a list of keywords.
;;
// (c) copyright Jenny List 2007 - 2026
//
// usage:  php ./stories-for-keywords.php [comma delinired list of keywords]
// example: php ./stories-for-keywords.php foo,bar,baz
// 
// returns: the contents of a CSV file to stdout, with 2 columns: URL, and text.
// You can pipe this with the > character to a file if you wish.

// List of words comes from the command line
$words = explode(",",$argv[1]);

// This line you'll need to edit to suit your system.
$path_to_corpus = '/full/path/to/corpus'; // directory where the processed corpus lives


$filesuffix = "-sources.json"; //the files we are looking for all end with this
$filesuffixlength = strlen($filesuffix);
$output = array();

// everything is lower case here
foreach ($words as $key => $word){
    $words[$key] = strtolower($word);
}

foreach ($words as $key => $word){ //Step throught he supplied list of keywords
        echo $word . "\n";
	$wordpath = $path_to_corpus . '/' . $word;  //find the corpus director for the keyword
        echo $wordpath . "\n";
	$d = dir($wordpath);
	if(trim($word)!=''){
		while (false !== ($entry = $d->read())) { //step through all the corpus files for this keyword
			if (substr($entry, -1*$filesuffixlength) == $filesuffix){
				echo $entry . "\n";
				$json = file_get_contents($wordpath . '/' . $entry); 
				$sources = json_decode($json);
				//print_r($sources);
				foreach ($sources as $day => $list){ // step through source list
					if(count($list)>0){
						foreach ($list as $index => $source){
							if(is_object($source)){
								$text = $source->{'t'};
								$url = $source->{'u'};
								//print_r($source);
								$wordcount = 0;
								foreach($words as $keyword){  // Look for keywords
									if(stripos($text,$keyword)){
										$wordcount++;
									}
								}
								if($wordcount>2){ //minimum 3 keyword matches seems to work best.
									$output[$url] = $text;
								}
							}
						}
					}
				}
			}
		}
		$d->close();
	}
}

//Push CSV data to stdout
foreach($output as $url=>$text){
	echo '"' . $url . '","' . $text . '"' . "\n";
}



?>
