<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

require_once 'db.php';

/* ------------------ GENERATORS ------------------ */

function generateLogin() {
    return 'user' . rand(1000, 9999);
}

function generatePassword($length = 8) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    return substr(str_shuffle($chars), 0, $length);
}

/* ------------------ COOKIES ------------------ */

function getFormData($field) {
    return $_COOKIE["form_$field"] ?? '';
}

function setFormCookie($name, $value, $expire = 0) {
    setcookie("form_$name", $value, $expire, '/');
}

function setErrorCookie($name, $message) {
    setcookie("error_$name", $message, 0, '/');
}

/* ------------------ LOGIN ------------------ */

if (isset($_POST['login_submit'])) {

    $stmt = $db->prepare("SELECT * FROM applications WHERE login = ?");
    $stmt->execute([$_POST['login']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['login_success'] = 1;
    } else {
        setcookie('login_error', 'Неверный логин или пароль', 0, '/');
    }

    header("Location: index.php");
    exit();
}

/* ------------------ FORM PROCESS ------------------ */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['login_submit'])) {

    $errors = [];

    /* -------- validation -------- */

    if (empty($_POST['fio'])) {
        $errors['fio'] = 'Заполните ФИО.';
        setErrorCookie('fio', $errors['fio']);
    }
    setFormCookie('fio', $_POST['fio']);

    if (empty($_POST['phone']) || !preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
        $errors['phone'] = 'Некорректный телефон';
        setErrorCookie('phone', $errors['phone']);
    }
    setFormCookie('phone', $_POST['phone']);

    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
        setErrorCookie('email', $errors['email']);
    }
    setFormCookie('email', $_POST['email']);

    if (empty($_POST['birthdate'])) {
        $errors['birthdate'] = 'Укажите дату';
        setErrorCookie('birthdate', $errors['birthdate']);
    }
    setFormCookie('birthdate', $_POST['birthdate']);

    if (empty($_POST['gender'])) {
        $errors['gender'] = 'Укажите пол';
        setErrorCookie('gender', $errors['gender']);
    }
    setFormCookie('gender', $_POST['gender']);

    if (empty($_POST['languages'])) {
        $errors['languages'] = 'Выберите язык';
        setErrorCookie('languages', $errors['languages']);
    }
    setFormCookie('languages', implode(',', $_POST['languages'] ?? []));

    if (empty($_POST['bio'])) {
        $errors['bio'] = 'Заполните биографию';
        setErrorCookie('bio', $errors['bio']);
    }
    setFormCookie('bio', $_POST['bio']);

    if (empty($_POST['contract'])) {
        $errors['contract'] = 'Требуется согласие';
        setErrorCookie('contract', $errors['contract']);
    }

    if (!empty($errors)) {
        header("Location: index.php");
        exit();
    }

    /* -------- DB -------- */

    try {

        $db->beginTransaction();

        /* generate credentials */
        $login = generateLogin();
        $password = generatePassword();
        $hash = password_hash($password, PASSWORD_DEFAULT);

        /* insert user */
        $stmt = $db->prepare("
            INSERT INTO applications
            (fio, phone, email, birthdate, gender, bio, contract_agreed, login, password_hash)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $_POST['fio'],
            $_POST['phone'],
            $_POST['email'],
            $_POST['birthdate'],
            $_POST['gender'],
            $_POST['bio'],
            isset($_POST['contract']) ? 1 : 0,
            $login,
            $hash
        ]);

        $applicationId = $db->lastInsertId();

        $_SESSION['generated_login'] = $login;
        $_SESSION['generated_password'] = $password;

        /* insert languages safely */
        if (!empty($_POST['languages'])) {

            $stmt = $db->prepare("
                INSERT INTO application_languages (application_id, language_id)
                SELECT ?, id FROM programming_languages WHERE name = ?
            ");

            foreach ($_POST['languages'] as $lang) {
                $stmt->execute([$applicationId, $lang]);
            }
        }

        $db->commit();

        header("Location: index.php?success=1&id=" . $applicationId);
        exit();

    } catch (PDOException $e) {

        $db->rollBack();
        setErrorCookie('db', 'Ошибка БД: ' . $e->getMessage());
        header("Location: index.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Анкета</title>
</head>
    <style>
body {
    max-width: 700px;
    margin: 0 auto;
    padding: 10px;
    background-color: #f398c3;
    color: #c93b6b;
}

h1 {
    text-align: center;
}

.success-message{
    color: white;
    font: 16pt bold;
    margin-top: 10px;
    padding: 10px;
    border-radius: 5px;
    background-color: #606d42;
    border: none;
    border-radius: 4px;
    text-align: center;
}

form{
    background-color: #f8f8e0;
    padding: 10px;
    border-radius: 4px;
height: 100%;
}

.form-group {
    margin-bottom: 15px;
}

label {
    display: block;
    font-weight: bold;
}

input[type="text"],
input[type="tel"],
input[type="email"],
input[type="date"],
textarea,
select {
margin-top: 5px;
    width: 100%;
    padding: 8px;
    border: 1px solid #9eb370;
    border-radius: 4px;
    box-sizing: border-box;
}

textarea {
    height: 100px;
}

.radio-group, .checkbox-group {
    display: flex;
    align-items: center;
    gap: 8px;
    margin: 5px 0;
}

.error {
    color: red;
    font-size: 0.9em;
    margin-top: 5px;
}

button {
    background-color: #9eb370;
    color: white;
    padding: 10px 15px;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 16px;
}

button:hover {
    background-color: #606d42;
}
</style>
<body>

<!-- ФОРМА ВХОДА -->
<form method="POST" style="margin-bottom:20px;">
    <h2>Вход</h2>

    <input type="text" name="login" placeholder="Логин">
    <input type="password" name="password" placeholder="Пароль">

    <button type="submit" name="login_submit">
        Войти
    </button>

    <?php if (isset($_COOKIE['login_error'])): ?>
        <div class="error">
            <?= htmlspecialchars($_COOKIE['login_error']) ?>
        </div>
    <?php endif; ?>
</form>

<?php if (isset($_SESSION['login_success'])): ?>
    <div class="success-message">
        Вход выполнен успешно!
    </div>
    <?php unset($_SESSION['login_success']); ?>
<?php endif; ?>


<!-- ФОРМА АНКЕТЫ -->
<form action="index.php" method="POST">

    <h1>Анкета</h1>

    <!-- ФИО -->
    <div class="form-group">
        <label for="fio">ФИО:</label>
        <input type="text" id="fio" name="fio"
               value="<?= htmlspecialchars(getFormData('fio')) ?>"
               class="<?= isset($_COOKIE['error_fio']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_fio'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_fio']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Телефон -->
    <div class="form-group">
        <label for="phone">Телефон:</label>
        <input type="tel" id="phone" name="phone"
               value="<?= htmlspecialchars(getFormData('phone')) ?>"
               class="<?= isset($_COOKIE['error_phone']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_phone'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_phone']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Email -->
    <div class="form-group">
        <label for="email">Email:</label>
        <input type="email" id="email" name="email"
               value="<?= htmlspecialchars(getFormData('email')) ?>"
               class="<?= isset($_COOKIE['error_email']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_email'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_email']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Дата рождения -->
    <div class="form-group">
        <label for="birthdate">Дата рождения:</label>
        <input type="date" id="birthdate" name="birthdate"
               value="<?= htmlspecialchars(getFormData('birthdate')) ?>"
               class="<?= isset($_COOKIE['error_birthdate']) ? 'error-field' : '' ?>">
        <?php if (isset($_COOKIE['error_birthdate'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_birthdate']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Пол -->
    <div class="form-group">
        <label>Пол:</label>

        <div class="radio-group">
            <input type="radio" id="male" name="gender" value="male"
                <?= getFormData('gender') == 'male' ? 'checked' : '' ?>>
            <label for="male">Мужской</label>
        </div>

        <div class="radio-group">
            <input type="radio" id="female" name="gender" value="female"
                <?= getFormData('gender') == 'female' ? 'checked' : '' ?>>
            <label for="female">Женский</label>
        </div>

        <?php if (isset($_COOKIE['error_gender'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_gender']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Языки -->
    <div class="form-group">
        <label for="languages">Языки программирования:</label>

        <select name="languages[]" multiple>
            <?php
            $selectedLangs = explode(',', getFormData('languages'));
            $options = ['Pascal','C','C++','JavaScript','PHP','Python','Java','Haskel','Clojure','Prolog','Scala','Go'];

            foreach ($options as $lang): ?>
                <option value="<?= $lang ?>"
                    <?= in_array($lang, $selectedLangs) ? 'selected' : '' ?>>
                    <?= $lang ?>
                </option>
            <?php endforeach; ?>
        </select>

        <?php if (isset($_COOKIE['error_languages'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_languages']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Биография -->
    <div class="form-group">
        <label for="bio">Биография:</label>
        <textarea name="bio"><?= htmlspecialchars(getFormData('bio')) ?></textarea>

        <?php if (isset($_COOKIE['error_bio'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_bio']) ?></div>
        <?php endif; ?>
    </div>

    <!-- Согласие -->
    <div class="form-group">
        <label>
            <input type="checkbox" name="contract" value="1"
                <?= getFormData('contract') ? 'checked' : '' ?>>
            С контрактом ознакомлен(-а)
        </label>

        <?php if (isset($_COOKIE['error_contract'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_contract']) ?></div>
        <?php endif; ?>
    </div>

    <button type="submit">Сохранить</button>

        <?php if (isset($_GET['success'])): ?>
            <div class="success-message">
    <?php if (isset($_SESSION['generated_login'])): ?>
        Спасибо за заполнение анкеты!<br><br>
        Ваш логин:
        <b><?= $_SESSION['generated_login'] ?></b><br>

        Ваш пароль:
        <b><?= $_SESSION['generated_password'] ?></b><br><br>

        Сохраните их! Они показываются только один раз.

        <?php
        unset($_SESSION['generated_login']);
        unset($_SESSION['generated_password']);
        ?>

    <?php else: ?>
        Данные успешно обновлены!
    <?php endif; ?>
</div>
        <?php endif; ?>
        <?php if (isset($_COOKIE['error_db'])): ?>
            <div class="error"><?= htmlspecialchars($_COOKIE['error_db']) ?></div>
        <?php endif; ?>
    </form>
</body>
</html>