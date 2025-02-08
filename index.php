<?php
session_start();
// フォームが送信された場合
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // データベースへの接続
    $conn = new mysqli('localhost', 'root', 'xxxx', 'new_shitaraba');

    if ($conn->connect_error) {
        die('Connection failed: ' . $conn->connect_error);
    }

    // プリペアドステートメントを使用してSQLインジェクションを防ぐ
    $stmt = $conn->prepare("SELECT image,username, password, UserLock FROM users WHERE email = ?");
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->bind_result($image, $username, $hashedPassword, $lockStatus);
    $stmt->fetch();




    if (password_verify($password, $hashedPassword)) {
        $_SESSION['username'] = $username;
        $_SESSION['image'] = $image;

        // ログイン成功時にクッキーにデータを保存
        setcookie("rememberedEmail", $email, time() + (86400 * 30)); // 30 days expiration
        setcookie("rememberedPassword", $password, time() + (86400 * 30)); // 30 days expiration

    if ($lockStatus == 1) {
        // Lockが1の場合は404.phpにリダイレクト
        header("Location: 404.php");
        exit();
    }

        header("Location: user_page.php");
        exit();
    } else {
        $_SESSION['error_message'] = "メールアドレスまたはパスワードが正しくありません。";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="ja">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: #ffffff;
        }

        header {
            background-color: #333;
            color: #fff;
            padding: 15px;
            text-align: center;
        }

        nav {
            background-color: #555;
            color: #fff;
            padding: 10px;
            text-align: center;
        }

        nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px;
            margin: 0 5px;
        }

        section {
            padding: 20px;
        }

        footer {
            background-color: #333;
            color: #fff;
            padding: 10px;
            text-align: center;
        }

        /* Media Query for Responsive Design */
        @media only screen and (max-width: 600px) {
            nav {
                flex-direction: column;
                text-align: left;
            }

            nav a {
                margin: 5px 0;
            }
        }
    </style>
    <title>νしたらば</title>
</head>
<body>

    <header>
        <h1>νメンタル広場</h1>
    </header>

<p></p>

    <section>
<img src=img/mental.jpg>
        
  <?php
    if (isset($_SESSION['error_message'])) {
        echo "<p>{$_SESSION['error_message']}</p>";
        unset($_SESSION['error_message']);
    }
    ?>

    <form action="" method="post">
    <label for="email">登録時ID(Email):</label>
    <input type="email" name="email" value="<?php echo isset($_COOKIE['rememberedEmail']) ? $_COOKIE['rememberedEmail'] : ''; ?>" required>

    <label for="password">Password:</label>
    <input type="password" name="password" value="<?php echo isset($_COOKIE['rememberedPassword']) ? $_COOKIE['rememberedPassword'] : ''; ?>" required>

        <button type="submit">Login</button>
    </form>
    </section>

    <footer>
        &copy; 2023 maguro1985
    </footer>

</body>
</html>
