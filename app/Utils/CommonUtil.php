<?php

declare(strict_types=1);

namespace App\Utils;

class CommonUtil
{

    /**
     * 数字缩写
     * @param $num
     * @return string
     */
    public static function formatNum($num)
    {
        if ($num > 10000){
            $thousand = floor($num / 10000);
            $hundred = floor(($num - $thousand * 10000) / 1000);
            $num = $thousand . '.' .$hundred . 'w';
        }elseif ($num > 1000){
            $thousand = floor($num / 1000);
            $hundred = floor(($num - $thousand * 1000) / 100);
            $num = $thousand . '.' .$hundred . 'k';
        }
        return $num;
    }

    /**
     * 检查是否手机号码
     * @param $phone
     * @return bool
     */
    public static function isPhoneNumber($phone)
    {
        if (strlen($phone) > 12 || strlen($phone) < 5) {
            return false;
        }
        return $phone;
    }
    /**
     * 格式化时间
     * @param $ptime
     * @return false|string
     */
    public static function ucTimeAgo($ptime)
    {
        $etime = time() - $ptime + 1;
        switch ($etime) {
            case $etime <= 60:
                $msg = '刚刚';
                break;
            case $etime > 60 && $etime <= 60 * 60:
                $msg = floor($etime / 60) . '分钟前';
                break;
            case $etime > 60 * 60 && $etime <= 24 * 60 * 60:
                $msg = date('Ymd', $ptime) == date('Ymd', time()) ? '今天' . date('H:i', $ptime) : '昨天';
                break;
            case $etime > 24 * 60 * 60 && $etime <= 2 * 24 * 60 * 60:
                $msg = date('Ymd', $ptime) + 1 == date('Ymd', time()) ? '昨天' . date('H:i', $ptime) : '前天 ';
                break;
            case $etime > 2 * 24 * 60 * 60 && $etime <= 12 * 30 * 24 * 60 * 60:
                $msg = date('Y', $ptime) == date('Y', time()) ? date('m-d H:i', $ptime) : date('Y-m-d H:i', $ptime);
                break;
            default:
                $msg = date('m-d', $ptime);
        }
        return $msg;
    }

    /**
     * 生成订单号
     * @param string $per
     * @return string
     */
    public static function createOrderNo($per = '')
    {
        return $per . date('YmdHis') . mt_rand(10000, 99999);
    }

    /**
     * 汉字转拼音
     * @param  $s
     * @param  $isFirst
     * @return string
     */
    public static function pinyin($s, $isFirst = false)
    {
        static $pinyins;
        $s = trim($s);
        $len = strlen($s);
        if ($len < 3) {
            return $s;
        }
        if (!isset($pinyins)) {
            $data = file_get_contents(BASE_PATH . '/app/Resource/pinyin.data');
            $a1 = explode('|', $data);
            $pinyins = array();
            foreach ($a1 as $v) {
                $a2 = explode(':', $v);
                $pinyins[$a2[0]] = $a2[1];
            }
        }
        $rs = '';
        for ($i = 0; $i < $len; $i++) {
            $o = ord($s[$i]);
            if ($o < 0x80) {
                if (($o >= 48 && $o <= 57) || ($o >= 97 && $o <= 122)) {
                    $rs .= $s[$i]; // 0-9 a-z
                } elseif ($o >= 65 && $o <= 90) {
                    $rs .= strtolower($s[$i]); // A-Z
                } else {
                    $rs .= '_';
                }
            } else {
                $z = $s[$i] . $s[++$i] . $s[++$i];
                if (isset($pinyins[$z])) {
                    $rs .= $isFirst ? $pinyins[$z][0] : $pinyins[$z];
                } else {
                    $rs .= '_';
                }
            }
        }
        return $rs;
    }


