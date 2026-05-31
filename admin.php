<?php
require_once 'db.php';
if (!isset($_SERVER['PHP_AUTH_USER'])) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit();
}
$stmt = $db->prepare("SELECT * FROM admins WHERE login = ?");
$stmt->execute([$_SERVER['PHP_AUTH_USER']]);
$admin = $stmt->fetch(PDO::FETCH_ASSOC);
if (
    !$admin ||
    !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])
) {
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    header('HTTP/1.0 401 Unauthorized');
    exit();
}
$stmt = $db->query("
    SELECT *
    FROM applications
    ORDER BY id DESC
");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt = $db->query("
    SELECT
        pl.name AS name,
        COUNT(al.application_id) AS total
    FROM programming_languages pl
    LEFT JOIN application_languages al
        ON pl.id = al.language_id
    GROUP BY pl.id, pl.name
    ORDER BY total DESC
");
$stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Админ-панель</title>
</head>
<body>
<h1>Админ-панель</h1>
<h2>Все пользователи</h2>
<table border="1" cellpadding="5">
<tr>
    <th>ID</th>
    <th>ФИО</th>
    <th>Телефон</th>
    <th>Email</th>
    <th>Действия</th>
</tr>
<?php foreach ($users as $user): ?>
<tr>
    <td><?= $user['id'] ?></td>
    <td><?= htmlspecialchars($user['fio']) ?></td>
    <td><?= htmlspecialchars($user['phone']) ?></td>
    <td><?= htmlspecialchars($user['email']) ?></td>
    <td>
        <a href="edit.php?id=<?= $user['id'] ?>">
            Редактировать
        </a>
        |
        <a href="delete.php?id=<?= $user['id'] ?>"
           onclick="return confirm('Удалить запись?')">
            Удалить
        </a>
    </td>
</tr>
<?php endforeach; ?>
</table>
<h2 style="margin-top:30px;">
    Статистика языков программирования
</h2>
<table border="1" cellpadding="5">
<tr>
    <th>Язык</th>
    <th>Количество пользователей</th>
</tr>
<?php foreach ($stats as $row): ?>
<tr>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= $row['total'] ?></td>
</tr>
<?php endforeach; ?>
</table>
</body>
</html>