#!/bin/sh
#######公共进程########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php crontab queue" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr crontab/queue  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php crontab queue>/dev/null&
echo "crontab/queue  has started!"
fi
#######任务进程########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php job low" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr job/low  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php job low>/dev/null&
echo "job/low  has started!"
fi
#######数据跟踪########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php crontab doTrackQueue" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr crontab/doTrackQueue  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php crontab doTrackQueue>/dev/null&
echo "crontab/doTrackQueue  has started!"
fi
#######用户行为########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php crontab doActQueue" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr crontab/doActQueue  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php crontab doActQueue>/dev/null&
echo "crontab/doActQueue  has started!"
fi
#######AI进程########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php crontab asyncAi" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr crontab/asyncAi  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php crontab asyncAi>/dev/null&
echo "crontab/asyncAi  has started!"
fi
#######ai/游戏下分########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php crontab balanceTransfer" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr crontab/balanceTransfer  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php crontab balanceTransfer>/dev/null&
echo "crontab/balanceTransfer  has started!"
fi
#######数据中心########################
stillRunning=$(/bin/ps -ef |/bin/grep "/manhua_douman_php/bin/shell.php center data" |/bin/grep -v "grep")
if [ ! "$stillRunning" ] ; then
dateStr=$(date +"%Y-%m-%d %H:%M:%S")
echo "$dateStr center/data  not started"
/usr/bin/php  /var/www/html/manhua_douman_php/bin/shell.php center data>/dev/null&
echo "center/data  has started!"
fi