<?php
/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

//Sample of top n words for bubble cloud
$sample = 100;

//Start by generating a time we know is in the system
$timestamp = strtotime($startweekdate); 

//Then get the date supplied or use the default. Intval because this value is passed to the filesystem.
if(!isset($_REQUEST['d'])){ $d = date('d',$timestamp); }else{ $d = intval($_REQUEST['d']); }
if(!isset($_REQUEST['m'])){ $m = date('m',$timestamp); }else{ $m = intval($_REQUEST['m']); }
if(!isset($_REQUEST['y'])){ $y = date('Y',$timestamp); }else{ $y = intval($_REQUEST['y']); }
//Catch leading zero on m and d if stripped out by intval.
$m = strval($m);
$d = strval($d);
if(strlen($m)==1){ $m = "0" . $m; }
if(strlen($d)==1){ $d = "0" . $d; }

function timeline_get_week($y,$m,$d){
	//Step through the seven days, get the files and consolidate their contents
	$wordlist = array();
	global $timelinepath,$corpuspath,$timelineweekpath,$noisewords,$sample;
	$days = array();
	$timestamp = strtotime($y . "-" . $m . "-" . $d);
	for ($weekday=1; $weekday<=7; $weekday++){
	    $day = date('Y-m-d',$timestamp);
	    $days[] = $day;
	    $filepath = $timelinepath . $day . ".json";
	    
	    if(file_exists($filepath)){
		@$daywordlist = json_decode(file_get_contents($filepath),true);
		foreach($daywordlist as $word=>$frequency){
                    if(!in_array($word,$noisewords)){
			    if(!isset($wordlist[$word])){
				$wordlist[$word] = $frequency;
			    }
			    else{
				$wordlist[$word] += $frequency;
			    }
                    }
		}
	    }
	    $timestamp += (24*60*60);
	}
	arsort($wordlist);
	
	$sources = '';

	return array($wordlist,$sources);
}


$timelineandsources = timeline_get_week($y,$m,$d);
$timeline = $timelineandsources[0];
$sources = $timelineandsources[1];

$timestamp = strtotime($y . "-" . $m . "-" . $d);
$nextweek = date('Y-m-d',strtotime("+ 7 days",$timestamp));
$previousweek = date('Y-m-d',strtotime("- 7 days",$timestamp));

//Page title & meta data
$pageTitle = "Words for the week of $y-$m-$d";
$pageH1 = "Words for the week of $y-$m-$d";
$pageDescription = "A bubble cloud of words used in the week of $y-$m-$d";
$canonicalLink = $siteDomainName . $localSitePath; //must add date string to path

?><!DOCTYPE html>
<html>
<head>
<title><?php echo $pageTitle; ?></title>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="<?php echo $pageDescription; ?>">
<link rel="canonical" href="<?php echo $canonicalLink; ?>">
<link rel="stylesheet" href="<?php echo $staticSiteRoot; ?>css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo $staticSiteRoot; ?>css/awialtip.css">
<script src="<?php echo $staticSiteRoot; ?>js/d3.min.js"></script>
<script src="<?php echo $staticSiteRoot; ?>js/wordtrendanalysis.js"></script>
<script src="<?php echo $staticSiteRoot; ?>js/bootstrap.min.js"></script>
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
    <div class="container-fluid">
	<!-- div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12">
<?php 
		include('./adverts/leaderboard.php');
?>
		</div -->
	</div>
      <section>
	<div class="row">
		<div class="col-md-2" style="padding-top:15px;"><?php if($previousweek != ''){ echo '<a type="button" class="btn btn-primary btn-block" href="./?y=' . explode("-",$previousweek)[0] . '&m=' . explode("-",$previousweek)[1] . '&d=' . explode("-",$previousweek)[2] . '">Previous week</a>'; } ?></div>
		<div class="col-md-8" style="text-align:center;"><h1><?php echo $pageH1; ?></h1></div>
		<div class="col-md-2" style="padding-top:15px;"><?php if($nextweek != ''){ echo '<a type="button" class="btn btn-primary btn-block" href="./?y=' . explode("-",$nextweek)[0] . '&m=' . explode("-",$nextweek)[1] . '&d=' . explode("-",$nextweek)[2] . '">Next week</a>'; } ?></div>
	</div>
