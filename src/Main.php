<?php

use Kami\Kpsr\ExtendedClass;

//require_once "../vendor/autoload.php";
require_once "../kpsr_autoload.php";

// deno-like web imports could be kind of funny. Like this:
// use __kpr_dynamic\packagist\superroute\slocker\v0_2_0 ;

$testExtended = new ExtendedClass();
$testExtended->extended();