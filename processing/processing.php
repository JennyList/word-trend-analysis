<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
//
// Feed to corpus processing server side script
// (c) copyright Jenny List 2007 - 2025
//
// Run this script from the inclded shell script, in its directory.

//
//   Paths to the various data directories. These are the defaults, you may wish to edit them.
//

$rootPath = getcwd() . '/../'; //path to the root of the application
$dataPath = 'data/'; //path to the folder in the root that holds the data. Suggest making this a drive monted at this path
$path_to_words = $rootPath . $dataPath . 'corpus/';
$path_to_timeline = $rootPath . $dataPath . 'timeline/';
$path_to_archive = $rootPath . $dataPath . 'archive/';

$modifiedFilesPath = './modifiedfiles/';

$path_to_feedlist = getcwd() . '/feedlist/feedlist.txt'; //A text list of feed URLs
$path_to_inc = getcwd() . '/inc'; //include files from this project
$path_to_external = getcwd() . '/external'; //External libraries not from this project

$path_to_simplepie = $path_to_external . '/simplepie/autoloader.php';

if (file_exists($path_to_simplepie)){
    include($path_to_simplepie);
}
else{
    echo $path_to_simplepie . " not found. \n";
    echo "SimplePie is not installed. Please get it from https://github.com/simplepie/simplepie and install it in the ./external directory.\n";
    exit();
}
include($path_to_inc . '/keyword-analysis-functions.php');
include($path_to_inc . '/misc-functions.php');
include($path_to_inc . '/feedlist-functions.php');

if(function_exists("date_default_timezone_set")){
	date_default_timezone_set('Europe/London');
}

echo "Current time: " . date("Y-m-d H:i:s",time()) . "\n";


//Maximum phrase length in words
$max_phrase_length = 2;


//We use this to create a list we can dump as a file
//This can be used to then FTP them to an external website.
//Function to add a path to the modified file list
function addToModifiedFiles($path,$contents,$json=false){
    global $modifiedFilesPath,$rootPath;
    $newPath = str_replace($rootPath,'',$path);

   // echo "Baseline file path is: " . $newPath . "\n";

    $dirbits = explode("/",$newPath);
    $testpath = $modifiedFilesPath;
    $i=0;
    foreach($dirbits as $dirbit){
        $i++;
        if($i!=count($dirbits)){
            $testpath .= $dirbit . '/';
            if(!file_exists($testpath)){
                echo "Modified file path is: " . $testpath . "\n";
                mkdir($testpath);
            }
        }
    }
    if($json===true){ $contents = json_encode($contents); }
    file_put_contents($modifiedFilesPath . $newPath,$contents);
}

$feeds = feedlist_to_array($path_to_feedlist);

