<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/
//
// Keyword analysis functions
// (c) copyright Jenny List 2007 - 2025
//

$kw_noiselevel_cache = array();
$kw_startend_noise_cache = array();

// Function to return a list of noise words to ignore completely
// The default list includes UK English noise words.
// all words are lowercase.
function kw_return_noisewords(){
	//'us' removed because it is a synonym for 'usa'
	$noisewords = array("a", "about", "above", "above", "across", "after", "afterwards", "again", "against", "all", "almost", "alone", "along", "already", "also","although","always","am","among", "amongst", "amoungst", "amount",  "an", "and", "another", "any","anyhow","anyone","anything","anyway", "anywhere", "are", "around", "as",  "at", "back","be","became", "because","become","becomes", "becoming", "been", "before", "beforehand", "behind", "being", "below", "beside", "besides", "between", "beyond", "bill", "both", "bottom","but", "by", "call", "can", "cannot", "cant", "co", "con", "could", "couldnt", "cry", "de", "describe", "detail", "do", "done", "down", "due", "during", "each", "eg", "eight", "either", "eleven","else", "elsewhere", "empty", "enough", "etc", "even", "ever", "every", "everyone", "everything", "everywhere", "except", "few", "fifteen", "fify", "fill", "find", "fire", "first", "five", "for", "former", "formerly", "forty", "found", "four", "from", "front", "full", "further", "get", "give", "go", "had", "has", "hasnt", "have", "he", "hence", "her", "here", "hereafter", "hereby", "herein", "hereupon", "hers", "herself", "him", "himself", "his", "how", "however", "hundred", "ie", "if", "in", "inc", "indeed", "interest", "into", "is", "it", "its", "itself", "keep", "last", "latter", "latterly", "least", "less", "ltd", "made", "many", "may", "me", "meanwhile", "might", "mill", "mine", "more", "moreover", "most", "mostly", "move", "much", "must", "my", "myself", "name", "namely", "neither", "never", "nevertheless", "next", "nine", "no", "nobody", "none", "noone", "nor", "not", "nothing", "now", "nowhere", "of", "off", "often", "on", "once", "one", "only", "onto", "or", "other", "others", "otherwise", "our", "ours", "ourselves", "out", "over", "own","part", "per", "perhaps", "please", "put", "rather", "re", "same", "see", "seem", "seemed", "seeming", "seems", "serious", "several", "she", "should", "show", "side", "since", "sincere", "six", "sixty", "so", "some", "somehow", "someone", "something", "sometime", "sometimes", "somewhere", "still", "such", "system", "take", "ten", "than", "that", "the", "their", "them", "themselves", "then", "thence", "there", "thereafter", "thereby", "therefore", "therein", "thereupon", "these", "they", "thickv", "thin", "third", "this", "those", "though", "three", "through", "throughout", "thru", "thus", "to", "together", "too", "top", "toward", "towards", "twelve", "twenty", "two", "un", "under", "until", "up", "upon", "very", "via", "was", "we", "well", "were", "what", "whatever", "when", "whence", "whenever", "where", "whereafter", "whereas", "whereby", "wherein", "whereupon", "wherever", "whether", "which", "while", "whither", "who", "whoever", "whole", "whom", "whose", "why", "will", "with", "within", "without", "would", "yet", "you", "your", "yours", "yourself", "yourselves", "the");
	return $noisewords;
}

//function to turn HTML into lowercase plain text with no non alphanumeric characters
function kw_html_to_plaintext($html){
	$html = preg_replace ( "'<script[^>]*?>.*?</script>'si",' ',$html); //get javascript
	$html = preg_replace ( "'<style[^>]*?>.*?</style>'si",' ',$html); //get styles
	$html = preg_replace ( "'<h[^>]*?>'si",' ',$html); //replace heading starts with sentence starts
	$html = preg_replace ( "'</h[^>]*?>'si",'. ',$html); //replace heading ends with sentence ends
	//Create reverse HTML translation table
	$trans = get_html_translation_table(HTML_ENTITIES);
	$trans = array_flip($trans);
	// do the translation with a couple of extras, tags like <!DOCTYPE confuses strip_tags, also put a space after a >
	$html = strtr($html, $trans+array('<!' =>'<','>' => '> ','-'=>' '));
	$html = strip_tags($html); //get rid of html tags
	$html = preg_replace ( '/[^0-9a-zA-Z.?! ]/','',$html); //get non alphanumeric characters except sentence terminators
	$html = preg_replace('/\s\s+/', ' ', $html); //get excess whitespace
	$html = strtolower($html);
	return $html;
}

