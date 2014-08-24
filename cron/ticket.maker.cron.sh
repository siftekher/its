#!/bin/sh

START=`date "+%m/%d/%y %H.%I.%S"`
ROOT_DIR=/data/web/cron
LOG_DIR=$ROOT_DIR/logs/
LOG_FILE=$LOG_DIR/ticket.maker.cron.log
LOCK_FILE=$LOG_DIR/ticket.maker.cron.lck
SCRIPT_NAME=ticket.maker.cron.php

PHP=/usr/local/bin/php

if [ -e $LOCK_FILE ]; then
   echo "Lock file $LOCK_FILE exists. Aborted!";
   exit;
fi

echo -n "$$, $START," >> $LOG_FILE;

touch $LOCK_FILE;

cd $ROOT_DIR

echo "Running ticket maker cron for ITS";
$PHP $SCRIPT_NAME 
#>> $LOG_FILE

STOP=`date "+%m/%d/%y %H.%I.%S"`

echo ",$STOP" >> $LOG_FILE;

rm -f $LOCK_FILE;

