<?php
/**
 * Author:     CriticalGears (http://www.CriticalGears.io)
 * Website:    http://www.CriticalGears.io
 * Support:    http://CriticalGears.io/support-tickets/
 * Version:    2.3.3
 *
 * Copyright:   (c)    CriticalGears.io
 *
 *
 */

include_once "includes/bootstrap.php";

$settings->set("admin_section", "dashboard");

$a->addStyle("../assets/bootstrap/css/datepicker3.css");
$a->addScripts("../assets/bootstrap/js/bootstrap-datepicker.js",false);
$a->addScripts("assets/js/dashboard.js");
$a->addScripts("assets/js/Chart.js");

if (!$user->logon) {
    header("Location: index.php");
    exit();
}
$fromThisMonth = date("Y-m-01");

$fromLastMonthStart = date("Y-m-01",strtotime("now -1 month"));
$lastMonthDays = date("t",strtotime($fromLastMonthStart));
$lastMonthEnd = date("Y-m-{$lastMonthDays}",strtotime($fromLastMonthStart));

$now = date("Y-m-d");

$payment = new paymentModel();
$subscription = new subscriptionModel();
$invoice = new invoiceModel();

$totalPayments = $payment->getTotalVolume() /*+ $subscription->getTotalVolume()*/;
$thisMonthTotalPayments = $payment->getTotalVolume($fromThisMonth,$now) /*+ $subscription->getTotalVolume($fromThisMonth,$now)*/;
$lastMonthTotalPayments = $payment->getTotalVolume($fromLastMonthStart,$lastMonthEnd) /*+ $subscription->getTotalVolume($fromLastMonthStart,$lastMonthEnd)*/;
$totalSubscriptions = $subscription->getSubscriptionsCount();
$curr = $settings->display_currency;

$_fromLastMonth = date("Y-m-d",strtotime("-1 week"));
//last month successful charges
$successfulChargesValues =$successfulChargesLabels = array();
for($d = date("Y-m-d");$d> $_fromLastMonth;$d = date("Y-m-d",strtotime("$d -1 day"))){
    $successfulChargesLabels[] = $a->dateToIso($d);
    $successfulChargesValues[] = $payment->getPaymentsCount($d);

}
$successfulCharges = array(
    "labels"=>$successfulChargesLabels,
    "datasets"=>array(
        array(
            "label"=>"Successful Charges",
            "fillColor"=> "rgba(220,220,220,0.9)",
            "strokeColor"=> "rgba(220,220,220,0.8)",
            "highlightFill"=> "rgba(220,220,220,0.75)",
            "highlightStroke"=> "rgba(220,220,220,1)",
            "data"=>$successfulChargesValues
        )
    )
);

//last month subscriptions
$successfulSubscriptionsValues =$successfulSubscriptionsLabels = array();
for($d = date("Y-m-d");$d> $_fromLastMonth;$d = date("Y-m-d",strtotime("$d -1 day"))){
    $successfulSubscriptionsLabels[] = $a->dateToIso($d);
    $successfulSubscriptionsValues[] = $subscription->getSubscriptionsCount($d);

}
$successfulSubscriptions = array(
    "labels"=>$successfulSubscriptionsLabels,
    "datasets"=>array(
        array(
            "label"=>"Subscriptions Created",
            "fillColor"=> "rgba(220,220,220,0.5)",
            "strokeColor"=> "rgba(220,220,220,0.8)",
            "highlightFill"=> "rgba(220,220,220,0.75)",
            "highlightStroke"=> "rgba(220,220,220,1)",
            "data"=>$successfulSubscriptionsValues
        )
    )
);


$dayValues =$dayLabels = array();
for($d = $_fromLastMonth;$d<=date("Y-m-d") ;$d = date("Y-m-d",strtotime("$d +1 day"))){
    $dayLabels[] = $d==date("Y-m-d")?"Today":$a->dateToIso($d);
    $dayValues[] = number_format(/*$subscription->getTotalVolumeByDate($d)+*/$payment->getTotalVolumeByDate($d),2,".","");

}
$dateValues = array(
    "labels"=>$dayLabels,
    "datasets"=>array(
        array(
            "label"=>"Subscriptions Created",
            "fillColor"=> "rgba(220,220,220,0.5)",
            "strokeColor"=> "rgba(220,220,220,0.8)",
            "highlightFill"=> "rgba(220,220,220,0.75)",
            "highlightStroke"=> "rgba(220,220,220,1)",
            "data"=>$dayValues
        )
    )
);
$datesLabelsTemplate = $settings->currency_position=='before'?
    "<%if (label){%><%=label%>: <%}%>{$curr}<%= value %>":"<%if (label){%><%=label%>: <%}%><%= value %>{$curr}";

$sales_vs_subscriptions = array(
    array(
        "value"=> $payment->getPaymentsCount()*10,
        "color"=>"#5FBFDB",
        "highlight"=> "#86CEE3",
        "label"=> "Sales"
    ),
    array(
        'value'=> $subscription->getSubscriptionsCount()*10,
        'color'=> "#EDAB55",
        'highlight'=> "#F3CB94",
        'label'=> "Subscriptions"
    )
);

$totalInvoicesValue = $invoice->totalInvoicesValue();
$totalUnpaidInvoicesValue = $invoice->totalUnpaidInvoicesValue();
$totalCurrentInvoicesValue = $invoice->totalCurrentInvoicesValue();
$totalOverdueInvoicesValue1_15  = $invoice->totalOverdueInvoicesValue(1,15);
$totalOverdueInvoicesValue16_30 = $invoice->totalOverdueInvoicesValue(16,30);
$totalOverdueInvoicesValue31_45 = $invoice->totalOverdueInvoicesValue(31,45);
$totalOverdueInvoicesValue46    = $invoice->totalOverdueInvoicesValue(46,1000);

