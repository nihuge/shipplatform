<?php
$a1 = array('aaa');
$a2 = array(
    array(
        'status'=>1,
        'remark'=>'aaa'
    )
    );
print_r(array_combine($a1,$a2));