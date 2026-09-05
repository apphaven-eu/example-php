<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/db.php';

$attempt = 0;
while (true) {
    try {
        db_migrate(db_from_env());
        break;
    } catch (PDOException $error) {
        if (++$attempt >= 30) {
            throw $error;
        }
        fwrite(STDERR, "waiting for the database: {$error->getMessage()}\n");
        sleep(1);
    }
}