//function to remove noise words from a piece of text.
function kw_strip_noisewords($text,$extrawords=0){
	$noisewords = kw_return_noisewords();
	if($extrawords==1){
		$textwords = explode(" ",$text);
		$text = "";
		foreach ($textwords as $key => $value){
		    if(!in_array($value,$noisewords)){
		        $text .= $value . " ";
			}
		}
	}

	return $text;
}

//function to split a load of text into sentences
function kw_get_sentences($text,$count){
	//Catch \n
	$text = str_replace("\n",' ',$text);
	//Get ? and !
	$text = str_replace(array('!','?'),'. ',$text);
	$sentences = explode(".",$text);
	foreach($sentences as $key=>$sentence){
		$sentences[$key] = trim($sentence);
	}
	if($count==0){
	    return $sentences;
	}
	else{
		return array_slice($sentences, 0, $count);
	}
}

//Function to split a piece of text into phrases of  $count words
function kw_split_phrases($text,$count=2){
	//Use the PHP explode function if $count==1, it's faster
	if($count==1){
		$keywords = explode(' ',kw_strip_noisewords(strtolower($text),1));
		return $keywords;
	}
	$noisewords = kw_return_noisewords();
	$keywords = explode(' ',strtolower($text));
	$wordphrases = array();
	foreach($keywords as $key=>$word){
		$ok=1;
		for ($i = 0; $i <= $count-1; $i++) {
			if(!isset($keywords[$key+$i]) or trim($keywords[$key+$i])==""){
				$ok=0;
			}
		}
		if($ok==1){
			$this_words = array();
			$phrase_ok = 1;
			for ($i = 0; $i <= $count-1; $i++) {
				if(!in_array($keywords[$key+$i],$noisewords) and !is_numeric($keywords[$key+$i])){  //Catch phrases with noise words or just numbers
					$this_words[] = trim($keywords[$key+$i]);
				}
				else{
					$phrase_ok = 0; //noise word found
				}
			}
			if($phrase_ok==1){
				$wordphrases[] = implode(" ", $this_words);
			}
		}
	}

	return $wordphrases;
}

//function to return a word frequency array from a piece of text
function kw_frequency($text,$raw_keywords,$freqcount=0){
	$keywords=array();
	foreach ($raw_keywords as $word){
		//get rid of any whitespace
		$word = trim($word);
		if($word!='' and !is_numeric($word)){//ignore whitespace and numbers
			if(array_key_exists($word,$keywords)){ //we already have it, update the counter
				$keywords[$word]++;
			}
			else{ //We don't have it, add it to the array
				$keywords[$word]=1;
			}
		}
	}
	arsort($keywords);
	if($freqcount != 0){ //if freqcount is not zero then we return only the top $freqcount frequencies
		$i=0;
		$freq_array = array();
		foreach ($keywords as $key => $value){
			if(!in_array($value,$freq_array)){
				array_push($freq_array,$value);
				if(count($freq_array)>$freqcount){
					break;
				}
			}
			$i++;
		}
		$keywords = array_slice($keywords, 0, $i);
	}
	return $keywords;
}


//function to get all the n-word keyword phrases from a piece of text and sort them by frequency
function kw_get_keyword_phrases_by_frequency($text,$count=2,$sentencecount=0,$max_sentence_length=400){
	$keyword_phrases = array();
	$sentences = kw_get_sentences($text,$sentencecount);
	foreach($sentences as $sentence){
		if(strlen($sentence)<$max_sentence_length){ //Lose very long sentences, they are more likely to be HTML junk
			$phrases = kw_split_phrases($sentence,$count);
			$this_phrases = kw_frequency($text,$phrases,15);
			foreach($this_phrases as $this_phrase => $this_phrase_count){
				if(array_key_exists($this_phrase,$keyword_phrases)){ //we already have it, update the counter
					$keyword_phrases[$this_phrase] += $this_phrase_count;
				}
				else{ //We don't have it, add it to the array
					$keyword_phrases[$this_phrase] = $this_phrase_count;
				}
			}
		}
	}
	arsort($keyword_phrases);
	return $keyword_phrases;
}


//Function to get a range of weighted keyword phrases from a piece of text up to a maximum length
function kw_get_keyword_phrases($text,$max_phrase_size=3,$sentencecount=0){
	$keyword_phrases = array();
	for($i = 1; $i <= $max_phrase_size; $i++) { //Step through phrase sizes
		//$lowvalue_words = kw_return_lowvalue_demote_words();
		$these_phrases = kw_get_keyword_phrases_by_frequency($text,$i,$sentencecount);
		foreach($these_phrases as $key=>$value){
			$words_in_phrase = explode(" ",$key);
			$promote = ($i*1.5);
			$these_phrases[$key] = $promote + ($value*$i); //Promote phrases more as they get longer
		}
		$keyword_phrases = array_merge($keyword_phrases,$these_phrases);
	}
	arsort($keyword_phrases);
	return $keyword_phrases;
}



?>
