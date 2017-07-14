<?php
/**
 * Created by PhpStorm.
 * User: htek
 * Date: 2017/07/06
 * Time: 20:55
 */

ini_set("display_errors",0);//Default 0(off)

require ('sqlconfig-file');

if(isset($_GET['key'])) {

    //http://every-rating.com/php/php-3.html
    $text = $_GET['key'];
    // 全角で書かれている場合半角に変換し、全角スペースを除去
    $text = trim(mb_convert_kana($text, 'as', 'UTF-8'));
// 半角英数字以外の文字列は除去
    $hankaku = preg_replace('/[^a-z]/', '', $text);
    $result = $db->query("delete from chat where chatkey = '{$hankaku}';");
    echo $db->affected_rows;
}else{
    echo "nokey";
}
