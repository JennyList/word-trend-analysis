<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
//random non kw functions
//
// (c) copyright Jenny List 2007 - 2025
//

function fix_file_name($name){
    $badstuff = array(" ","?","=",":",";",",","'","\"","&","$","#","*","|","~","`","!","[", "]","/","\\","<", ">","(", ")","{", "}");
    return str_replace($badstuff,"_",$name);
}

// Get a JSON file and return its data
function get_json_file($path,$assoc=true){
	$path = str_replace("//","/",$path);
    $return = false;
	if(file_exists($path) && $json = file_get_contents($path)){
        $return =  json_decode($json,$assoc);
	}
	return $return;
}

// Write some data to a JSON file
function put_json_file($path, $data){
	$path = str_replace("//","/",$path);
    $return = false;
	if($json=json_encode($data)){
	    if(file_put_contents($path,$json)){
			$return = true;
		}
	}
	return $return;
}

//Extend the runtime limit
// usage $i=extend_time($i);
function extend_time($i=0,$l=30,$t=120){ //parameters: count, iterations, seconds to extend
	if($i>$l){
		set_time_limit($t);
		$i++;
	}
    return $i;
}

?>
