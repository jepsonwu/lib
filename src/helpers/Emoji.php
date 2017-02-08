<?php
namespace Jepsonwu\helpers;

class Emoji
{
    public static function input($str)
    {
        $word_list = preg_split("//u", $str, -1, PREG_SPLIT_NO_EMPTY);
        for ($i = 0; $i < count($word_list); $i++) {
            strlen($word_list[$i]) == 4 && $word_list[$i] = "[emoji]"
                . ltrim(bin2hex(mb_convert_encoding($word_list[$i], 'UCS-4', mb_internal_encoding())), 0)
                . "[/emoji]";
        }

        return implode("", $word_list);
    }

    public static function output($str)
    {
        return preg_replace_callback("/\[emoji\]([\w]+)\[\/emoji\]/i", function ($matches) {
            return mb_convert_encoding(implode("", array_map(function ($val) {
                return hex2bin(str_pad($val, 8, 0, STR_PAD_LEFT));
            }, str_split($matches[1], 5))), mb_internal_encoding(), "UCS-4");
        }, $str);
    }
}