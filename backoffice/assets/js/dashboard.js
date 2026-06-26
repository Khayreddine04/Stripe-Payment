$().ready(function () {
    "use strict";

    $('#dateFrom,#dateTo').datepicker({
        format: "mm/dd/yyyy",
        endDate: "+0d"
    }).on("changeDate", function(e){
        var dateFrom = $("#dateFrom").val();
        var dateTo = $("#dateTo").val();
        $.getJSON("ajax/updateChart.php",{dateFrom:dateFrom,dateTo:dateTo},function(data){
            if(data.res){
                updateChart(data.data);
            }else{
                alert(data.mes);
            }
        });
    });

    document.chartOptions = {
        responsive: true,
        bezierCurve: true,
        scaleLabel: chartLabel,
        tooltipTemplate: datesLabelsTemplate};

    var ctxOverview =  $("#plot").get(0).getContext("2d");
    var myBarChart1 = new Chart(ctxOverview).Line(dateValues,document.chartOptions);

    var chartOptions = {responsive: true};

    var ctx =  $("#successful_charges").get(0).getContext("2d");
    var myBarChart = new Chart(ctx).Bar(successfulCharges,chartOptions);

    var ctx1 =  $("#customer_created").get(0).getContext("2d");
    var myBarChart1 = new Chart(ctx1).Bar(successfulSubscriptions,chartOptions);

    // And for a doughnut chart
    var ctx2 =  $("#sales_vs").get(0).getContext("2d");
    var myDoughnutChart = new Chart(ctx2).Doughnut(sales_vs_subscriptions,{responsive: true,showTooltips: false,percentageInnerCutout : 90,legendTemplate : "<ul class=\"<%=name.toLowerCase()%>-legend\"><% for (var i=0; i<segments.length; i++){%><li><span style=\"background-color:<%=segments[i].fillColor%>\"></span><%if(segments[i].label){%><%=segments[i].label%><%}%></li><%}%></ul>"});
    var legend =  myDoughnutChart.generateLegend();
    jQuery("#legend").append(legend);

});

var updateChart = function(data){
    "use strict";

    $('#plot').replaceWith('<canvas id="plot" height="300" width="1140"></canvas>');

    var ctxOverview =  $("#plot").get(0).getContext("2d");
    var myBarChart1 = new Chart(ctxOverview).Line(data,document.chartOptions);
}
