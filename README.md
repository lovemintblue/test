# mrs
暗网
添加系统用户
php shell.php tools addAdminUser 用户名 密码 角色id  
如果复制其他的谷歌code
php shell.php tools addAdminUser 用户名 密码 角色id   谷歌密钥

绑定谷歌
获取谷歌二维码
php shell.php tools bindGoogleCode  用户名  
绑定谷歌验证
php shell.php tools bindGoogleCode  用户名   谷歌验证码

添加系统ip白名单
php shell.php  tools adminIp ad[composer.json](composer.json)d ip

删除系统ip白名单
php shell.php  tools adminIp del ip


定时任务


*/1 * * * *  /bin/sh      /var/www/html/manhua-php/bin/start.sh>/dev/null&
*/15 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab reportMms >/dev/null&
*/15 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab reportServer >/dev/null&
*/15 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab reportServerHour>/dev/null&
*/10 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab reportAgentV2>/dev/null&
*/10 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab reportAgentV3>/dev/null&
*/1 * * * *  /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab doPaid >/dev/null&

0 */3 * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab scoreComics >/dev/null&
8 */5 * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab scoreMovie >/dev/null&
*/50 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab tagCount >/dev/null&

* 11 * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncComics>/dev/null&
*/10 * * * *    /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncAi>/dev/null&
00 17 * * *     /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncMovie>/dev/null&
* 16 * * *      /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncPost>/dev/null&
*/3 * * * *     /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncApk>/dev/null&
*/30 * * * *    /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncDomain>/dev/null&
*/3 * * * *     /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab asyncAdvApp>/dev/null&
4 0 * * *    /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab adminLogs >/dev/null&
30 0 * * *    /usr/bin/php /var/www/html/manhua-php/bin/shell.php crontab sendMsgToGroup >/dev/null&

10 21 * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php mrs updateComics>/dev/null&
* 10 * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php mrs updateComics>/dev/null&
5 09 * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php mrs updateMovie>/dev/null&

*/30 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php jms user>/dev/null&
*/30 * * * * /usr/bin/php /var/www/html/manhua-php/bin/shell.php jms day>/dev/null&