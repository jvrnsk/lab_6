<?php
require_once 'db.php';
session_start();

$id = intval($_GET['id']);

// получаем данные пользователя
$stmt = $db->prepare("SELECT * FROM applications WHERE id=?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Пользователь не найден");
}

// обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $stmt = $db->prepare("
        UPDATE applications
        SET fio=?, phone=?, email=?, birthdate=?, gender=?, bio=?
        WHERE id=?
    ");

    $stmt->execute([
        $_POST['fio'],
        $_POST['phone'],
        $_POST['email'],
        $_POST['birthdate'],
        $_POST['gender'],
        $_POST['bio'],
        $id
    ]);

    header("Location: admin.php");
    exit();
}
?>

<h2>Редактирование</h2>

<form method="POST">
    <input name="fio" value="<?= htmlspecialchars($user['fio']) ?>"><br>
    <input name="phone" value="<?= htmlspecialchars($user['phone']) ?>"><br>
    <input name="email" value="<?= htmlspecialchars($user['email']) ?>"><br>
    <input name="birthdate" value="<?= $user['birthdate'] ?>"><br>

    <select name="gender">
        <option value="male" <?= $user['gender']=='male'?'selected':'' ?>>male</option>
        <option value="female" <?= $user['gender']=='female'?'selected':'' ?>>female</option>
    </select><br>

    <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea><br>

    <button type="submit">Сохранить</button>
</form>