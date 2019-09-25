#!/bin/bash

php -f /home/bender/Daemons/daemon_parser.php &
php -f /home/bender/Daemons/daemon_asintool.php &
php -f /home/bender/Daemons/daemon_translator_check.php &
php -f /home/bender/Daemons/daemon_translator_create.php &
