<?php
/**
 * Created by PhpStorm.
 * User: htek
 * Date: 2017/07/06
 * Time: 20:55
 */

ini_set("display_errors",0);//Default 0(off)

require ('sqlconfig-file');


//http://qiita.com/TetsuTaka/items/bb020642e75458217b8a
$str = array_merge(range('a', 'z'));
$r_str = null;
$length =5;
for ($i = 0; $i < $length; $i++) {
    $r_str .= $str[rand(0, count($str) - 1)];
}

$result = $db->query("INSERT INTO `chat` (`chatkey`, `createdate`) VALUES ('{$r_str}', CURRENT_DATE());");

echo $r_str;
