#!/bin/bash
curl -s -o /dev/null -w "STATIC=%{http_code} TIME=%{time_total}\n" --max-time 5 http://127.0.0.1/wp-includes/js/jquery/jquery.min.js
curl -s -o /dev/null -w "ABOUT=%{http_code} TIME=%{time_total}\n" --max-time 45 http://127.0.0.1/about/
curl -s -o /dev/null -w "HOME=%{http_code} TIME=%{time_total}\n" --max-time 45 http://127.0.0.1/
curl -s -o /dev/null -w "LOGIN=%{http_code} TIME=%{time_total}\n" --max-time 45 http://127.0.0.1/wp-login.php
