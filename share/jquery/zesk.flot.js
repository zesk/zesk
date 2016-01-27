// Taken from examples
function flot_weekend_areas_x(axes)
{
	var markings = [];
	var d = new Date(axes.xaxis.min);
	// go to the first Saturday
	d.setUTCDate(d.getUTCDate() - ((d.getUTCDay() + 1) % 7))
	d.setUTCSeconds(0);
	d.setUTCMinutes(0);
	d.setUTCHours(0);
	var i = d.getTime();
	do {
		// when we don't set yaxis the rectangle automatically
		// extends to infinity upwards and downwards
		markings.push({
			xaxis: {
				from: i,
				to: i + 2 * 24 * 60 * 60 * 1000
			}
		});
		i += 7 * 24 * 60 * 60 * 1000;
	} while (i < axes.xaxis.max);

	return markings;
}

var plotPoints = {};
var plotClicks = {};

function flot_tooltip_hide(series_name)
{
	$('#'+series_name+'-tooltip-id').remove();
}

function flot_tooltip_show(series_name, x, y, contents)
{
    $('<div class="plot-tooltip" id="'+series_name+'-tooltip-id">' + contents + '</div>').css( {
        position: 'absolute',
        display: 'none',
        top: y + 5,
        left: x + 5,
        border: '1px solid #fdd',
        padding: '2px',
        'background-color': '#fee',
        opacity: 0.80
    }).appendTo("body").fadeIn(200);
}

function flot_click(event, pos, item)
{
    if (item) {
		var series_name = item.series.name || 'plot';
		var previousClick = plotClicks[series_name] || null;
        $("#clickdata").text("You clicked point " + item.dataIndex + " in " + item.series.label + ".");
        plot.highlight(item.series, item.datapoint);
        if (previousClick) {
        	plot.unhighlight( previousClick[0], previousClick[1] );
        }
        plotClicks[series_name] = [ item.series, item.datapoint ];
    }
}

function flot_hover(event, pos, item)
{
    if (item) {
		var series_name = item.series.name || 'plot';
	   	var previousPoint = plotPoints[series_name] || null;
        if (previousPoint != item.datapoint) {
            plotPoints[series_name] = item.datapoint;
            flot_tooltip_hide(series_name);
            var x = item.datapoint[0], y = item.datapoint[1], d = new Date();
            d.setTime(x+1);
            d = d.format('{WWW}, {MMM} {DDD}');
            flot_tooltip_show(series_name, item.pageX, item.pageY, y + ' ' + item.series.label + " on " + d);
        }
//  } else {
//      $("#tooltip").remove();
//      plotPoints[series_name] = null;
    }
}

function flot_color(i)
{
	var c = flot_colors();
	return c[i % c.length];
}

function flot_colors()
{
	return [
		'#EEC13E',
		'#ADD8F8',
		'#CD4B49',
		'#4AA64D',
		'#9443ED',
		'#C4A244',
		'#95B0C8',
		'#A33C3B'
	];
}