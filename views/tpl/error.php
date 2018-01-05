<?php


/*
primary
secondary
success
danger
warning
info
light
dark
*/


?>
<?php if(@$notify[0] != "" ):
    $alertType = (!array_key_exists(1 , $notify)) ? "success" : $notify[1];
    ?>
    <div class="alert alert-<?=$alertType?> <?=@$notify[2]?> alert-dismissible fade show" role="alert">
        <strong><?=$notify[0]?></strong>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>