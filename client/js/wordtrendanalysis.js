/*

Licenced under the MIT licence as follows:

Copyright 2007-2025 Jenny List

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the “Software”), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED “AS IS”, WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

//Function to parse a set of lists into a json for the graph function
function htmlDataToGraphDataObject(id){
	var output = [];
	var parent = document.getElementById(id);	
	var keywordNodes = parent.getElementsByTagName('div');
	var parseDate = d3.time.format("%Y-%m-%d").parse;
	for(var i=0;i<keywordNodes.length;i++){
		var thisKeywordOutput = {};
		var xValues = [];
		var yValues = [];
		var keywordNode = keywordNodes[i];
		var h2Element = keywordNode.firstElementChild;
		var ulElement = keywordNode.lastElementChild;
		var dataList = ulElement.childNodes;
		var keyword = h2Element.innerHTML;
		thisKeywordOutput['label'] = keyword;
		for(var j=0;j<dataList.length;j++){
			var li = dataList[j];
			if(li.tagName == 'LI'){
				var dataItem = li.textContent;
				var dataPair = dataItem.split(':');
				if(typeof(dataPair[1]) != 'undefined'){
					xValues[xValues.length] = parseDate(dataPair[0]);
					yValues[yValues.length] = parseInt(dataPair[1]);
				}
			}
		}
		thisKeywordOutput['x'] = xValues;
		thisKeywordOutput['y'] = yValues;
	
		output[output.length] = thisKeywordOutput;
		keywordNode.removeChild(h2Element);
		keywordNode.removeChild(ulElement);
	}

	return output;
}

//Reusable D3 graph function
function d3_xy_chart() {
    var width = 800,  
        height = 400, 
        xlabel = "X axis",
        ylabel = "Y axis",
	keyTransform = 20; //X pixels from top to place key text
    
    function chart(selection) {
        selection.each(function(datasets) {
            //
            // Create the plot. 
            //
            var margin = {top: 20, right: 80, bottom: 30, left: 50}, 
                innerwidth = width - margin.left - margin.right,
                innerheight = height - margin.top - margin.bottom ;
    
        
		var minDate = d3.min(datasets, function(d) { return d3.min(d.x); }),
		    maxDate = d3.max(datasets, function(d) { return d3.max(d.x); });

		var x_scale = d3.time.scale().domain([minDate, maxDate]).range([0, innerwidth]);


            var y_scale = d3.scale.linear()
                .range([innerheight, 0])
                .domain([ d3.min(datasets, function(d) { return d3.min(d.y); }),
                          d3.max(datasets, function(d) { return d3.max(d.y); }) ]) ;

            var color_scale = d3.scale.category10()
                .domain(d3.range(datasets.length)) ;

            var x_axis = d3.svg.axis()
                .scale(x_scale)
                .orient("bottom") ;

            var y_axis = d3.svg.axis()
                .scale(y_scale)
                .orient("left") ;

            var x_grid = d3.svg.axis()
                .scale(x_scale)
                .orient("bottom")
                .tickSize(-innerheight)
                .tickFormat("") ;

            var y_grid = d3.svg.axis()
                .scale(y_scale)
                .orient("left") 
                .tickSize(-innerwidth)
                .tickFormat("") ;

            var draw_line = d3.svg.line()
                .interpolate("basis")
                .x(function(d) { return x_scale(d[0]); })
                .y(function(d) { return y_scale(d[1]); }) ;

            var svg = d3.select(this)
               // .attr("width", width)
                .attr("style", "width:" + width + "; height:" + height)
               // .attr("height", height)
                .append("g")
                .attr("transform", "translate(" + margin.left + "," + margin.top + ")") ;
            
            svg.append("g")
                .attr("class", "x grid")
                .attr("transform", "translate(0," + innerheight + ")")
                .call(x_grid) ;

            svg.append("g")
                .attr("class", "y grid")
                .call(y_grid) ;

            svg.append("g")
                .attr("class", "x axis")
                .attr("transform", "translate(0," + innerheight + ")") 
                .call(x_axis)
                .append("text")
                .attr("dy", "-.71em")
                .attr("x", innerwidth)
                .style("text-anchor", "end")
                .text(xlabel) ;
            
            svg.append("g")
                .attr("class", "y axis")
                .call(y_axis)
                .append("text")
                .attr("transform", "rotate(-90)")
                .attr("y", 6)
                .attr("dy", "0.71em")
                .style("text-anchor", "end")
                .text(ylabel) ;

            var data_lines = svg.selectAll(".d3_xy_chart_line")
                .data(datasets.map(function(d) {return d3.zip(d.x, d.y);}))
                .enter().append("g")
                .attr("class", ".d3_xy_chart_line") ;
            
            data_lines.append("path")
                .attr("class", "line")
                .attr("d", function(d) {return draw_line(d); })
                .attr("stroke", function(_, i) {return color_scale(i);}) ;
            
            data_lines.append("text")
                .datum(function(d, i) { return {name: datasets[i].label, final: d[d.length-1]}; }) 
                .attr("transform", function(d) { //For key evenly spaced 
			var yTranslate = keyTransform;
			keyTransform += 15;
                	return ( "translate(" + x_scale(d.final[0]) + "," + yTranslate + ")" ) ; 
		})
                /*.attr("transform", function(d) { //For key at end of line
                    return ( "translate(" + x_scale(d.final[0]) + "," + 
                             y_scale(d.final[1]) + ")" ) ; }) */
                .attr("x", 3)
                .attr("dy", ".35em")
                .attr("fill", function(_, i) { return color_scale(i); })
                .text(function(d) { return d.name; }) ;

            data_lines.append("text") //add "Key"
                .datum(function(d) { return {name: datasets[1].label, final: d[d.length-1]}; }) 
                .attr("transform", function(d) { //For key evenly spaced 
                	return ( "translate(" + x_scale(d.final[0]) + "," + 5 + ")" ) ; 
		})
                .attr("x", 3)
                .attr("dy", ".35em")
                .attr("fill", function(_, i) { return "#000000"; })
                .text(function(d) { return "KEY" }) ;

        }) ;
    }

    chart.width = function(value) {
        if (!arguments.length) return width;
        width = value;
        return chart;
    };

    chart.height = function(value) {
        if (!arguments.length) return height;
        height = value;
        return chart;
    };

    chart.xlabel = function(value) {
        if(!arguments.length) return xlabel ;
        xlabel = value ;
        return chart ;
    } ;

    chart.ylabel = function(value) {
        if(!arguments.length) return ylabel ;
        ylabel = value ;
        return chart ;
    } ;

    return chart;
}


