#!/bin/bash

# This script should be run on cron
# If there are no live dipper:work processes, it will start a new one.

N_PROCS=$(ps -ef | grep "dipper:work" | grep -v grep | wc -l)
DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"

if [ $N_PROCS -lt 1 ]; then
    $DIR/console dipper:work --no-throbber >> $DIR/../var/logs/dipper.log&
fi