    /**
     * 阿拉伯数字转汉字
     * @param  $num
     * @param string $mode
     * @param string $sim
     * @return string
     */
    public static function numToCNMoney($num, $mode = true, $sim = true)
    {
        if (!is_numeric($num)) {
            return '含有非数字非小数点字符！';
        }

        $char = $sim ? array('零', '一', '二', '三', '四', '五', '六', '七', '八', '九')
            : array('零', '壹', '贰', '叁', '肆', '伍', '陆', '柒', '捌', '玖');
        $unit = $sim ? array('', '十', '百', '千', '', '万', '亿', '兆')
            : array('', '拾', '佰', '仟', '', '萬', '億', '兆');
        $retval = $mode ? '元' : '点';
        //小数部分
        if (strpos($num, '.')) {
            list($num, $dec) = explode('.', $num);
            $dec = strval(round($dec, 2));
            if ($mode) {
                $retval .= "{$char[$dec[0]]}角{$char[$dec[1]]}分";
            } else {
                for ($i = 0, $c = strlen($dec); $i < $c; $i++) {
                    $retval .= $char[$dec[$i]];
                }
            }
        }
        //整数部分
        $str = $mode ? strrev(intval($num)) : strrev($num);
        $out = array();
        for ($i = 0, $c = strlen($str); $i < $c; $i++) {
            $out[$i] = $char[$str[$i]];
            if ($mode) {
                $out[$i] .= $str[$i] != '0' ? $unit[$i % 4] : '';
                if ($i > 1 and $str[$i] + $str[$i - 1] == 0) {
                    $out[$i] = '';
                }
                if ($i % 4 == 0) {
                    $out[$i] .= $unit[4 + floor($i / 4)];
                }
            }
        }
        $retval = join('', array_reverse($out)) . $retval;
        return $retval;
    }


    /**
     * 获取星座
     * @param $month
     * @param $day
     * @return string
     */
    public static function getZodiacSign($month, $day)
    {
        // 检查参数有效性
        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return '';
        }