//Function to parse a set of lists into a set of bubble maps
function htmlDataToBubbleMap(id){
	var parent = document.getElementById(id);	
	var keywordNodes = parent.getElementsByTagName('div');
	for(var i=0;i<keywordNodes.length;i++){
		var keywordNode = keywordNodes[i];
		var h2Element = keywordNode.firstElementChild;
		var ulElement = keywordNode.lastElementChild;
		var dataList = ulElement.childNodes;
		var keyword = h2Element.innerHTML;
		var keywordOutput = {};
		keywordOutput['name'] = keyword;
		var children = new Array;
		for(var j=0;j<dataList.length;j++){
			var dataPair = dataList[j].textContent.split(':');			
			if(typeof(dataPair[1]) != 'undefined'){
				var child = {};
				child["name"] = dataPair[0];
				child["size"] = dataPair[1];
				children[children.length] = child;
			}
		}
		keywordOutput['children'] = children;
		makeTimelineBubbleMap(keywordOutput,keywordNode.id);
		//keywordNode.removeChild(h2Element);
		//keywordNode.removeChild(ulElement);
	}
}


//Function to make a timeline bubble map
// Pass data and ID of parent node
function makeTimelineBubbleMap(root,id){


  var diameter = 500,
    format = d3.format(",d"),
    color = d3.scale.category20c();

  var bubble = d3.layout.pack()
    .sort(null)
    .size([diameter, diameter])
    .padding(1.5);
  var svg = d3.select("#" + id).append("svg")
    .attr("width", diameter)
    .attr("height", diameter)
    .attr("class", "bubble");

  var node = svg.selectAll(".node")
      .data(bubble.nodes(classes(root))
      .filter(function(d) { return !d.children; }))
    .enter().append("g")

      .attr("onclick", function(d) { return "window.location = '" + linkQuerystring + "&addkeyword=" + d.className + "';" })

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
      .style("font-size", function(d) { return (parseInt(d.value/9)+10) + "px"; })
      .text(function(d) { return d.className.substring(0, d.r / 3); });
}


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
