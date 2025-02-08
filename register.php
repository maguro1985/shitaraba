<?php
// データベース接続情報
$servername = "localhost";
$username_db = "root";
$password_db = "xxxx";
$dbname = "new_shitaraba";

// POST データからユーザー情報を受け取る
$username = $_POST['username'];
$email = $_POST['email'];
$password = $_POST['password'];

// データベースに接続
$conn = new mysqli($servername, $username_db, $password_db, $dbname);

// 接続確認
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// 重複チェック
$sql_check_duplicate = "SELECT * FROM users WHERE Email = ?";
$stmt_check_duplicate = $conn->prepare($sql_check_duplicate);
$stmt_check_duplicate->bind_param("s", $email);
$stmt_check_duplicate->execute();
$result_check_duplicate = $stmt_check_duplicate->get_result();
$stmt_check_duplicate->close();

// 重複が無ければデータを登録
if ($result_check_duplicate->num_rows == 0) {
    // パスワードハッシュ化（セキュリティ対策）
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // SQL文を作成して実行（プリペアドステートメントを使用）
    $sql = "INSERT INTO users (Username, Email, Password) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);

    // パラメータをバインド
    $stmt->bind_param("sss", $username, $email, $hashedPassword);

    // 実行
    if ($stmt->execute()) {
        echo "ユーザーが登録されました。";

        // リダイレクト先のファイル名やURLを指定
        $redirect_location = "./";

        // リダイレクトを実行
        header("Location: " . $redirect_location);

        // リダイレクト後に実行されるコードがあれば、exitを呼ぶことが推奨されます
        exit;
    } else {
        echo "エラー: " . $stmt->error;
    }

    // ステートメントを閉じる
    $stmt->close();
} else {
    echo "エラー: 既に同じメールアドレスが登録されています。";
}

// データベース接続を閉じる
$conn->close();
?>
