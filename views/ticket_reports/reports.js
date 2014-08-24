function showNoisyChart(v,head)
{  
   var chart; 
	chart = new Highcharts.Chart({
		chart: {
			renderTo: 'noisy_project_chart',
			defaultSeriesType: 'line',
			margin: [50, 150, 60, 80],			
			borderColor: "#4572A7",
			borderWidth: 1,
			showAxes: true
		},
		title: {
			text: 'Noisy Project List',
			style: {
				margin: '10px 100px 0 0' // center it
			}
		},
		xAxis: {
			categories: head,
			title: {
				text: 'Month'
			}
		},
		yAxis: {
			title: {
				text: 'Tickets'
			},
			plotLines: [{
				value: 0,
				width: 1,
				color: '#808080'
			}]
		},
		tooltip: {
			formatter: function() {
	            return '<b>'+ this.series.name +'</b><br/>'+
					this.x +': '+ this.y;
			}
		},
		legend: {
			layout: 'vertical',
			style: {
				left: 'auto',
				bottom: 'auto',
				right: '10px',
				top: '100px'
			}
		},
		credits:{
				    enabled: false
				},
		series : v
						
	});			
}

function showTicketChart(v)
{   
   var chart; 
   chart = new Highcharts.Chart({
				chart: {
					renderTo: 'ticket_report_chart',
					defaultSeriesType: 'pie',
					margin: [60, 200, 60, 170],
					borderColor: "#4572A7",    
               borderWidth: 1
				}, 
				title: {
					text: 'Ticket Report'
				},
				plotArea: {
					shadow: null,
					borderWidth: null,
					backgroundColor: null
				},
				tooltip: {
					formatter: function() {
						return '<b>'+ this.point.name +'</b>: '+ this.y;
					}
				},
				plotOptions: {
					pie: {
						allowPointSelect: true,
						dataLabels: {
							enabled: true,
							formatter: function() {
								if (this.y > 5) return this.point.name;
							},
							color: 'white',
							style: {
								font: '13px Trebuchet MS, Verdana, sans-serif'
							}
						}
					}
				},
				legend: {
					layout: 'vertical',
					style: {
						left: 'auto',
						bottom: 'auto',
						right: '50px',
						top: '100px'
					}
				},
				credits:{
				    enabled: false
				},
			   series: [{
					data: v
				}]
			});  
}

function showResolverReport(u,v)
{
    chart = new Highcharts.Chart({
				chart: {
					renderTo: 'resolver_report_chart',
					defaultSeriesType: 'column',
					borderColor: "#4572A7",    
               borderWidth: 1
				},
				title: {
					text: 'Resolver Report'
				},
				xAxis: {
					categories: u
				},
				yAxis: {
					min: 0,
					title: {
						text: 'Number of Ticket'
					}
				},
				legend: {
					layout: 'vertical',
					backgroundColor: '#FFFFFF',
					style: {
						left: '450px',
						top: '10px',
						bottom: 'auto'
					}
				},
				tooltip: {
					formatter: function() {
						return '<b>'+ this.series.name +'</b><br/>'+
							this.x +': '+ this.y;
					}
				},
				plotOptions: {
					column: {
						pointPadding: 0.2,
						borderWidth: 0
					}
				},
				credits:{
				    enabled: false
				},
			   series: v
			});
			
}