<noscript>
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-12 bg-danger">
<b>Attention!</b> Your browser does not have Javascript enabled. This site uses Javascript to turn raw data into graphical visualisations, so you will not get the best experience from it without enabling Javascript.
		</div>
	</div>
</noscript>
	<div class="row">
	  <div class="col-md-12" id="bubblemap"></div>
	</div>
        </section>
        <section itemscope itemtype="http://schema.org/Dataset">
	<div class="row">
	  <div class="col-md-12">
Word frequencies in the week of <span itemprop="temporal" content="<?php echo date('c',strtotime($y . '-' . $m . '-' . $d)) . '/' . date('c',strtotime("+ 6 days",strtotime($y . '-' . $m . '-' . $d))); ?>"><time datetime="<?php echo $y . '-' . $m . '-' . $d; ?>"><?php echo $y . '-' . $m . '-' . $d; ?></time></span>.
          </div>
	</div>
	<div class="row">
	  <div class="col-md-12">
            <table class="table table-striped">
              <tr><th>Word or phrase</th><th>% frequency</th><th>Action</th></tr>
<?php

$i=0;
$timeline = array_slice ($timeline,0,$sample);
$wordcount = array_sum($timeline);
foreach($timeline as $word=>$frequency){
    echo "<tr><td>" . $word . "</td><td>" . round(($frequency/$wordcount)*100,2) . "</td><td>";
    echo '<a href="' . $localTrendsPath . $word . "?startDate=" . $y . '-' . $m . '-' . $d . "&endDate=" . date('Y-m-d',strtotime("+ 6 days",strtotime($y . '-' . $m . '-' . $d))) . '">View graph for ' . $word . '</a>'; //$localSitePath
    echo "</td></tr>\n";
    if($i==90){ break; }else{ $i++; }
}
?>
            </table>
           </div>
	</div>
      </section>
    </div><!-- /.container -->
<script>

var weekdates = <?php echo json_encode($weekdates); ?>;


var wordlist = {"name": "wordlist","children": [<?php

$jsonbuild = array();
$i=0;
$timeline = array_slice ($timeline,0,$sample);
$wordcount = array_sum($timeline);
foreach($timeline as $word=>$frequency){
    $jsonbuild[] = '{"name": "' . $word . '", "size": ' . ($frequency/$wordcount)*100 . '}';
   // if($i==100){ break; }else{ $i++; }
}
echo implode(",",$jsonbuild);

?>]};


var diameter = 640,
    format = d3.format(",d"),
    color = d3.scale.category20c();

var bubble = d3.layout.pack()
    .sort(null)
    .size([diameter, diameter])
    .padding(1.5);

var svg = d3.select("#bubblemap").append("svg")
    .attr("width", diameter)
    .attr("height", diameter)
    .attr("class", "bubble");


function makeBubbleMap(root){
  var node = svg.selectAll(".node")
      .data(bubble.nodes(classes(root))
      .filter(function(d) { return !d.children; }))
    .enter().append("g")

      .attr("onclick", function(d) { return "window.location = '../trends/" + d.className + "?<?php echo 'startDate=' . $y . '-' . $m . '-' . $d; ?>&endDate=<?php echo date('Y-m-d',strtotime("+ 6 days",strtotime($y . '-' . $m . '-' . $d))); ?>';" })

      .attr("class", "node")
      .attr("transform", function(d) { return "translate(" + d.x + "," + d.y + ")"; });

  node.append("title")
      .text(function(d) { return d.className + ": " + format(d.value); });

  node.append("circle")
      .attr("r", function(d) { return d.r; })
      .style("fill", function(d) { return color(d.packageName); });

  node.append("text")
      .attr("dy", ".3em")
      .style("text-anchor", "middle")
      .style("font-size", function(d) { return (parseInt(d.value*4)+10) + "px"; })
      .text(function(d) { return d.className.substring(0, d.r / 3); });
}

makeBubbleMap(wordlist);

// Returns a flattened hierarchy containing all leaf nodes under the root.
function classes(root) {
  var classes = [];

  function recurse(name, node) {
    if (node.children) node.children.forEach(function(child) { recurse(node.name, child); });
    else classes.push({packageName: name, className: node.name, value: node.size});
  }

  recurse(null, root);
  return {children: classes};
}

d3.select(self.frameElement).style("height", diameter + "px");

</script>
</body>
</html>
