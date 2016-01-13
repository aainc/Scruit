<?php
namespace Scruit;
class StringUtil
{
    public static function camelize($str)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $str)));
    }

    public static function decamelize($str)
    {
        return ltrim(preg_replace_callback('/([A-Z])/', function ($matches) {return '_' . strtolower($matches[1]);}, $str), '_');
    }

    public static function parseConfig($str)
    {
        $len = mb_strlen ($str, 'UTF8');
        $key = '';
        $val = '';
        for ( $i = 0; $i < $len; $i++) {
            $key = '';
            while (mb_substr ($str, $i++, 1, 'UTF8') === ' ');
            for ($i -= 1 ; $i < $len; $i++) {
                $char = mb_substr ( $str, $i, 1, 'UTF8' );
                if ($key && $char == ' ') continue;
                if ( $char === '=' ) {
                    $i++;
                    break;
                }
                $key .= $char;
            }

            while (mb_substr ($str, $i++, 1, 'UTF8') === ' ');
            $val = '';
            for ($i -= 1 ;$i < $len; $i++) {
                $char = mb_substr ($str, $i, 1, 'UTF8');
                if ($char === '"' || $char === "'") {
                    $quote = $char;
                    for ($i += 1; $i < $len; $i++) {
                        $char = mb_substr ($str, $i, 1, 'UTF8');
                        if ($char === '\\') {
                            $val .= mb_substr($str, ++$i, 1, 'UTF8');
                        } else if ($char === $quote) {
                            break;
                        } else {
                            $val .= $char;
                        }
                    }
                } else if ($char === ' ') {
                    break;
                } else {
                    $val .= $char;
                }
            }

            if ($key && $val) {
                $result[$key] = $val;
            }
        }
        if ($key && $val) {
            $result[$key] = $val;
        }
        return $result;
    }
}
