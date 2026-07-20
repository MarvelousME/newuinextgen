# Production cron (WP-Cron replacement)

WordPress pseudo-cron is request-driven and **will miss** session reminders and payout batches on low-traffic hosts.

## Required system cron

Add to the server crontab (adjust path and URL):

```bash
*/5 * * * * cd /var/www/html && php wp-cli.phar cron event run --due-now --allow-root >> /var/log/ngc-cron.log 2>&1
```

Or without WP-CLI:

```bash
*/5 * * * * curl -s https://your-domain.example/wp-cron.php?doing_wp_cron >/dev/null 2>&1
```

## Manual ops (Docker local)

```bash
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar cron event run --due-now --allow-root
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar ngc process_reminders --allow-root
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar ngc run_payout_batch --allow-root
```

## Verify after deploy

```bash
wp ngc verify
wp ngc integrate_status
```

Companion plugin: run `NGC_Database::create_tables()` via plugin re-activation or `wp eval 'NGC_Database::create_tables();'` after upgrades.

## E2E smoke (Docker)

```bash
docker exec nextgentutors-wordpress-1 php /var/www/html/wp-cli.phar eval-file \
  /var/www/html/wp-content/plugins/NextGenTutors-Companion/scripts/e2e-docker.php --allow-root
```
