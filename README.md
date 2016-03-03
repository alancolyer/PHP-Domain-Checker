#PHP Domain Checker

A simple PHP script that requests a website using cURL and attempts to determine if it is working or not.
According to this script, working websites return a non-empty string and a 2XX or 3XX HTTP response code.

Anything else, including timeouts, empty responses, 404s, 500s, etc, are classed as failures.

This is built with the intention of being run via cron, and depending on the timeout settings and number of websites to check, can be run as often as once per minute.

No special requirements, just PHP with cURL support & a properly configured mail server so that the notification emails will be sent.
It will log failures if PHP can write to the directory it is run from.


Example cron command:
* * * * * php /domain-checker.php #once per hour
