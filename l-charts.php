<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="author" content="xiffy">
		<title>nrc.nl, de tweets in grafieken</title>
		<link rel="stylesheet" href="./style2.css" />
		<link rel="alternate" type="application/rss+xml" title="Artikelen van nrc.nl" href="./rss.php">
		<script src="//ajax.googleapis.com/ajax/libs/jquery/2.0.3/jquery.min.js"></script>
		<script src="highcharts.js"></script>
	</head>
	<body>


<?php
require_once('settings.local.php');
require_once('functions.php');
include('db.php');

// Grafiek 1
// Tweets per dag, de laatste 30 dagen in de database
$chart1_data = tweets_per_day();

// Grafiek 2;
// Tweets per uur en de dagtrend daar op afgezet
 $chart2_data = tweets_today();

//
// Grafiek 3;
$chart3_data = tweets_per_minute();

//
// Grafiek 4; de artikelen van vandaag, totaal tweets en een 'benchmark'
// beperk tot de 30 beste artikelen van de dag
$chart4_data = tweets_per_article();

?>

		<h1>nrc.nl tweets in grafieken </h1>
<?php include ('menu.php'); ?>
		<div class="center full">

			<h2>Tweets per dag</h2>

			<div id="tot_tweets" style="position: relative;"></div>

			<script>
				$(function () {
        	day_chart = new Highcharts.Chart({
            chart: { type: 'column',
            	       renderTo: 'tot_tweets',
            	       events: { load: tweets_per_dayRequestData}
            	     },
            title: { text: 'Totaal aantal tweets per dag' },
            xAxis: { categories: [<?php echo $chart1_data['label'];  ?>] },
            yAxis: {
                min: 0,
                title: {
                    text: 'Tweets per dag'
                }
            },
            plotOptions: {
            	column: {
            		pointPadding: 0,
            		borderWidth: 0
            	}
            },
            tooltip: {
								valueSuffix: ' tweets',
                shared: true,
                useHTML: true
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                floating: true,
                shadow: true,
                x: 80,
                y: 20,
                verticalAlign: 'top',
                borderWidth: 1,
                backgroundColor: '#FCFFC5'
            },
            series: [{
            		name: 'Tweets',
                data: [<?php echo $chart1_data['data'];?>]

            }]
        });
        function tweets_per_dayRequestData()
        {
  	      	$.ajax({
  	      		url: 'live-data.php?type=per_day',
  	      		success: function(data)
  	      		{
  	      			day_chart.series[0].setData(data[1]);

  	      			setTimeout(tweets_per_dayRequestData, 60000); // eens per minuut
  	      		}
						});

        }
      });
			</script>
			<p>De laatste 30 dagen</p>

			<h2>Tweets per uur</h2>
			<div id="hour_tweets"></div>
			<script>
				$(function () {
					hour_chart = new Highcharts.Chart({
						chart: {
							renderTo: 'hour_tweets',
							type: 'column',
							events: {
								load: tweets_per_uurrequestData
							}
						},
            title: { text: 'Tweets per uur' },

            xAxis: { categories: [<?php echo $chart2_data['label'];  ?>] },
            yAxis: { title: { text: 'Tweets per uur' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                min: 0
            },
            plotOptions: {
            	column: {
            		pointPadding: 0,
            		borderWidth: 0,
            		groupPadding: 0,
            		shadow: false
            	}
            },

            tooltip: {
                valueSuffix: ' tweets ',
                shared: true
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                floating: true,
                shadow: true,
                x: 80,
                y: 20,
                verticalAlign: 'top',
                borderWidth: 1,
                backgroundColor: '#FCFFC5'
            },
            series: [{
                name: 'Gemiddeld',
                data: [<?php echo $chart2_data['average_data'];?>]
            }, {
                name: 'Vandaag',
                data: [<?php echo $chart2_data['current_data'];?>]
            }, {
            		type: 'line',
            		name: 'Voorspelling',
            		data: [<?php echo $chart2_data['projection_data'];?>],
            		marker: {
                	radius: 5
                },
                lineWidth: 1
            } ]
        	});
	        function tweets_per_uurrequestData()
  	      {
  	      	$.ajax({
  	      		url: 'live-data.php?type=per_hour',
  	      		success: function(data)
  	      		{
  	      			hour_chart.series[0].setData(data[1]);
  	      			hour_chart.series[1].setData(data[2]);
								hour_chart.series[2].setData(data[3]);

  	      			setTimeout(tweets_per_uurrequestData, 60000); // eens per minuut
  	      		}
						});
    	    }

        });
       </script>

			<h2>Tweets vandaag</h2>
			<div id="tweets_pm" style="height:600px;"></div>
			<script>
				$(function () {
					minute_chart = new Highcharts.Chart({
						chart: { renderTo: 'tweets_pm', type: 'column',
							       events: { load: tweets_per_minuterequestData }
						},
            plotOptions: {
            	column: { pointPadding: 0, borderWidth: 0, groupPadding: 0, shadow: false	}
            },
            title: { text: 'Tweets per 5 minuten' },
            xAxis: { categories: [<?php echo $chart3_data['label'];  ?>],
            				 labels: {
            				 		rotation: -90,
            				 		style: {fontSize: 11},
            				 		formatter: function () {
            				 			var text = this.value;
            				 			if(text.substr(3,2) == '00')
            				 			{
            				 				formatted = text;
            				 			}
            				 			else if(text.substr(3,2) == '30')
            				 			{
            				 				formatted = '30';
            				 			}
            				 			else
            				 			{
            				 				formatted =  ' ';
            				 			}
            				 			return '<div class="js-ellipse" title="' + text + '">' +
            				 							formatted + '</div>';
                    		},
            				 	}
            },
            yAxis: { title: { text: 'Tweets per 5 minuten' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                min: 0
            },
            tooltip: {
            		headerFormat: '<div style="font-size: 10px;line-height:14px">{point.key}</div><br/>',
                valueSuffix: ' tweets ',
                shared: true
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                floating: true,
                shadow: true,
                x: 80,
                y: 20,
                verticalAlign: 'top',
                borderWidth: 1,
                backgroundColor: '#FCFFC5'
            },
            series: [{
            		type: 'area',
            		name: 'Vorige week',
            		data: [<?php echo $chart3_data['last_week_value'];?>],
            		fillOpacity: 0.2,
                marker: {
                	radius: 2
                },
                lineWidth: 2
            }, {
            		type: 'area',
                name: 'Gemiddeld',
                data: [<?php echo $chart3_data['average_value'];?>],
                fillOpacity: 0.2,
                marker: {
                	radius: 2
                },
                lineWidth: 2
            }, {
            		type: 'column',
                name: 'Vandaag',
                data: [<?php echo $chart3_data['today_value'];?>]
            } ]
        	});
	        function tweets_per_minuterequestData()
  	      {
  	      	$.ajax({
  	      		url: 'live-data.php?type=per_minute',
  	      		success: function(data)
  	      		{
  	      			minute_chart.series[0].setData(data[2]);
  	      			minute_chart.series[1].setData(data[3]);
								minute_chart.series[2].setData(data[1]);

  	      			setTimeout(tweets_per_minuterequestData, 60000); // eens per minuut
  	      		}
  	      	});
  	      }

        });
       </script>

			<h2>Meest getweete artikelen van vandaag</h2>
			<div id="today_tweets" style="height: 800px"></div>
			<script>
				$(function () {
					$('#today_tweets').highcharts({
						chart: { type: 'line' },
            title: { text: 'Meest getweete artikelen van vandaag' },
            xAxis: { categories: [<?php echo $chart4_data['label'];  ?>],
            				 labels: {
            				 		formatter: function () {
            				 			var text = this.value,
            				 			formatted = text.length > 30 ? text.substring(0, 40) : text;
            				 			return '<div class="js-ellipse" style="width:150px; overflow:hidden" title="' + text + '">' +
            				 							formatted + '</div>';
                    		},
                    		rotation: -70,
            				 		style: {
            				 					 		color: '#000',
            				 					 		font: '12px Trebuchet MS, Verdana, sans-serif'
            				 					 }
            				 	}
            			 },
            yAxis: { title: { text: 'Tweets per artikel' },
                plotLines: [{ value: 0, width: 1, color: '#808080' }],
                min: 0
            },
            plotOptions: {
            	column: {
            		pointPadding: 0,
            		borderWidth: 0,
            		groupPadding: 0,
            		shadow: false
            	}
            },

            tooltip: {
                valueSuffix: ' tweets '
            },
            legend: {
                layout: 'vertical',
                align: 'left',
                floating: true,
                shadow: true,
                x: 80,
                y: 20,
                verticalAlign: 'top',
                borderWidth: 1,
                backgroundColor: '#FCFFC5'
            },
            series: [{
								type: 'area',
                name: 'Gemiddeld',
                fillOpacity: 0.2,
                marker: {
                	radius: 2
                },
                lineWidth: 2,
                data: [<?php echo $chart4_data['average_value'];?>]
            }, {
            		type: 'column',
                name: 'Vandaag',
                data: [<?php echo $chart4_data['today_value'];?>]
            } ]
        	});
        });




       </script>

			<p>De hoogst scorende artikelen van vandaag, maximaal 25. De laagst scorende valt uit de grafiek zodra er meer dan 25 artikelen zijn gepubliceerd / gevonden.</p>

		</div>
<?php include('footer.php') ?>
		<script>
/**
 * Grid theme for Highcharts JS
 * @author Torstein Hønsi
 */

Highcharts.theme = {
   colors: ['#058DC7', '#50B432', '#ED561B', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4'],
   chart: {
      backgroundColor: {
         linearGradient: { x1: 0, y1: 0, x2: 1, y2: 1 },
         stops: [
            [0, 'rgb(255, 255, 255)'],
            [1, 'rgb(240, 240, 255)']
         ]
      },
      borderWidth: 2,
      plotBackgroundColor: 'rgba(255, 255, 255, .9)',
      plotShadow: true,
      plotBorderWidth: 1
   },
   title: {
      style: {
         color: '#000',
         font: 'bold 16px "Trebuchet MS", Verdana, sans-serif'
      }
   },
   subtitle: {
      style: {
         color: '#666666',
         font: 'bold 12px "Trebuchet MS", Verdana, sans-serif'
      }
   },
   xAxis: {
      gridLineWidth: 1,
      lineColor: '#000',
      tickColor: '#000',
      labels: {
         style: {
            color: '#000',
            font: '11px Trebuchet MS, Verdana, sans-serif'
         }
      },
      title: {
         style: {
            color: '#333',
            fontWeight: 'bold',
            fontSize: '12px',
            fontFamily: 'Trebuchet MS, Verdana, sans-serif'

         }
      }
   },
   yAxis: {
      minorTickInterval: 'auto',
      lineColor: '#000',
      lineWidth: 1,
      tickWidth: 1,
      tickColor: '#000',
      labels: {
         style: {
            color: '#000',
            font: '11px Trebuchet MS, Verdana, sans-serif'
         }
      },
      title: {
         style: {
            color: '#333',
            fontWeight: 'bold',
            fontSize: '12px',
            fontFamily: 'Trebuchet MS, Verdana, sans-serif'
         }
      }
   },
   legend: {
      itemStyle: {
         font: '9pt Trebuchet MS, Verdana, sans-serif',
         color: 'black'

      },
      itemHoverStyle: {
         color: '#039'
      },
      itemHiddenStyle: {
         color: 'gray'
      }
   },
   labels: {
      style: {
         color: '#99b'
      }
   },

   navigation: {
      buttonOptions: {
         theme: {
            stroke: '#CCCCCC'
         }
      }
   }
};

// Apply the theme
var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
</script>
	</body>
</html>
