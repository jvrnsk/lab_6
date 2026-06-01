<?php
require_once 'db.php';
session_start();

$id = intval($_GET['id']);

if ($id > 0) {

    // удалить связи языков
    $stmt = $db->prepare("
        DELETE FROM application_languages
        WHERE application_id=?
    ");
    $stmt->execute([$id]);

    // удалить анкету
    $stmt = $db->prepare("
        DELETE FROM applications
        WHERE id=?
    ");
    $stmt->execute([$id]);
}

header("Location: admin.php");
exit();