<!-- Modal -->
<div class="modal fade" id="terms_and_conditions" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel">Terms and Conditions</h4>
            </div>
            <div class="modal-body">
                <?php 
                // Initialize terms and conditions
                $terms_and_conditions = isset($terms_and_conditions) ? $terms_and_conditions : 'No terms and conditions available.';
                echo stripslashes($terms_and_conditions);
                ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal" onclick="$('#pt_terms').prop('checked', true)">Accept</button>
                <button type="button" class="btn btn-danger" data-dismiss="modal" onclick="$('#pt_terms').removeAttr('checked')">Decline</button>
            </div>
        </div>
    </div>
</div>


<!-- Modal -->
<div class="modal fade" id="popup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" data-dismiss="modal">Close</button>

            </div>
        </div>
    </div>
</div>