//Step through the list of feeds.
foreach($feeds as $feedURL){

	echo "Checking " . $feedURL . "\n";

	set_time_limit(120);

	// Parse it
	$feed = new SimplePie();
	$feed->set_feed_url($feedURL);
	$feed->enable_cache(false);
	$feed->init();
	$feed->handle_content_type();

	if($feed->error()){
	    echo $feed->error();
	}

	if ($feed->data){
		$items = $feed->get_items();
		echo $feed->get_item_quantity() . " items <br/>";
		//Step through items
		foreach($items as $item){

			set_time_limit(120);

			$guid = $item->get_id();
			$permlink = $item->get_permalink();
			$title = $item->get_title();
			$description = $item->get_title();
			$pubdate = $item->get_date();
			$content = $item->get_content();
			
			if(!file_exists($path_to_archive . fix_file_name($guid).".json")){ // Have we already done this one
			
				file_put_contents($path_to_archive . fix_file_name($guid).".json",json_encode(array($guid,$permlink,$description,$pubdate,$content))); //write it to the archive
		    
				$i=extend_time($i);

				$phrases = array();
				$phraselist = array();
			
				$this_source_array = array();
				$this_source_array['t'] = $title;
				$this_source_array['u'] = $permlink;
				
				echo "\n\n\n$pubdate\n\n\n";
				
				//Make filename prefix for this date range
				$pubtime = strtotime($pubdate);
				$pubtime_array = getdate($pubtime);
				$date_prefix =  date("M-Y", $pubtime);
				//Make empty month for frequency JSON
				$days_in_month = date('t', mktime(0, 0, 0, $pubtime_array['mon'], 1, $pubtime_array['year'])); 
				$empty_month = array_fill (1,$days_in_month ,0);
				
				//Make timeline day fliename
				$timelinefile = $path_to_timeline . date("Y-m-d", $pubtime) . ".json";


				//Make timeline source day fliename
				$timelinesourcefile = $path_to_timeline . date("Y-m-d", $pubtime) . "-sources.json";

				
				//kw analyse items 

				$text = kw_html_to_plaintext($title . ". " . $description);
				$keyword_phrases = kw_get_keyword_phrases($text,$max_phrase_length,4); // max 4 sentences

				$phrasecount = 0;
				foreach($keyword_phrases as $phrase=>$rank){
				
				$i=extend_time($i);
					
					$wordcount = count(explode(" ",$phrase));
					$keywordref = 0;
					//Store keyword if new
					if(!in_array($phrase,$phraselist)){
						$phrases[$phrase] = 1;
						$phraselist[] = $phrase;   //  !!! store the rank?
						$phrasecount++;
						echo "new $phrase \n";
					}
					else{ //Add to keyword phrase count
						$phrases[$phrase]++;
						echo " $phrase \n";
					}


				}//end of keyword foreach

				echo $phrasecount . " new keyword phrases found in item " . $title . " \n";
			
				//Now store all the stuff
				foreach($phraselist as $phrase){
					$thisphrasefrequency = 0;
				    $i=extend_time($i);
					// Make directories for new phrases
				    $filename = fix_file_name($phrase);
					$dirname_parts = explode("-",$filename);
					$dirpath = "";
					foreach($dirname_parts as $key=>$dir){
					$i=extend_time($i);
						
					    //Create the next dir level if it's not already there
						$dirpath .= "/" . $dir;
						$phrase_path = $path_to_words . $dirpath;
						$phrase_path = str_replace("//","/",$phrase_path);
						if(!file_exists($phrase_path)){
							mkdir($phrase_path);
							echo "Directory $phrase_path created\n";
						}
					}
					
					
					//Store collocates for this month
				    $collocate_file_path = $phrase_path . "/" . $date_prefix . "-" . $filename . "-collocates.json";

					$collocates = $phrases;
					unset($collocates[$phrase]);//remove the current phrase from the collocates list
					if(!file_exists($collocate_file_path)){ //new collocate file
						if(put_json_file($collocate_file_path,$collocates)){
							addToModifiedFiles($collocate_file_path,$collocates,true);
						    echo "Collocate file $collocate_file_path created\n";
						}
						else{
						    echo "Collocate file $collocate_file_path could not be created\n";
						}
					}
					else{ //Add to existing collocate file
					    if($all_collocates = get_json_file($collocate_file_path)){
						    echo "Collocate file $collocate_file_path loaded OK\n";
							foreach($collocates as $collocate=>$frequency){
						$i=extend_time($i);
								if(isset($all_collocates[$collocate])){
								    $all_collocates[$collocate] += $frequency;
								}
								else{
								    $all_collocates[$collocate] = $frequency;
								}
							}
							arsort($all_collocates);
							if(put_json_file($collocate_file_path,$all_collocates)){
								addToModifiedFiles($collocate_file_path,$all_collocates,true);
								echo "Collocate file $collocate_file_path rewritten\n";
							}
							else{
								echo "Collocate file $collocate_file_path could not be rewritten\n";
							}
						}
						else{
						    echo "Collocate file $collocate_file_path could not be loaded\n";
						}
					}
					
					//Store frequencies for this month
				    $frequency_file_path = $phrase_path . "/" . $date_prefix . "-" . $filename . "-frequencies.json";

					if(!file_exists($frequency_file_path)){ //new frequency file
					    $frequencies = $empty_month;
						$frequencies[$pubtime_array['mday']]++;
						$thisphrasefrequency = $frequencies[$pubtime_array['mday']];
						if(put_json_file($frequency_file_path,$frequencies)){
							addToModifiedFiles($frequency_file_path,$frequencies,true);
						    echo "Frequency file $frequency_file_path created\n";
						}
						else{
						    echo "Frequency file $frequency_file_path could not be created\n";
						}
					}
					else{ //Add to existing frequency file
					    if($frequencies = get_json_file($frequency_file_path)){
						    echo "Frequency file $frequency_file_path loaded OK\n";
						    $frequencies[$pubtime_array['mday']]++;
						    $thisphrasefrequency = $frequencies[$pubtime_array['mday']];
							if(put_json_file($frequency_file_path,$frequencies)){
								addToModifiedFiles($frequency_file_path,$frequencies,true);
								echo "Frequency file $frequency_file_path rewritten\n";
							}
							else{
								echo "Frequency file $frequency_file_path could not be rewritten\n";
							}
						}
						else{
						    echo "Frequency file $frequency_file_path could not be loaded\n";
						}
					}
					
					
					//Store this item link in the source list
			
					$sources_file_path = $phrase_path . "/" . $date_prefix . "-" . $filename . "-sources.json";

					if(!file_exists($sources_file_path)){ //new sources file
					    $sources = $empty_month;
						foreach($sources as $key=>$sourceday){ //populate with empty arrays
						    $sources[$key] = array();
						}
						$sources[$pubtime_array['mday']][] = $this_source_array;
						if(put_json_file($sources_file_path,$sources)){
							addToModifiedFiles($sources_file_path,$sources,true);
						    echo "Sources file $sources_file_path created\n";
						}
						else{
						    echo "Sources file $sources_file_path could not be created\n";
						}
					}
					else{ //Add to existing sources file
					    if($sources = get_json_file($sources_file_path)){
						    echo "Sources file $sources_file_path loaded OK\n";
						    $sources[$pubtime_array['mday']][] = $this_source_array;
							if(put_json_file($sources_file_path,$sources)){
								addToModifiedFiles($sources_file_path,$sources,true);
								echo "Sources file $sources_file_path rewritten\n";
							}
							else{
								echo "Sources file $sources_file_path could not be rewritten\n";
							}
						}
						else{
						    echo "Sources file $sources_file_path could not be loaded\n";
						}
					}

					//Add this word to the timeline
					$i=extend_time($i);
					$phrasewordcount = explode(" ",$phrase);
					if(count($phrasewordcount) == 1){
						addToTimeline($timelinefile,$phrase,$thisphrasefrequency);
					}


				}//end of phrases writing foreach

				//Add this item to the sources timeline
				addToTimelineSources($timelinesourcefile,$permlink,$title);
			} //end of file exists have we already done this one foreach
			else{
			    echo "Already processed this file\n";
			}
			
		}//end of items foreach

	}//end of $feed->data if
	sleep(60); //put the brakes on a little, to be a good online citizen

} //end of step through feeds loop

//Write the list of modified files if it is needed
//file_put_contents('./modified-files.json',json_encode($modifiedFiles));

//End of processing code

//Function to write a timeline day file
function addToTimeline($jsonfilepath,$word,$frequency){
	if(!file_exists($jsonfilepath)){
		$filecontents = array();
	}
	else{
		$filecontents = json_decode(file_get_contents($jsonfilepath),true);
	}
	if(isset($filecontents[$word])){
		$filecontents[$word] += $frequency;
	}
	else{
		$filecontents[$word] = $frequency;
	}
	file_put_contents($jsonfilepath,json_encode($filecontents));
	addToModifiedFiles($jsonfilepath,json_encode($filecontents));
}


//Function to write a timeline day file
function addToTimelineSources($jsonfilepath,$url,$text){
	if(!file_exists($jsonfilepath)){
		$filecontents = array();
	}
	else{
		$filecontents = json_decode(file_get_contents($jsonfilepath),true);
	}
	if(!isset($filecontents[$url])){
		$filecontents[$url] = $text;
	}
	file_put_contents($jsonfilepath,json_encode($filecontents));
	addToModifiedFiles($jsonfilepath,json_encode($filecontents));
}

?>
