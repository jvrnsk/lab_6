<?php
require_once 'db.php';
session_start();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    die("Некорректный ID");
}

/* ------------------ GET USER ------------------ */
$stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("Пользователь не найден");
}

/* ------------------ GET ALL LANGUAGES ------------------ */
$allLangsStmt = $db->query("SELECT name FROM programming_languages");
$allLanguages = $allLangsStmt->fetchAll(PDO::FETCH_COLUMN);

/* ------------------ USER LANGUAGES ------------------ */
$userLangStmt = $db->prepare("
    SELECT pl.name
    FROM programming_languages pl
    JOIN application_languages al ON pl.id = al.language_id
    WHERE al.application_id = ?
");
$userLangStmt->execute([$id]);
$userLanguages = $userLangStmt->fetchAll(PDO::FETCH_COLUMN);

/* ------------------ UPDATE ------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        $db->beginTransaction();

        /* update main data */
        $stmt = $db->prepare("
            UPDATE applications
            SET fio = ?, phone = ?, email = ?, birthdate = ?, gender = ?, bio = ?, contract_agreed = ?
            WHERE id = ?
        ");

        $stmt->execute([
            $_POST['fio'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            isset($_POST['contract']) ? 1 : 0,
            $id
        ]);

        /* delete old languages */
        $db->prepare("
            DELETE FROM application_languages
            WHERE application_id = ?
        ")->execute([$id]);

        /* insert new languages */
        if (!empty($_POST['languages'])) {
            $stmtLang = $db->prepare("
                INSERT INTO application_languages (application_id, language_id)
                SELECT ?, id FROM programming_languages WHERE name = ?
            ");

            foreach ($_POST['languages'] as $lang) {
                $stmtLang->execute([$id, $lang]);
            }
        }

        $db->commit();

        header("Location: admin.php");
        exit();

    } catch (PDOException $e) {
        $db->rollBack();
        die("Ошибка: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Редактирование</title>
</head>
<body>

<h2>Редактирование анкеты</h2>

<form method="POST">

    <input name="fio" value="<?= htmlspecialchars($user['fio']) ?>" placeholder="ФИО"><br><br>

    <input name="phone" value="<?= htmlspecialchars($user['phone']) ?>" placeholder="Телефон"><br><br>

    <input name="email" value="<?= htmlspecialchars($user['email']) ?>" placeholder="Email"><br><br>

    <input type="date" name="birthdate"
           value="<?= htmlspecialchars($user['birthdate']) ?>"><br><br>

    <select name="gender">
        <option value="male" <?= $user['gender'] === 'male' ? 'selected' : '' ?>>Мужской</option>
        <option value="female" <?= $user['gender'] === 'female' ? 'selected' : '' ?>>Женский</option>
    </select><br><br>

    <textarea name="bio"><?= htmlspecialchars($user['bio']) ?></textarea><br><br>

    <!-- languages -->
    <label>Языки программирования:</label><br>
    <select name="languages[]" multiple size="6">
        <?php foreach ($allLanguages as $lang): ?>
            <option value="<?= $lang ?>"
                <?= in_array($lang, $userLanguages) ? 'selected' : '' ?>>
                <?= $lang ?>
            </option>
        <?php endforeach; ?>
    </select><br><br>

    <!-- contract -->
    <label>
        <input type="checkbox" name="contract"
            <?= $user['contract_agreed'] ? 'checked' : '' ?>>
        Согласие с контрактом
    </label><br><br>

    <button type="submit">Сохранить</button>

</form>

</body>
</html>