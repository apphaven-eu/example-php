#!/bin/sh
set -e

# Create the table if it does not exist, then hand over to Apache with exec so the
# web server is PID 1 and receives SIGTERM directly.
php /app/bin/migrate.php
exec apache2-foreground
