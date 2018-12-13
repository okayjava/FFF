<?php
include_once dirname(__FILE__)."/config.php";
$INCDIR = dirname(__FILE__)."/include";
foreach (glob($INCDIR."/*.php") as $filename)
{
    include $filename;
}

$DB = new Maria();
