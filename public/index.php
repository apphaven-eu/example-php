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

    if ($method === 'POST' && $path === '/add') {
        $title = trim(is_string($_POST['title'] ?? null) ? $_POST['title'] : '');
        if ($title !== '') {
            $stmt = $pdo->prepare('INSERT INTO todos (title) VALUES (?)');
            $stmt->execute([mb_substr($title, 0, 200)]);
        }
        redirect_home();
        return;
    }

    if ($method === 'POST' && $path === '/delete') {
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

    $todos = $pdo->query('SELECT id, title FROM todos ORDER BY created_at DESC, id DESC')->fetchAll();
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
<link rel="stylesheet" href="/style.css">
</head>
<body>
<main>
  <h1>PHP Todo</h1><p>A shared task list, built with PHP and PostgreSQL.</p>

  <form class="add" method="post" action="/add">
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
      <form method="post" action="/delete">
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
