<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <link rel="stylesheet" href="assets/css/invoice.css">
</head>
<body>
<div class="wrapper">
    <div style="max-width: 1000px;margin: 0 auto">
        <?php echo($invoice_template) ?>
    </div>
</div>
<?php if($invoice_print){?>
<script type="text/javascript">
    (function(){
        window.print();
    })()
</script>
<?php }?>
</body>

</html>
