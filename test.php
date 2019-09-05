<?php
$a1 = array('aaa'=>'aaa');
$a2 = array('aaa'=>'aaa','aab'=>'aab','aac'=>'aac','aad'=>'aad');
print_r(array_diff_assoc($a1,$a2));