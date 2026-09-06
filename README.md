# PHP with PostgreSQL on AppHaven

Deploy a PHP application with managed PostgreSQL on [AppHaven](https://apphaven.eu). This example uses PDO, prepared statements, and Apache to serve a todo list without a framework or Composer dependencies.

Add, list, and delete tasks. Data persists across app restarts in PostgreSQL.
The repository includes the application, a `Dockerfile`, and an `apphaven.yaml` manifest.

## Stack

- PHP 8.4, a single `public/index.php` front controller, no framework
- PDO with the `pdo_pgsql` driver and prepared statements
- PostgreSQL 17
- Container base `php:8.4-apache`, with Apache serving `public/` on the port from `PORT`

`DATABASE_URL` arrives as a `postgres://user:pass@host:port/dbname` URL. PDO needs a DSN, so
`src/db.php` splits the URL with `parse_url`, decodes the user and password (the generated password
is percent encoded), and builds a `pgsql:host=...;port=...;dbname=...` DSN.

## Run it locally

You need Docker for PostgreSQL and PHP 8.4 with `pdo_pgsql` and `mbstring`. Clone this repository first:

```sh
git clone https://github.com/apphaven-eu/example-php.git
cd example-php
```

1. Start PostgreSQL:

   ```
   docker run -d --name todo-pg -p 127.0.0.1:5432:5432 -e POSTGRES_PASSWORD=devpass postgres:17
   ```

2. Point the application at it:

   ```
   export DATABASE_URL=postgres://postgres:devpass@127.0.0.1:5432/postgres
   ```

3. Create the `todos` table:

   ```
   php bin/migrate.php
   ```

4. Start the PHP built-in server for development:

   ```
   php -S 127.0.0.1:8080 -t public public/index.php
   ```

5. Open http://localhost:8080/. The health endpoint is http://localhost:8080/healthz.

Your local PHP needs the `pdo_pgsql` extension. Check with `php -m | grep pdo_pgsql`. In the
container the entry point is `bin/start.sh`, which runs the same migration and then execs
Apache.

## Deploy PHP on AppHaven

1. Fork this repository, or push a copy to a Git host reachable over HTTPS.
2. Open the [AppHaven console](https://console.apphaven.eu/), select a project, and create an app.
3. In **Source**, connect your repository and select the production branch (usually `main`).
4. Click **Deploy** and select that branch. Follow the build logs, then open the deployment URL.

You need an AppHaven account with console access. See the
[getting started guide](https://docs.apphaven.eu/getting-started) for account and repository setup.

`apphaven.yaml` declares the `web` service (built from the `Dockerfile`) and the managed `db`
service running PostgreSQL 17. `DATABASE_URL` is injected at deploy time from `${service.db.url}`,
so production database credentials stay out of source control.

### Access and shared data

Apps are **private by default**: only members of the AppHaven project can open them.
This example has one shared todo list; it does not separate tasks by user.
For a public demo, a project administrator can select **Public** in the app's **Security**
section and redeploy. Anyone who can reach the app can add and delete tasks, so use demo data.
Production can be public while previews remain private. See [access control](https://docs.apphaven.eu/access).

### Preview a change

Push a new branch and deploy it from the console. AppHaven creates a preview with its own URL,
storage, and database, separate from production. Add a task in the preview, redeploy that branch,
and check that the task is still there before merging the change.

### Verify the deployment

Open the app, add a task, refresh, and delete it. The manifest waits for PostgreSQL to be healthy
before starting the web container. `/healthz` is a process liveness endpoint; it does not query
the database. The container's healthcheck runs internally, so it needs no public-path exemption.

The schema uses `CREATE TABLE IF NOT EXISTS` for the initial table. When extending the app,
use versioned migrations for changes to existing columns and tables.

## AppHaven

AppHaven provides the runtime for this example and the PostgreSQL database it writes to. The
database is declared in `apphaven.yaml`, and its connection URL reaches the container as
`DATABASE_URL` when the deployment starts.

- Platform: https://apphaven.eu
- Managed PostgreSQL: https://docs.apphaven.eu/services/postgres
- Manifest reference: https://docs.apphaven.eu/reference/manifest

## Related examples

[Go](https://github.com/apphaven-eu/example-go), [Spring Boot](https://github.com/apphaven-eu/example-java), [Next.js](https://github.com/apphaven-eu/example-nextjs), [Express](https://github.com/apphaven-eu/example-node), [FastAPI](https://github.com/apphaven-eu/example-python).

## License

[MIT](LICENSE).
