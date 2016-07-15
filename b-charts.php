<!DOCTYPE html>
<html>
	<head>
		<meta charset="utf-8">
		<meta name="author" content="xiffy">
		<title>nrc.nl versus vk.nl; de tweets in grafieken</title>
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
$chart1_data = us_them_per_day();

// Grafiek 2;
// Tweets per uur
$chart2_data = us_them_today();

$chart3_data = us_them_articles();

?>

		<h1>nrc.nl versus vk.nl; de tweets in grafieken (live)</h1>
<?php include ('menu.php'); ?>
		<div class="center full">


			<h2>Tweets per dag</h2>

			<div id="tot_tweets" style="position: relative;"></div>

			<script>
				$(function () {
					Highcharts.setOptions({
						colors: ['#D30910', '#003366', '#50B432', '#DDDF00', '#24CBE5', '#64E572', '#FF9655', '#FFF263', '#6AF9C4']
					});

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
            		borderWidth: 0,
            		groupPadding: 0,
            	}
            },
            tooltip: {
								valueSuffix: ' tweets',
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
            	  name: 'nrc.nl',
            	  data: [<?php echo $chart1_data['data'];?>]

            },{
            		name: 'vk.nl',
                data: [<?php echo $chart1_data['vkdata'];?>]

            }]
        });
        function tweets_per_dayRequestData()
        {
  	      	$.ajax({
  	      		url: 'live-data.php?type=us_them_per_day',
  	      		success: function(data)
  	      		{
  	      			day_chart.series[1].setData(data[2]);
  	      			day_chart.series[0].setData(data[1]);
								var j = 0;

								for(var i=0, l=data[2].length; i < l; i++){
									if ( data[2][i] != null)
									{
										stack = data[2][i];
										done = data[1][i];
										total = parseInt(stack) + parseInt(done);
										$('.prestatie'+j).html( '<strong>'+done+'</strong><br/>[tot: '+total+']' );
										j++;
									}
									$('.prestatie'+j).html('<strong>'+data[1][ data[1].length - 1] +'</strong><br/>&nbsp;');
								}
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
                name: 'nrc.nl',
                data: [<?php echo $chart2_data['us_data'];?>]
            }, {
                name: 'vk.nl',
                data: [<?php echo $chart2_data['them_data'];?>]
            } ]
        	});
	        function tweets_per_uurrequestData()
  	      {
  	      	$.ajax({
  	      		url: 'live-data.php?type=us_them_today',
  	      		success: function(data)
  	      		{
  	      			hour_chart.series[0].setData(data[1]);
  	      			hour_chart.series[1].setData(data[2]);
  	      			hour_chart.xAxis[0].setCategories(data[0]);
  	      			setTimeout(tweets_per_uurrequestData, 60000); // eens per minuut
  	      		}
						});
    	    }

        });
       </script>


			<h2>Meest getweete artikelen van vandaag</h2>
			<div id="today_tweets" style="height: 800px"></div>
			<script>
				$(function () {
					article_chart = new Highcharts.Chart ({
						chart: { type: 'line', renderTo: 'today_tweets', events: { load: tweets_per_articlerequestData } },
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
            series: [ {
            		type: 'column',
                name: 'nrc.nl',
                data: []
            }, {
            		type: 'line',
            		name: 'vk.nl'
            } ]
        	});
        	function tweets_per_articlerequestData()
  	      {
  	      	$.ajax({
  	      		url: 'live-data.php?type=us_them_articles',
  	      		success: function(data)
  	      		{
  	      			article_chart.series[0].setData(data);
								//article_chart.xAxis[0].setCategories(data[0]);
  	      			setTimeout(tweets_per_articlerequestData, 60000); // eens per minuut
  	      		}
  	      	});
  	      }
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
