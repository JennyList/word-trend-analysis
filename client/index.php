<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

//This script takes the input supplied to it via mod_rewrite or through the path, and sends the user to the right place
/*

/timeline/ - wordcloud of trends this day or week
/trends/ - Graphs for individual word combinations

everything else: 404

*/

//
// You will ALMOST CERTAINLY have to edit the folowing two variables.
//
$startweekdate = '2025-04-07'; //Start of corpus. Our weeks start on a monday. 
$siteDomainName = "http://localhost:8080"; //This is my dev environment. You will need to change this for your server. 

//
// The following variables are all editable for your installation, they should work with the software as distributed.
//
// Paths. Edit these for your corpus and storage location if they differ from the defaults.
$htmlSiteRoot = '/client/index.php/'; //path for all PHP generated pages due to lack of .htaccess
$staticSiteRoot = '/client/'; //path for static assets
$localSitePath = $htmlSiteRoot;
$localSourcePath = "data"; //the foldername of your data store in your site root.

//Define paths for data. Edit to fit your storage if different from default.
$corpuspath = "../" . $localSourcePath . "/corpus/";
$timelinepath = "../" . $localSourcePath . "/timeline/";
$timelineweekpath = "../" . $localSourcePath . "/timeline/weeks/"; 

//These noisewords are all UK politics related, this was the original use of this system.
// Edit for your application.
$noisewords = array("may","today","minister","new","says","government","video","uk","people","mps","said","public","year","prime","britain","way","time","us","just","i","political","say","change","commons","week","plans","years","following","private","dy","make","mp","day","news","monday","tuesday","wednesday","thursday","friday","saturday","sunday","need","did","live","continue reading");



$path="";

//$_SERVER["SCRIPT_NAME"]

if(isset($_SERVER['PATH_INFO']) && trim($_SERVER['PATH_INFO']) != ''){
    $path = $_SERVER['PATH_INFO'];
}
else{
    //Path not supplied, give them whatever the root page is 
    include('./index-content.php');
    exit;
}

//Catch obvious illegal characters Also catches filenames e.g.index.php
if(strpbrk($path, '.<>,;%') !== false){
    include('./404.php');
    exit;
}

//Split path by directory for country
$pathparts = explode("/",$path);
if($pathparts[0] == ""){ //bug found while dealing with no htaccess webserver, pathparts[0] is empty. It's a nasty hack.
    array_shift($pathparts);
}


//Display landing page when no keywords specified.
if(!isset($pathparts[0]) || trim($pathparts[0])==''){
        $localTrendsPath = $localSitePath . "trends/";
        $localSitePath .= "timeline/";
        include('./timeline.php');
        exit;
}

//Still have a valid country?
switch($pathparts[0]){
    case "timeline":
        $localTrendsPath = $localSitePath . "trends/";
        $localSitePath .= "timeline/";
        include('./timeline.php');
        exit;
    break;
    case "trends":
        $localSitePath .= "trends/";
	array_shift($pathparts); //Remove 'trends'
        $keywords = $pathparts;
        include('./trends.php');
        exit;
    break;
    default:
        include('./404.php');
        exit;
}



?>
