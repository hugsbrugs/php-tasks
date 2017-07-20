<?php

$conf = parse_ini_file(__DIR__ . '/config.ini');

//error_log(print_r($conf, true));
foreach($conf as $key => $val)
{
    define($key, $val);
}
