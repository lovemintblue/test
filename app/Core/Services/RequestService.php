<?php

declare(strict_types=1);

namespace App\Services;

namespace App\Core\Services;

class RequestService
{

    /**
     * 获取请求
     * @param array $data 参数的数组
     * @param  $key
     * @param string $type
     * @param  $defaultValue
     * @return string
     */
    public function getRequest($data, $key, $type = 'string', $defaultValue = null)
    {
        if (!isset($data[$key])) {
            return $defaultValue;
        }
        if (is_array($data[$key])) {
            foreach ($data[$key] as $index => $value) {
                $value = trim($value);
//                if (get_magic_quotes_gpc() == "on") {
                    $value = stripslashes($value);
//                }
                $data[$key][$index] = addslashes($this->word($value));
            }
            return $data[$key];
        }
        if ($type == 'int') {
            return intval($data[$key]);
        } elseif ($type == 'float' || $type == 'double') {
            return doubleval($data[$key]);
        } elseif ($type == 'html') {
            $value = trim($data[$key]);
//            if (get_magic_quotes_gpc() == "on") {
                $value = stripslashes($value);
//            }
            return addslashes($value);
        } else {
            return $this->filter($data[$key]);
        }

    }

    /**
     * 过滤值
     * @param  $value
     * @return string
     */
    public function filter($value)
    {
        $value = trim("".$value);
//        if (get_magic_quotes_gpc() == "on") {
            $value = stripslashes($value);
//        }
        return addslashes($this->word($value));
    }

    /**
     * 过滤关键词
     * @param string $str 要过滤的文本
     * @return string
     */
    public function word($str)
    {
        $word = array("expression", "@import", "select ", "select/*", "update ", "update/*", "delete ", "delete/*", "insert ", "insert/*", "updatexml", "concat", "()", "`", "/**/", "union(");
        foreach ($word as $val) {
            if (stripos($str, $val) !== false) {
                return '';
            }
        }
        if (preg_match("/<(.*)script/isU", $str)) {
            return '';
        }
        if (preg_match("/<(.*)iframe/isU", $str)) {
            return '';
        }
        return $str;
    }

    /**
     * 过滤二进制表情
     * @param string $str 要过滤的文本
     * @return string
     */
    public function removeEmoji($str)
    {
        $str = preg_replace_callback('/./u', function ($match) {
            return strlen($match[0]) >= 4 ? '' : $match[0];
        }, $str);
        return $str;
    }


}