<?php
function ftime($s) {
    if ($s <= 60*60*24) {
        if($s >= 60*60)
        {
            $ftime = gmdate("H小时i分s秒",$s);
        }else if($s>= 60)
        {
            $ftime = gmdate("i分s秒",$s);
        }else{
            $ftime = gmdate("s秒",$s);
        }
    } else {
        $ftime = "pass";
    }
    return $ftime;
}
?>