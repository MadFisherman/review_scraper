#!/bin/bash

php -f /var/www/reviewscraper/reviews/Daemons/daemon_parser.php &
php -f /var/www/reviewscraper/reviews/Daemons/daemon_asintool.php &
php -f /var/www/reviewscraper/reviews/Daemons/daemon_translator_check.php &
php -f /var/www/reviewscraper/reviews/Daemons/daemon_translator_create.php &
