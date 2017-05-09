<?php
require_once(__DIR__ . '/ttr/require.php');

//$str = "\x1b[31mRED\x1b[39m";
$str = "\x1b[01;31mRED\x1b[0m";
var_dump(ttr\str\rem_ctrl_char($str));


//echo preg_replace('/[\x00-\x09\x0B\x0C\x0E-\x1F\x7F]/', '', $str);
//echo dechex(ord("\x1b")) . PHP_EOL;

