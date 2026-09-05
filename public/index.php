<?php

declare(strict_types=1);

require __DIR__ . '/../src/db.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if ($path === '/healthz') {
    header('Content-Type: text/plain; charset=utf-8');
    echo 'ok';
    return;
}

function redirect_home(): void
{
    header('Location: /', true, 303);
}

function e(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

try {
    $pdo = db_from_env();

    if ($method === 'POST' && $path === '/todos') {
        $title = trim(is_string($_POST['title'] ?? null) ? $_POST['title'] : '');
        if ($title !== '') {
            $stmt = $pdo->prepare('INSERT INTO todos (title) VALUES (?)');
            $stmt->execute([mb_substr($title, 0, 200)]);
        }
        redirect_home();
        return;
    }

    if ($method === 'POST' && $path === '/todos/delete') {
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if ($id !== false && $id !== null) {
            $stmt = $pdo->prepare('DELETE FROM todos WHERE id = ?');
            $stmt->execute([$id]);
        }
        redirect_home();
        return;
    }

    if ($method !== 'GET' || $path !== '/') {
        http_response_code(404);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'not found';
        return;
    }

    $todos = $pdo->query('SELECT id, title FROM todos ORDER BY id DESC')->fetchAll();
} catch (Throwable $error) {
    error_log((string) $error);
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'internal server error';
    return;
}

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PHP Todo | AppHaven</title>
<style>
  * { box-sizing: border-box; }
  input[type=text] { min-width: 0; }
  li span { min-width: 0; overflow-wrap: anywhere; }
  li form { flex-shrink: 0; }
  footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #8884; font-size: .875rem; }
  a { color: #2457bd; }
  :focus-visible { outline: 2px solid #2457bd; outline-offset: 3px; }

  :root { color-scheme: light; }
  body {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
    margin: 0;
    padding: 3rem 1rem;
    line-height: 1.5;
  }
  main { max-width: 640px; margin: 0 auto; }
  h1 { font-size: 1.5rem; margin: 0 0 1.5rem; }
  form.add { display: flex; gap: .5rem; margin-bottom: 1.5rem; }
  input[type=text] {
    flex: 1;
    padding: .55rem .7rem;
    font: inherit;
    border: 1px solid #9a9a9a;
    border-radius: 6px;
    background: transparent;
    color: inherit;
  }
  button {
    padding: .55rem .9rem;
    font: inherit;
    border: 1px solid #9a9a9a;
    border-radius: 6px;
    background: transparent;
    color: inherit;
    cursor: pointer;
  }
  ul { list-style: none; margin: 0; padding: 0; }
  li {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: .7rem 0;
    border-top: 1px solid rgba(128, 128, 128, .35);
  }
  li:last-child { border-bottom: 1px solid rgba(128, 128, 128, .35); }
  li form { margin: 0; }
  li button { padding: .25rem .6rem; font-size: .85rem; }
  p.empty { color: #6b6b6b; }
</style>
</head>
<body>
<main>
  <h1>PHP Todo</h1><p>A shared task list, built with PHP and PostgreSQL.</p>

  <form class="add" method="post" action="/todos">
    <input type="text" name="title" aria-label="New task" placeholder="What needs doing?" maxlength="200" required autofocus>
    <button type="submit">Add</button>
  </form>

<?php if ($todos === []) : ?>
  <p class="empty">No items yet.</p>
<?php else : ?>
  <ul>
<?php foreach ($todos as $todo) : ?>
    <li>
      <span><?= e((string) $todo['title']) ?></span>
      <form method="post" action="/todos/delete">
        <input type="hidden" name="id" value="<?= e((string) $todo['id']) ?>">
        <button type="submit">Delete</button>
      </form>
    </li>
<?php endforeach; ?>
  </ul>
<?php endif; ?>
<footer><p>Deploy your own on <a href="https://apphaven.eu">AppHaven</a> · <a href="https://github.com/apphaven-eu/example-php">Source code</a> · <a href="https://docs.apphaven.eu/getting-started">Deployment guide</a></p></footer></main>
</body>
</html>