$a->getHeader();
?>
<script>
    var successfulCharges = <?php echo(json_encode($successfulCharges))?>;
    var successfulSubscriptions = <?php echo(json_encode($successfulSubscriptions))?>;
    var dateValues = <?php echo(json_encode($dateValues))?>;
    var sales_vs_subscriptions = <?php echo(json_encode($sales_vs_subscriptions))?>;
    var chartLabel = '<?php echo ($settings->currency_position=='before'?$curr:"")?><%=value%>'+
        '<?php echo ($settings->currency_position=='after'?$curr:"")?>';
    var datesLabelsTemplate = '<?php echo $datesLabelsTemplate?>'
</script>
<div class="container" role="main">

    <hr>
    <table class="dashboard_details">
        <tr>
            <td>
                <?php if($settings->currency_position=='before'){?>
                    <sup><?php echo($curr) ?></sup>
                <?php }?>
                <label><?php echo($a->decFormat($thisMonthTotalPayments)) ?></label>
                <?php if($settings->currency_position=='after'){?>
                    <sup><?php echo($curr) ?></sup>
                <?php }?>
                <b><?php echo($settings->live_currency) ?></b>
                <h5>This Month</h5>
            </td>
            <td>
                <?php if($settings->currency_position=='before'){?>
                    <sup><?php echo($curr) ?></sup>
                <?php }?>
                <label><?php echo($a->decFormat($lastMonthTotalPayments)) ?></label>
                <?php if($settings->currency_position=='after'){?>
                    <sup><?php echo($curr) ?></sup>
                <?php }?>
                <b><?php echo($settings->live_currency) ?></b>
                <h5>Last Month</h5>
            </td>
            <td>

                <label><?php echo($totalSubscriptions) ?></label>

                <h5>Subscriptions</h5>
            </td>
            <td>
                <?php if($settings->currency_position=='before'){?>
                    <sup><?php echo($curr) ?></sup>
                <?php }?>
                <label><?php echo($a->decFormat($totalPayments)) ?></label>
                <?php if($settings->currency_position=='after'){?>
                    <sup><?php echo($curr) ?></sup>
                <?php }?>
                <b><?php echo($settings->live_currency) ?></b>
                <h5>Total volume</h5>
            </td>
        </tr>
    </table>
    <hr>
    <div class="clearfix"></div>
    <div class="overview">
        <h4 style="float: left">Overview</h4>
        <div class="datesCont">
            <input type="text" id="dateFrom" value="<?php echo($a->dateToIso($_fromLastMonth)) ?>">
            <span>to</span>
            <input type="text" id="dateTo" value="<?php echo($a->dateToIso(date("Y-m-d"))) ?>">
        </div>
        <div class="clearfix"></div>
        <canvas id="plot"  height="500" width="1140"></canvas>

        <br>
        <br>
        <div class="row">
            <div class="col-md-6 col-sm-6 col-xs-12">
                <h4>Successful charges</h4>
                <div class="chart-cont">
                    <canvas id="successful_charges" width="554" height="300"></canvas>
                </div>
                <br><br>
                <h4>Subscriptions created</h4>
                <div class="chart-cont">
                    <canvas id="customer_created" width="554" height="300"></canvas>
                </div>
            </div>

            <div class="col-md-6 col-sm-6 col-xs-12">
                <h4>Sales vs. Subscriptions</h4>
                <div id="sales_vs_cont">
                    <canvas id="sales_vs" width="554" height="450"></canvas>
                    <div id="legend"></div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <br>
        <br>
        <h4>Total Receivables</h4>
        <table class="total_receivables">
            <tr>
                <td colspan="5">
                    <label>Total unpaid invoices <?php echo(PT_Core::getCurrencyText($totalUnpaidInvoicesValue,false)) ?></label>
                    <div id="receivables_bar"><span style="width: <?php echo $totalInvoicesValue>0?round($totalUnpaidInvoicesValue/$totalInvoicesValue*100):0?>%"></span></div>

                </td>
            </tr>
            <tr>
                <td>
                    <h5 class="blue">CURRENT</h5>
                    <span><?php echo(PT_Core::getCurrencyText($totalCurrentInvoicesValue,false)) ?></span>
                </td>
                <td>
                    <h5 class="red">OVERDUE</h5>
                    <span><?php echo(PT_Core::getCurrencyText($totalOverdueInvoicesValue1_15,false)) ?></span>
                    <i>1-15 Days</i>
                </td>
                <td>
                    <h5 class="red">&nbsp;</h5>
                    <span><?php echo(PT_Core::getCurrencyText($totalOverdueInvoicesValue16_30,false)) ?></span>
                    <i>16-30 Days</i>
                </td>
                <td>
                    <h5 class="red">&nbsp;</h5>
                    <span><?php echo(PT_Core::getCurrencyText($totalOverdueInvoicesValue31_45,false)) ?></span>
                    <i>31-45 Days</i>
                </td>
                <td>
                    <h5 class="red">&nbsp;</h5>
                    <span><?php echo(PT_Core::getCurrencyText($totalOverdueInvoicesValue46,false)) ?></span>
                    <i>Above 45 Days</i>
                </td>
            </tr>
        </table>
    </div>
</div>
</div>
<?php echo($a->getFooter()) ?>
