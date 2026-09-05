<?php

declare(strict_types=1);

// Create the table if it does not exist, then replace this process with Apache
// so the web server is PID 1 and receives SIGTERM directly.
require __DIR__ . '/migrate.php';

pcntl_exec('/usr/local/bin/apache2-foreground');

fwrite(STDERR, "failed to start Apache\n");
exit(1);
