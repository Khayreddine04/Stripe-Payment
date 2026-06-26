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
?>

<form id="gridForm" method="get">

    <table id="grid" class="display nowrap" cellspacing="0" width="100%">
        <thead>
        <tr>
            <?php foreach ($columns as $col) { ?>

                <th><?php echo($col) ?></th>
            <?php } ?>
        </tr>
        </thead>

        <tfoot>
        <tr>
            <?php foreach ($columns as $col) { ?>

                <th><?php echo($col) ?></th>
            <?php } ?>
        </tr>
        </tfoot>
    </table>
</form>
<script>
    $().ready(function() {
        // validate the comment form when it is submitted
        $('#grid').DataTable({
            "order":[[<?php echo (isset($pt_order_col)?$pt_order_col:3)?>, '<?php echo (isset($pt_order_type)?$pt_order_type:'desc')?>']],
            "language": {
                "lengthMenu": "View per page: _MENU_ ",
                "zeroRecords": "Nothing found - sorry",
                "info": "Showing <b>_START_</b> - <b>_END_</b>",
                "infoEmpty": "Showing <b>0</b> ",
                "infoFiltered": "from <b>_MAX_</b> records"
            },
            responsive: true,
            "processing": true,
            "serverSide": true,
            "ajax": "<?php echo($request_uri)?>",
            /*dom: 'Bfrtip',*/
            buttons: [
                {
                    extend: 'csv',
                    exportOptions: {
                        columns: getPrintColumns()
                    }
                },
                {
                    extend: 'pdf',
                    exportOptions: {
                        columns: getPrintColumns()
                    }
                },
                {
                    extend: 'print',
                    exportOptions: {
                        columns: getPrintColumns()
                    }
                }

            ]
        });
        <?php if($can_delete){?>
            $('#grid_wrapper .toolbar').prepend("<input name='delete' id='delete_button' style='float: left;margin-right: 9px;' value='Delete' type='submit' class='btn  btn-danger' onclick='deleteItems(this,event);'/>");
        <?php } ?>
        if($('#grid_filter').length){
            var search = $(".dataTables_filter").find("input");

            search.attr("placeholder","Search")
            search.appendTo($("#grid_filter"))
                .wrap("<div class=\"input-group\">")
                .after("<span class=\"input-group-addon bg-gr\"><span class=\"glyphicon glyphicon-search\" aria-hidden=\"true\"></span></span>")
                .parent().prev().remove();

        }

        $("#grid thead input,#grid tfoot input").on("click",function(e){

            e.stopPropagation();
            var $el = $(this);

            if($el.is(":checked")){
                $(this).parents("table").find("input[name^='del_id']").prop("checked",true);
            }else{
                $(this).parents("table").find("input[name^='del_id']").prop("checked",false);
            }
        });
        $('#grid').on( 'draw.dt', function () {
            $('[data-toggle="tooltip"]').tooltip()
        } );
    })
function deleteItems(el,event){
    event.preventDefault();
    var form = el.form;
    if($("input[name^='del_id']:checked").length<1){

        swal({
            title: 'Warning',
            text: 'Please, select items to delete',
            type: "warning",
            showCancelButton: false,
            confirmButtonColor: '#DD6B55',
            confirmButtonText: 'Proceed',
            cancelButtonText: "Cancel"

        })
        return false;
    }

    swal({
        title: 'Warning',
        text: 'Are you about to delete selected record(s)?',
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: '#DD6B55',
        confirmButtonText: 'Proceed',
        cancelButtonText: "Cancel"

    }).then(function (result) {
        $(form).append("<input type='hidden' name='delete' value='1'/>")
        form.submit();
    });

}
</script>
