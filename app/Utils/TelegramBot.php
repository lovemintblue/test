<?php


namespace App\Utils;


class TelegramBot
{
    /**
     * token
     * @var string
     */
    private static $bot_token = '1953885500:AAGjbG_dqa2EOS4RZOJGes0pMr8Cl7YX2Es';
    private static $bot_chat_id = '-476787557';

    /**
     * 获取更新列表
     * https://api.telegram.org/bot1953885500:AAGjbG_dqa2EOS4RZOJGes0pMr8Cl7YX2Es/getUpdates
     */
    public static function getUpdates()
    {
        $url = "https://api.telegram.org/bot".self::$bot_token."/getUpdates";
        $result = CommonUtil::httpPost($url, []);
        $result = json_decode($result,true);
        return $result;
    }

    /**
     * 发送消息
     * @param $text
     * @param $chatId
     * @param $botToken
     * @return bool
     */
    public static function sendMsg($text, $chatId='', $botToken='')
    {
        $botToken = $botToken?:self::$bot_token;
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        $data = [
            'text'=>$text,
            'chat_id'=>$chatId?:self::$bot_chat_id,
            'parse_mode'=>'HTML',
        ];
        $result = CommonUtil::httpPost($url, $data);
        $result = json_decode($result,true);
        if($result['error_code']){
//            LogsUtil::console($result['description']);
            return false;
        }
        return true;
    }

}