        // 星座名称以及开始日期
        $signs = array(
            array("20" => "宝瓶座"),
            array("19" => "双鱼座"),
            array("21" => "白羊座"),
            array("20" => "金牛座"),
            array("21" => "双子座"),
            array("22" => "巨蟹座"),
            array("23" => "狮子座"),
            array("23" => "处女座"),
            array("23" => "天秤座"),
            array("24" => "天蝎座"),
            array("22" => "射手座"),
            array("22" => "摩羯座"),
        );
        list($signStart, $signName) = each($signs[(int)$month - 1]);
        if ($day < $signStart) {
            list($signStart, $signName) = each($signs[($month - 2 < 0) ? $month = 11 : $month -= 2]);
        }
        return $signName;
    }

    /**
     * 计算时间差
     * @param $timestamp1
     * @param $timestamp2
     * @param bool $needLabel
     * @return array|string
     */
    public static function timeDiff($timestamp1, $timestamp2, $needLabel = false)
    {
        if ($timestamp2 <= $timestamp1) {
            if ($needLabel) {
                return '';
            }
            return ['hours' => 0, 'minutes' => 0, 'seconds' => 0];
        }
        $timediff = $timestamp2 - $timestamp1;
        // 时
        $remain = $timediff % 86400;
        $hours = intval($remain / 3600);

        // 分
        $remain = $timediff % 3600;
        $mins = intval($remain / 60);
        // 秒
        $secs = $remain % 60;

        $time = ['hours' => $hours, 'minutes' => $mins, 'seconds' => $secs];

        if ($needLabel) {
            $label = '';
            if ($time['hours'] > 0) {
                $label .= $time['hours'] . '小时';
            }
            if ($time['minutes'] > 0) {
                $label .= $time['minutes'] . '分钟';
            }
            if ($time['seconds'] > 0) {
                $label .= $time['seconds'] . '秒';
            }
            return $label;
        }
        return $time;
    }

    /**
     * 获取系统支持的后缀
     * @param $filename
     * @return mixed|string
     */
    public static function getFileExtName($filename)
    {
        $exts = array(
            '.m3u8', '.gif', '.mp4', '.jpg', '.jpeg', '.bmp', '.png', '.ts', '.txt', '.zip', '.webp'
        );
        foreach ($exts as $ext) {
            if (strpos($filename, $ext) > 0) {
                return $ext;
            }
        }
        return '';
    }


    /**
     * 数组分页
     * @param array $arr
     * @param int $page
     * @param int $pageSize
     * @return array|bool
     */
    public static function pageArray($arr = array(), $page = 1, $pageSize = 15)
    {
        $page = (int)$page;
        $pageSize = (int)$pageSize;
        if (empty($arr) || !$page || !$pageSize) {
            return false;
        }
        $end_index = count($arr);
        $start = ($page - 1) * $pageSize;
        $end = $start + $pageSize;
        if ($end > $end_index) {
            $end = $end_index;
        }
        if ($start < 0) {
            $start = 0;
        }
        $new_arr = [];
        for ($i = $start; $i < $end; $i++) {
            $new_arr[] = $arr[$i];
        }
        return $new_arr;
    }

    /**
     * 初始化一个curl
     * @param string $url
     * @param array $header
     * @param int $timeout
     * @return false|resource
     */
    public static function initCurl($url, $header = array(), $timeout = 40)
    {
        if (!function_exists("curl_init")) {
            die('undefined function curl_init');
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_CAINFO, BASE_PATH . 'app/Resource/cacert.pem');
        /**************測試環境先不驗證ssl準確性**************/
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        /**************測試環境先不驗證ssl準確性**************/
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.3; WOW64; rv:32.0) Gecko/20100101 Firefox/32.0');
        return $ch;

    }

    /**
     * 请求json数据
     * @param string $url
     * @param mixed $data
     * @param int $timeout
     * @param array $header
     * @return bool|string
     */
    public static function httpJson($url, $data, $timeout = 40, $header = array())
    {
        if (is_array($data) || is_object($data)) {
            $data = json_encode($data);
        }
        $header[] = 'Content-Type: application/json';
        $header[] = 'Content-Length: ' . strlen($data);
        $ch = self::initCurl($url, $header, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }


    /**
     * 请求json数据
     * @param string $url
     * @param mixed $data
     * @param int $timeout
     * @param array $header
     * @return bool|string
     */
    public static function httpRaw($url, $data, $timeout = 3, $header = array())
    {
        $header[] = 'Content-Type: Content-Type: application/octet-stream';
        $header[] = 'Content-Length: ' . strlen($data);
        $ch = self::initCurl($url, $header, $timeout);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }



    /**
     * http post
     * @param $url
     * @param $data
     * @param int $timeout
     * @param array $header
     * @return bool|string
     */
    public static function httpPost($url, $data, $timeout = 40, $header = array())
    {
        $ch = self::initCurl($url, $header, $timeout);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POSTREDIR, 3);
        $rs = curl_exec($ch);
        curl_close($ch);
        return $rs;
    }


    /**
     * http get
     * @param $url
     * @param int $timeout
     * @param array $header
     * @param  $referer
     * @return bool|string
     */
    public static function httpGet($url, $timeout = 40, $header = array(), $referer = "")
    {
        $ch = self::initCurl($url, $header, $timeout);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


    /**
     * @param $url
     * @param int $timeout
     * @param array $header
     * @param string $referer
     * @param string $proxy
     * @return bool|string
     */
    public static function httpGetProxy($url, $timeout = 40, $header = array(), $referer = "", $proxy = '')
    {
        $ch = self::initCurl($url, $header, $timeout);
        if ($referer) {
            curl_setopt($ch, CURLOPT_REFERER, $referer);
        }

        if ($proxy) {

            if (stripos($proxy, '|') !== false) {
                list($host, $auth) = explode('|', $proxy);

                curl_setopt($ch, CURLOPT_PROXYUSERPWD, $auth);

            } else {
                $host = $proxy;
            }

            curl_setopt($ch,CURLOPT_PROXYTYPE,CURLPROXY_SOCKS5);//使用了SOCKS5代理
            curl_setopt($ch, CURLOPT_PROXY, $host);

        }


        $data = curl_exec($ch);
        curl_close($ch);
        return $data;
    }


    /**
     * 格式化秒
     * @param $times
     * @return float|int
     */
    public static function formatSecond($times)
    {
        $result = '00:00:00';
        if ($times > 0) {
            $hour = floor($times / 3600);
            $minute = floor(($times - 3600 * $hour) / 60);
            $second = floor((($times - 3600 * $hour) - 60 * $minute) % 60);
            $hour = strlen('' . $second) == 1 ? '0' . $hour : $hour;
            $minute = strlen('' . $minute) == 1 ? '0' . $minute : $minute;
            $second = strlen('' . $second) == 1 ? '0' . $second : $second;
            $result = $hour . ':' . $minute . ':' . $second;
        }
        return $result;
    }

    /**
     * 探测网络文件是否存在
     * @param $url
     * @return bool
     */
    public static function checkRemoteFileExists($url)
    {
        $curl = curl_init($url); // 不取回数据
        curl_setopt($curl, CURLOPT_NOBODY, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'GET'); // 发送请求
        $result = curl_exec($curl);
        $found = false; // 如果请求没有发送失败
        if ($result !== false) {
            /** 再检查http响应码是否为200 */
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if ($statusCode == 200) {
                $found = true;
            }
        }
        curl_close($curl);
        return $found;
    }

    /**
     * @param $time1
     * @param null $time2
     * @return string
     * @desc 计算两个时间到时间差，社会化显示
     */
    public static function showTimeDiff($time1, $time2 = null)
    {
        if (empty($time1) && empty($time2)) {
            return '';
        }

        $time2 = !$time2 ? time() : $time2;

        $timeDiff = $time2 - $time1;

        if ($timeDiff >= 172800) {
            //两天前
            return date('m-d H:i', $time1 *1);
        } else if ($timeDiff >= 86400) {
            //昨天
            $todayStart = self::getTodayZeroTime() - 86400;
            if ($time1 >= $todayStart) {
                return '昨日 ' . date('H:i', $time1 *1);
            } else {
                return '前日 ' . date('H:i', $time1 *1);
            }
        } else if ($timeDiff >= 43200) {
            // 超过半天(但可能涉及今日0点)
            $todayStart = self::getTodayZeroTime();
            if ($time1 >= $todayStart) {
                return '今天 ' . date('H:i', $time1*1);
            } else {
                return '昨日 ' . date('H:i', $time1 *1);
            }
        } else if ($timeDiff >= 3600) {
            $str = '';
            $hours = floor($timeDiff / 3600);
            if ($hours > 0) {
                $str .= $hours . '小时 ';
            }
            $str .= '前';
            return $str;
        } else if ($timeDiff >= 60) {
            $hours = ceil($timeDiff / 60);
            return $hours . '分钟前';
        } else {
            return '刚刚';
        }
    }

    /**
     * 格式化Email
     * @param  $mail
     * @param  $label
     * @return string
     */
    public static function formatEmail($mail, $label = '*')
    {
        $emailInfo = explode('@', $mail);
        $emailName = $emailInfo[0];
        $result = '';
        for ($i = 0; $i < strlen($emailName); $i++) {
            if ($i >= 2 && $i <= 6) {
                $result .= $label;
            } else {
                $result .= substr($emailName, $i, 1);
            }
        }
        $emailInfo[0] = $result;
        return join('@', $emailInfo);
    }

    /**
     * 格式化手机号码
     * @param  $phone
     * @param  $label
     * @return string
     */
    public static function formatPhone($phone, $label = '*')
    {
        $phone = trim(strval($phone));
        $result = '';
        for ($i = 0; $i < strlen($phone); $i++) {
            if ($i > 2 && $i < 7) {
                $result .= $label;
            } else {
                $result .= substr($phone, $i, 1);
            }
        }
        return $result;
    }

    /**
     * @param int $times
     * @return  mixed
     */
    public static function parseSecond($times)
    {
        $result = '00:00:00';
        if ($times > 0) {
            $hour = floor($times / 3600);
            $hour = $hour >= 10 ? $hour : '0' . $hour;
            $minute = floor(($times - 3600 * $hour) / 60);
            $minute = $minute >= 10 ? $minute : '0' . $minute;
            $second = floor((($times - 3600 * $hour) - 60 * $minute) % 60);
            $second = $second >= 10 ? $second : '0' . $second;
            $result = $hour . ':' . $minute . ':' . $second;
        }
        return $result;
    }

    /**
     * 获取今天零点时间
     * @return number
     */
    public static function getTodayZeroTime()
    {
        $time = date('Y-m-d 00:00:00');
        return strtotime($time);
    }

    /**
     * 获取今天24点59分59秒的时间
     * @return number
     */
    public static function getTodayEndTime()
    {
        $time = date('Y-m-d 23:59:59');
        return strtotime($time);
    }

    /**
     * 获取周一
     * @return false|int
     */
    public static function getWeekFirst()
    {
        return strtotime(date('Y-m-d 00:00:00', (time() - ((date('w') == 0 ? 7 : date('w')) - 1) * 24 * 3600)));
    }

    /**
     * 获取周日
     * @return false|int
     */
    public static function getWeekEnd()
    {
        return strtotime(date('Y-m-d 00:00:00', (time() + (7 - (date('w') == 0 ? 7 : date('w'))) * 24 * 3600)));
    }

    /**
     * 获取本月第一天
     * @return false|int
     */
    public static function getMonthStart()
    {
        return strtotime(date('Y-m', time()) . '-01 00:00:00');
    }

    /**
     * 获取本月最后一天
     * @return int
     */
    public static function getMonthEnd()
    {
        return strtotime(date('Y-m', time()) . '-' . date('t', time()) . ' 00:00:00');
    }


    /**
     * 获取是星期几
     * @param null|int $timestamp
     * @param bool $onlyDay
     * @return int|mixed|null
     */
    public static function getWeek($timestamp = null, $onlyDay = true)
    {
        $timestamp = $timestamp ?: time();
        $week = date('w', $timestamp);

        $weeks = array(
            '0' => array(
                'day' => 7,
                'name' => '周日'
            ),
            '1' => array(
                'day' => 1,
                'name' => '周一'
            ),
            '2' => array(
                'day' => 2,
                'name' => '周二'
            ),
            '3' => array(
                'day' => 3,
                'name' => '周三'
            ),
            '4' => array(
                'day' => 4,
                'name' => '周四'
            ),
            '5' => array(
                'day' => 5,
                'name' => '周五'
            ),
            '6' => array(
                'day' => 6,
                'name' => '周六'
            ),
        );

        $row = $weeks[$week] ?? null;

        if ($onlyDay) {
            return $row ? $row['day'] : 0;
        }

        return $row;
    }

    /**
     * 全角转换半角
     * @param $str
     * @return string
     */
    public static function makeSemiangle($str)
    {
        $arr = array('０' => '0', '１' => '1', '２' => '2', '３' => '3', '４' => '4',
            '５' => '5', '６' => '6', '７' => '7', '８' => '8', '９' => '9',
            'Ａ' => 'A', 'Ｂ' => 'B', 'Ｃ' => 'C', 'Ｄ' => 'D', 'Ｅ' => 'E',
            'Ｆ' => 'F', 'Ｇ' => 'G', 'Ｈ' => 'H', 'Ｉ' => 'I', 'Ｊ' => 'J',
            'Ｋ' => 'K', 'Ｌ' => 'L', 'Ｍ' => 'M', 'Ｎ' => 'N', 'Ｏ' => 'O',
            'Ｐ' => 'P', 'Ｑ' => 'Q', 'Ｒ' => 'R', 'Ｓ' => 'S', 'Ｔ' => 'T',
            'Ｕ' => 'U', 'Ｖ' => 'V', 'Ｗ' => 'W', 'Ｘ' => 'X', 'Ｙ' => 'Y',
            'Ｚ' => 'Z', 'ａ' => 'a', 'ｂ' => 'b', 'ｃ' => 'c', 'ｄ' => 'd',
            'ｅ' => 'e', 'ｆ' => 'f', 'ｇ' => 'g', 'ｈ' => 'h', 'ｉ' => 'i',
            'ｊ' => 'j', 'ｋ' => 'k', 'ｌ' => 'l', 'ｍ' => 'm', 'ｎ' => 'n',
            'ｏ' => 'o', 'ｐ' => 'p', 'ｑ' => 'q', 'ｒ' => 'r', 'ｓ' => 's',
            'ｔ' => 't', 'ｕ' => 'u', 'ｖ' => 'v', 'ｗ' => 'w', 'ｘ' => 'x',
            'ｙ' => 'y', 'ｚ' => 'z',
            '（' => '(', '）' => ')', '〔' => '[', '〕' => ']', '【' => '[',
            '】' => ']', '〖' => '[', '〗' => ']', '“' => '[', '”' => ']',
            '‘' => '[', '’' => ']', '｛' => '{', '｝' => '}', '《' => '<',
            '》' => '>', '■' => '.',
            '％' => '%', '＋' => '+', '—' => '-', '－' => '-', '～' => '-',
            '：' => ':', '。' => '.', '、' => ',', '，' => '.', '、' => '.',
            '；' => ',', '？' => '?', '！' => '!', '…' => '-', '‖' => '|',
            '”' => '"', '’' => '`', '‘' => '`', '｜' => '|', '〃' => '"',
            '　' => ' ', '＄' => '$', '＠' => '@', '＃' => '#', '＾' => '^', '＆' => '&', '＊' => '*',
            '＂' => '"');

        return strtr($str, $arr);
    }


    /**
     * 检测关键字
     * @param $content
     * @return bool
     */
    public static function checkKeywords($content)
    {
        $content = CommonUtil::makeSemiangle($content);
        $content = preg_replace('/ /iU', '', $content);
        $checkArr = array(
            '.me', '.com', '.top', '.info', '.cn', '.net', '.://', '.xyz', '.vip', '.org', '.edu', '.tv', '.uk', '.jp','.club','.cc', '.porn', '.app', '.live', '.hk', '.site',
            '人兽','人妻','幼女', '幼钕', '御姐','乖乖水','药物','約炮','包','企鹅','微信','vx','抠','筘','扣','捃','加','联系','破解','聊','私',
            '约炮','裙',"Q",'q','管方','交友','肏茓','网址','约-炮','群','桾','峮', '百分百','同城', '约炮', '约泡','泡', '佰芬佰',
            '全国约炮','騒女','极品','粉嫩','骚穴','Ｑ','箹','帝王','服务','电话调情','骚女','陪約','快来玩',
            '㈠','㈡','㈢','㈣','㈤','㈥','㈦','㈧','㈨','ⓠ',
            '❶','❷','❸','❹','❺','❻','❼','❽','❾',
            '①','②','③','④','⑤','⑥','⑦','⑧','⑨',
            '（一）','（二）','（三）','（四）','（五）','（六）','（七）','（八）','（九）',
            '壹','贰','叁','肆','伍','陆','柒','捌','玖','零',
            '¹','²','³','⁴','⁵','⁶','⁷','⁸','⁹','⁰',
            '🐧',
        );
        foreach ($checkArr as $check) {
            if (strpos($content, $check) !== false) {
                return false;
            }
        }
        return true;
    }

    /**
     * 蜘蛛
     * @return bool
     */
    public static function isCrawler()
    {
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if (!empty($agent)) {
            $spiderSite = array(
                "TencentTraveler",
                "Baiduspider+",
                "BaiduGame",
                "Googlebot",
                "msnbot",
                "Sosospider+",
                "Sogou web spider",
                "ia_archiver",
                "Yahoo! Slurp",
                "YoudaoBot",
                "Yahoo Slurp",
                "MSNBot",
                "Java (Often spam bot)",
                "BaiDuSpider",
                "Voila",
                "Yandex bot",
                "BSpider",
                "twiceler",
                "Sogou Spider",
                "Speedy Spider",
                "Google AdSense",
                "Heritrix",
                "Python-urllib",
                "Alexa (IA Archiver)",
                "Ask",
                "Exabot",
                "Custo",
                "OutfoxBot/YodaoBot",
                "yacy",
                "SurveyBot",
                "legs",
                "lwp-trivial",
                "Nutch",
                "StackRambler",
                "The web archive (IA Archiver)",
                "Perl tool",
                "MJ12bot",
                "Netcraft",
                "MSIECrawler",
                "WGet tools",
                "larbin",
                "Fish search",
            );
            foreach ($spiderSite as $val) {
                $str = strtolower($val);
                if (strpos($agent, $str) !== false) {
                    return true;
                }
            }
        } else {
            return false;
        }
    }

    public static function getId($isShort=false)
    {
        $id= md5(microtime(true) . mt_rand(1000, 9000));
        if($isShort){
            $id= substr($id,8,16);
        }
        return $id;
    }

    /**
     * 获取渠道
     * @param $channel
     * @return string
     */
    public static function getChannel($channel)
    {
        if(empty($channel)){
            return "";
        }
        $channel = trim(str_replace('sign=xxx','',$channel),'&');
        if(strpos($channel,'channel://')!==false){
            $channel = str_replace('channel://','',$channel);
        }
        return trim($channel);
    }

    /**
     * 获取上级
     * @param $share
     * @return string
     */
    public static function getParent($share)
    {
        if(empty($share)){
            return "";
        }
        $share = trim(str_replace('sign=xxx','',$share),'&');
        if(strpos($share,'share://')!==false){
            $share = str_replace('share://','',$share);
        }
        return trim($share);
    }

    /**
     * 获取媒体文件存储位置
     * @return string
     */
    public static function getPublicRuntimePath()
    {
        return WEB_PATH . '/runtime';
    }

    /**
     * 数组分组
     * @param $arr
     * @param $key
     * @return array
     */
    public static function arrayGroup($arr, $key){
        $grouped = array();
        foreach ($arr as $value) {
            $grouped[$value[$key]][] = $value;
        }
        if (func_num_args() > 2) {
            $args = func_get_args();
            foreach ($grouped as $key => $value) {
                $parms = array_merge($value, array_slice($args, 2, func_num_args()));
                $grouped[$key] = call_user_func_array('arrayGroup', $parms);
            }
        }
        return $grouped;
    }

    /**
     *  格式换行
     * @param $lines
     * @return array
     */
    public  static function formatBrToArr($lines)
    {
        $values =[];
        $lines = explode("\n",$lines);
        foreach ($lines  as $line)
        {
            $line = trim($line);
            if($line){
                $values[]=$line;
            }
        }
        return $values;
    }

    /**
     * id分表
     * @param int $id
     * @param int $num
     * @return int
     */
    public static function getIdTable(int $id,int $num) {
//        $str = crc32($id);
//        if($str<0){
//            $hash = "0".substr(abs($str), 0, 1);
//        }else{
//            $hash = substr($str, 0, 2);
//        }
        return $id%$num;
    }

    /**
     *      * array_splice函数是删除数组中的一部分并返回
     * 这里用它来截取要插入的元素的前面的元素，这样就可以获得要插入元素前面的数组了
     * 这时候$arr也被截取为两部分，前半部分是待插入元素前面的数组，后半部分是待插入元素后面的数组
     * 然后利用array_merge就可以实现在任意指定位置插入元素了
     * @param $arr
     * @param $position
     * @param $element
     * @return array
     */
    public static  function insertToArray($arr, $position, $element)
    {
        $first_array = array_splice($arr, 0, $position);
        $result = array_merge($first_array, $element, $arr);
        return $result;
    }

    /**
     * 获取是http还是https
     * @return string
     */
    public static function getServerSchema(){
        if ( (!empty($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https') || (! empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (! empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443') ) {
            return 'https';
        }
        return 'http';
    }

    /**
     *  转化区间用户id  ,表示多个 -表示起止
     * @param $userIds
     * @return array
     */
    public  static function parseUserIds($userIds)
    {
        if(empty($userIds)){
            return [];
        }
        $ids = [];
        if(strpos($userIds,',')){
            $ids = explode(',',$userIds);
        }elseif (strpos($userIds,'-')){
            $userIds = explode('-',$userIds);
            for ($i=$userIds[0];$i<=$userIds[1];$i++){
                $ids[]= $i;
            }
        }else{
            $ids[]=$userIds;
        }
        foreach ($ids as $index=>$id){
            if(intval($id)>0){
                $ids[$index]=intval($id);
            }
        }
        return array_values($ids);
    }

    /**
     * 获取每行的分割符号
     * @param $content
     * @return string
     */
    public static function getSplitChar($content)
    {
        $split = "\n";
        if (strpos($content, "\r\n") > 0) {
            $split = "\r\n";
        };
        return $split;
    }
}
