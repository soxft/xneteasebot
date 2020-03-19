<?php
function spaces($string){
    return preg_replace("/\s(?=\s)/","\\1",$string);
}