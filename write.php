<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: user_page.php");
    exit();
}



    // ユーザー名をセッションから取得
    $username = $_SESSION['username'];
    $image = $_SESSION['image'];

    // ランダムな数値を二つ生成
    $gold = mt_rand(1, 1000);
    $exp = mt_rand(1, 1000);

    // 受け取ったデータ
    $BBS = isset($_POST['BBS']) ? $_POST['BBS'] : '';
    $KEY = isset($_POST['KEY']) ? $_POST['KEY'] : '';
    $DIR = isset($_POST['DIR']) ? $_POST['DIR'] : '';
    $PRIVATE = isset($_POST['PRIVATE']) ? $_POST['PRIVATE'] : '';
    $NAME = isset($_POST['NAME']) ? $_POST['NAME'] : '';
    $MAIL = isset($_POST['MAIL']) ? $_POST['MAIL'] : '';
    $tmp = isset($_POST['MESSAGE']) ? $_POST['MESSAGE'] : '';
    $IP = $_SERVER['REMOTE_ADDR'];
    $MESSAGE = $tmp;

    $referer_url = "https://jbbs.shitaraba.net/{$DIR}/{$BBS}/{$KEY}/";
    $target_url = "https://jbbs.shitaraba.net/bbs/write.cgi/{$DIR}/{$BBS}/{$KEY}/";
    echo $target_url;


if (isset($_FILES['imagefile']) && $_FILES['imagefile']['error'] == 0) {
    $allowed_exts = array("jpg", "jpeg", "png");
    $file_name = $_FILES['imagefile']['name']; 
    $file_ext = strtolower(end(explode('.', $file_name)));
    $file_size = $_FILES['imagefile']['size'];
    $file_tmp = $_FILES['imagefile']['tmp_name'];

    if (in_array($file_ext, $allowed_exts) === true && $file_size < 6097152) {
        $new_file_name = uniqid() . '.' . $file_ext;
        if (move_uploaded_file($file_tmp, "uploads/" . $new_file_name)) {
             $upload_flug = 1;
            // ファイルアップロード成功
        } else {
            echo "ファイルのアップロードに失敗しました。";
        }
    } else {
        echo "不正なファイルタイプかファイルサイズが大きすぎます。";
    }
} else {
    echo "ファイルが選択されていないか、アップロード中にエラーが発生しました。";
}
$image_path = "uploads/" . $new_file_name; // アップロードされた画像のパス
echo $image_path;
// 非公開の書き込みをオリジナルしたらばへ行う
$Hidden = 1; // true
$NAME_UTF8 = "君と紅茶と流星群#OP25B";
$MAIL_UTF8 = "age";
$MESSAGE_UTF8 = "非公開の書き込みです。";
$TIME = time();
date_default_timezone_set('Asia/Tokyo');
// UnixタイムスタンプをMySQLのTIMESTAMP型に変換
$timestamp = date('Y-m-d H:i:s', $TIME);

// UTF-8からEUC-JPに変換
$NAME_EUCJP = mb_convert_encoding($NAME_UTF8, "EUC-JP");
$MAIL_EUCJP = mb_convert_encoding($MAIL_UTF8, "EUC-JP");
$MESSAGE_EUCJP = mb_convert_encoding($MESSAGE_UTF8, "EUC-JP");

// POSTデータの作成
$post_data = array(
    'DIR' => $DIR,
    'BBS' => $BBS,
    'TIME' => $TIME,
    'NAME' => $NAME_EUCJP,
    'MAIL' => $MAIL_EUCJP,
    'MESSAGE' => $MESSAGE_EUCJP,
    'KEY' => $KEY,  // ここにコンマを追加
    'IP' => $IP
);

// コンテキストを定義
$context = stream_context_create(array(
    'http' => array(
        'method' => 'POST',
        'header' => "Content-type: application/x-www-form-urlencoded\r\n" .
                    "Referer: {$referer_url}\r\n" .
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.159 Safari/537.36",
        'content' => http_build_query($post_data)
    )
));

// 書き込みを行う
// $response = file_get_contents($target_url, false, $context);


echo "投稿したで！<br>";
echo $gold . 'ゴールド手に入れた。<br>';
echo '経験値を' . $exp . '手に入れた。<br>';



// データベースに登録する
  // MySQLに接続
    // データベースへの接続
    $servername = "localhost";
    $dbname = "new_shitaraba";
    $username_db = "root";
    $password_db = "xxxx";
    $PostNo = 4649;
    $UserID = "νメンタル広場";

    $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username_db, $password_db);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $connection = new mysqli($servername, $username_db, $password_db, $dbname);


// 重複の確認
$sql_check_duplicate = "SELECT * FROM post WHERE Timestamp = ? AND ThreadID = ?";
$stmt_check_duplicate = $connection->prepare($sql_check_duplicate);
$stmt_check_duplicate->bind_param("si", $TIME, $KEY);
$stmt_check_duplicate->execute();
$result_check_duplicate = $stmt_check_duplicate->get_result();
$stmt_check_duplicate->close();

// 重複が無ければデータを登録
if ($result_check_duplicate->num_rows == 0) {
    //echo "重複がないので登録";
    // 挿入クエリ
$sql_insert_post = "INSERT INTO post (upload_flug, image_path, image, PostNo, UserID, PostName, Timestamp, Content, ThreadID, UserIP, UserName, PostEmail, Hidden) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

// プリペアドステートメントの作成
$stmt = $connection->prepare($sql_insert_post);

// パラメータのバインド
$stmt->bind_param("isiissssisssi",$upload_flug, $image_path, $image, $PostNo, $UserID, $username, $timestamp, $MESSAGE, $KEY, $IP, $username, $MAIL, $Hidden);

    // クエリの実行
    $stmt->execute();

    // ステートメントのクローズ
    $stmt->close();
}
// 接続を閉じる
$connection->close();

// MySQLに接続
$connection = new mysqli($servername, $username_db, $password_db, $dbname);

// 接続エラーの確認
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// プリペアドステートメントの準備
$sql = "UPDATE users
        SET exp = exp + ?,
            gold = gold + ?
        WHERE username = ?";
$stmt = $connection->prepare($sql);

// バインドパラメータの設定
$stmt->bind_param("iis", $exp, $gold, $username);

// クエリの実行
$result = $stmt->execute();

// 実行結果の確認
if ($result) {
    //echo "expとgoldの加算に成功しました。";
} else {
    echo "エラー: " . $stmt->error;
}

// ステートメントのクローズ
$stmt->close();

// 接続を閉じる
$connection->close();


      echo "<img src='img/box.jpg' width='50' height='50'>";
      echo "<form action='user_page.php' method='POST'>";
      echo "<button class='form-button' type='submit'>ホームに戻る</button>";
      echo "</form>";
?>
