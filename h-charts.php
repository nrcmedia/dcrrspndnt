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
// Grafiek; de artikelen van vandaag, totaal tweets en een 'benchmark'
// beperk tot de 30 beste artikelen van de dag
//
$art_res = mysql_query('
select count(tweets.id) as tweets_today, artikelen.*
	from artikelen
		left join tweets on tweets.art_id = artikelen.id
	join (
    select artikelen.id
			from artikelen
			left join tweets on tweets.art_id = artikelen.id
		where year(artikelen.created_at) = year(now() ) and month(artikelen.created_at) = month(now()) and day(artikelen.created_at) = day(now() )
		group by artikelen.id
		order by count(tweets.id) desc
		limit 0,25
	) top_arts
where artikelen.ID = top_arts.ID
group by artikelen.ID
order by artikelen.created_at	' );
$today_tweets_title = 'Artikelen van vandaag';
// hiermee zetten we de labels en de x-as waardes
$num_arts = mysql_num_rows($art_res);
// zolang we nog niks terugkrijgen ('snacht ;-))
// gisteren halen
if($num_arts ==0)
{
	$art_res = mysql_query('
	select count(tweets.id) as tweets_today, artikelen.*
		from artikelen
			left join tweets on tweets.art_id = artikelen.id
		join (
  	  select artikelen.id
				from artikelen
				left join tweets on tweets.art_id = artikelen.id
			where year(artikelen.created_at) = year(now() ) and month(artikelen.created_at) = month(now()) and 	day(artikelen.created_at) = day(now() ) - 1
			group by artikelen.id
			order by count(tweets.id) desc
			limit 0,25
		) top_arts
	where artikelen.ID = top_arts.ID
	group by artikelen.ID
	order by artikelen.created_at	' );
	$today_tweets_title = 'Artikelen van gisteren';
}


$art_today_label = '';
$art_today_count = '';
$max_art_today = get_tweet_benchmark() + 4;
$i = 1;
$today_table_row = array();
while ($row = mysql_fetch_array($art_res))
{

	$og = unserialize($row['og']);
	$art_today_label .= '"'.trim(preg_replace('/\s\s+/', ' ', $og['title'])).'",';

	$i++;
	$art_today_count .= $row['tweets_today'].',';
	$max_art_today = max($max_art_today, $row['tweets_today'] + 5);
	$art_today_fenton .= floor(get_tweet_benchmark()).',';
}

$art_today_label  = substr($art_today_label,  0, strlen($art_today_label)  - 1);
$art_today_count  = substr($art_today_count,  0, strlen($art_today_count)  - 1);
$art_today_fenton = substr($art_today_fenton, 0, strlen($art_today_fenton) - 1);
$scalewidth4 = ceil($max_art_today / 10);
?>

		<h1>nrc.nl tweets in grafieken </h1>
<?php include ('menu.php'); ?>
		<div class="center full">

			<h2>Tweets per dag</h2>

			<div id="tot_tweets" style="position: relative;"></div>

			<script>
				$(function () {
        	$('#tot_tweets').highcharts({
            chart: { type: 'column' },
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
                headerFormat: '<table>',
                pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                    '<td style="padding:0"> <b>{point.y}</b> </td></tr>',
                footerFormat: '</table>',
                shared: true,
                useHTML: true
            },
            series: [{
            		name: 'Tweets',
                data: [<?php echo $chart1_data['data'];?>]

            }]
        });
      });
			</script>
			<p>De laatste 30 dagen</p>

			<h2>Tweets per uur</h2>
			<div id="hour_tweets"></div>
			<script>
				$(function () {
					$('#hour_tweets').highcharts({
						chart: { type: 'column' },
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
        });
       </script>

			<h2>Tweets vandaag</h2>
			<div id="tweets_pm" style="height:600px;"></div>
			<script>
				$(function () {
					$('#tweets_pm').highcharts({
						chart: { type: 'line' },
            plotOptions: {
            	column: {
            		pointPadding: 0,
            		borderWidth: 0,
            		groupPadding: 0,
            		shadow: false
            	}
            },

						//chart: {zoomType: 'xy'},
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
        });
       </script>

			<h2><?php echo $today_tweets_title;?></h2>
			<div id="today_tweets" style="height: 800px"></div>
			<script>
				$(function () {
					$('#today_tweets').highcharts({
						chart: { type: 'line' },
            title: { text: 'Meest getweete <?php echo $today_tweets_title;?>' },
            xAxis: { categories: [<?php echo $art_today_label;  ?>],
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
                data: [<?php echo $art_today_fenton;?>]
            }, {
            		type: 'column',
                name: 'Vandaag',
                data: [<?php echo $art_today_count;?>]
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
