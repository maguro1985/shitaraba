<?php
session_start();

if (!isset($_SESSION['username'])) {
    header("Location: user_page.php");
    exit();
}

$KEY = isset($_POST['KEY']) ? $_POST['KEY'] : '';
$DIR = isset($_POST['DIR']) ? $_POST['DIR'] : '';
$VIEW = isset($_POST['VIEW']) ? $_POST['VIEW'] : '';

$username = $_SESSION['username'];
$image = $_SESSION['image'];
require 'simple_html_dom.php';

// 対象のURL
if($VIEW === '50'){
$url = "https://jbbs.shitaraba.net/bbs/read.cgi/internet/" . $DIR . "/" . $KEY . "/l50";
}

// HTMLを取得
$html = file_get_html($url);

// データベース接続情報
$db_host = "localhost";
$db_name = "new_shitaraba";
$db_user = "root";
$db_password = "4q@lnp3viy";


// 最後のレス番を取得
// <dt id="comment_n">の中のnを取得するための正規表現
$pattern = '/comment_(\d+)/';
// 全ての<dt>タグを取得
$dt_tags = $html->find('dt[id^=comment_]');
// nの値を格納するリスト

$comment_numbers = [];
// 各<dt>タグからnの数値を抽出
foreach ($dt_tags as $dt_tag) {
    if (preg_match($pattern, $dt_tag->id, $matches)) {
        $comment_numbers[] = intval($matches[1]);
    }
}


// 与えられたHTMLからdt要素とdd要素を取得
$dt_elements = $html->find('dt');
$dd_elements = $html->find('dd');




// 最後のレス番までのレスの情報を取得
for ($n = 0; $n < count($comment_numbers); $n++) {
 $dt_element = $dt_elements[$n];
 $dd_element = $dd_elements[$n];
 $dd_text = $dd_element->plaintext;
if (preg_match('/：(.*?)：/', $dt_element->plaintext, $matches)) {
    $Name_result = $matches[1];
} else {
    $name_result = "マッチする部分が見つかりませんでした.";
    $name_result = "名無しさん";
}
// 正規表現を用いて日付時間の部分を抽出
preg_match('/：(\d{4}\/\d{2}\/\d{2}\([^)]+\) \d{2}:\d{2}:\d{2})/', $dt_element->plaintext, $matches);
// $matchesには正規表現にマッチした結果が格納される
// 配列の2番目の要素が日付時間に対応する
if (isset($matches[1])) {
    $datetimeString = $matches[1];
    $datetime = date('Y-m-d H:i:s', strtotime(preg_replace('/\([^)]+\)/', '', $datetimeString)));
} else {
    $datetimeString = "日時情報が見つかりませんでした.";
}

 preg_match('/ID:(\S+)/', $dt_element->plaintext, $matches);
if (isset($matches[1])) {
    $idString = $matches[1];
} else {
    $idString = "IDが見つかりませんでした.";
}

// MySQLに接続
$connection = new mysqli($db_host, $db_user, $db_password, $db_name);

// 接続エラーの確認
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$sql = "SELECT Content FROM post WHERE ThreadID = $KEY AND PostNo = $n AND Hidden = 1";
$result = $connection->query($sql);

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    // SQLからcontentを拾ってくる。
    $dd_text = $row['Content'];
    // SQLの接続を閉じる
    $connection->close();
} else {
    // レコードが見つからなかった場合の処理

}

// 情報を配列に格納する
                    $Res = [
                        'Number' => $comment_numbers[$n],
                        'Name' => $Name_result,
                        'Date' => $datetime,
                        'Id' => $idString,
                        'Content' => $dd_text
                    ];
// SQLに登録する。
// MySQLに接続
$connection = new mysqli($db_host, $db_user, $db_password, $db_name);

// 接続エラーの確認
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// 重複の確認
$sql_check_duplicate = "SELECT * FROM post WHERE Timestamp = ? AND ThreadID = ?";
$stmt_check_duplicate = $connection->prepare($sql_check_duplicate);
$stmt_check_duplicate->bind_param("si", $Res['Date'], $KEY);
$stmt_check_duplicate->execute();
$result_check_duplicate = $stmt_check_duplicate->get_result();
$stmt_check_duplicate->close();

// 重複が無ければデータを登録
if ($result_check_duplicate->num_rows == 0) {
    // 挿入クエリ
    $sql_insert_post = "INSERT INTO post (PostNo, PostName, Timestamp, UserID, Content, ThreadID) VALUES (?, ?, ?, ?, ?, ?)";
    // プリペアドステートメントの作成
    $stmt = $connection->prepare($sql_insert_post);

    // パラメータのバインド
    $stmt->bind_param("issssi", $Res['Number'], $Res['Name'], $Res['Date'], $Res['Id'], $Res['Content'], $KEY);
  // クエリの実行
    $stmt->execute();
  // ステートメントのクローズ
    $stmt->close();
}
// SQLの接続を閉じる
$connection->close();

}// for閉じ
// simple_html_domの解放
$html->clear();
unset($html);

?>

<!DOCTYPE html>
<html lang="ja">
<head>
<title>νメンタル広場</title>
</head>
<body>
<style>
.oneArea {
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
  max-width: 1024px;
  margin: 50px auto;
  padding: 0 10px;
}
.oneArea .onebox {
  width: 100%;
  display: flex;
  flex-wrap: wrap;
  justify-content: flex-start;
}
.oneArea .onebox:nth-child(even) {
  flex-direction: row-reverse;
  margin-top: 20px;
}
.oneArea .onebox .imgArea {
  width: 16%;
  position: relative;
}
.oneArea .onebox:nth-child(odd) .imgArea img {
  width: 100%;
  max-width: 130px;
  position: absolute;
  top: -20px;
  left: 0;
  padding-right: 30px;
}
.oneArea .onebox:nth-child(even) .imgArea img {
  width: 100%;
  max-width: 130px;
  position: absolute;
  top: -20px;
  left: auto;
  right: 0;
  padding-left: 30px;
}
.oneArea .onebox .fukiArea {
  width: 63%;
}
.oneArea .onebox .fukidasi {
  width: 100%;
  position: relative;
  padding: 25px;
  background-color: #f2f3f7;
  font-size: 18px;
  color: #231815;
  border-radius: 12px;
  box-sizing: border-box;
}
.oneArea .onebox .fukidasi::before {
  content: '';
  position: absolute;
  display: block;
  width: 0;
  height: 0;
  border-radius: 50%;
  transform: rotate(45deg);
  top: 22px;
  border-right: 25px solid transparent;
  border-bottom: 25px solid transparent;
}
.oneArea .onebox .fukidasi::after {
  content: '';
  position: absolute;
  display: block;
  width: 0;
  height: 0;
  border-radius: 50%;
  transform: rotate(45deg);
  top: 40px;
  border-right: 25px solid transparent;
  border-bottom: 25px solid transparent;
}
.oneArea .onebox:nth-child(odd) .fukidasi::before {
  left: -15px;
  border-left: 25px solid #f2f3f7;
  border-top: 25px solid transparent;
}
.oneArea .onebox:nth-child(odd) .fukidasi::after {
  left: -25px;
  border-left: 25px solid #ffffff;
  border-top: 25px solid transparent;
}
.oneArea .onebox:nth-child(even) .fukidasi {
  background-color: #fde5e5;
}
.oneArea .onebox:nth-child(even) .fukidasi::before {
  left: auto;
  right: -15px;
  border-left: 25px solid transparent;
  border-top: 25px solid #fde5e5;
}
.oneArea .onebox:nth-child(even) .fukidasi::after {
  left: auto;
  right: -25px;
  border-left: 25px solid transparent;
  border-top: 25px solid #ffffff;
}
@media screen and (max-width: 1024px) {
  .oneArea .onebox:nth-child(odd) .imgArea img {
    max-width: 70%;
    top: 0;
  }
  .oneArea .onebox:nth-child(even) .imgArea img {
    max-width: 70%;
    top: 0;
  }
  .oneArea .onebox .fukidasi {
    padding: 15px;
    font-size: 14px;
  }
  .oneArea .onebox .fukidasi::before {
    top: 8px;
  }
  .oneArea .onebox .fukidasi::after {
    top: 20px;
  }
}
@media screen and (max-width: 420px) {
  .oneArea {
    margin: 30px auto;
  }
  .oneArea .onebox:nth-child(even) {
    margin-top: 15px;
  }
  .oneArea .onebox .imgArea {
    width: 20%;
  }
  .oneArea .onebox .fukidasi {
    padding: 10px 15px;
    font-size: 12px;
  }
}

/*====================================================================
以下は不要です。
====================================================================*/
body {
  background-color: #ffffff; /* 白 */
  color: #333; /* 文字色をダークグレーに設定 */
  font-family: 'Arial', sans-serif; /* フォントの指定 */
  line-height: 1.6; /* 行間を調整して読みやすさを向上させる */
  font-weight: 400;
  margin: 0;
}
.section {
  width: 100%;
  height: 100vh;
  overflow-x: hidden;
  overflow-y: auto;
  -webkit-overflow-scrolling: touch;
}
.section p._a {
  font-size: 12px;
  font-weight: bold;
  padding: 0 10px;
}
.section p._a .link {
  position: relative;
  display: inline-block;
  padding-left: 12px;
  margin: 0;
  color: #607D8B;
}
.section p._a .link:before {
  content: '';
  width: 0;
  height: 0;
  border-style: solid;
  border-width: 6px 0 6px 8px;
  border-color: transparent transparent transparent #607D8B;
  position: absolute;
  top: 50%;
  left: 0;
  margin-top: -6px;
}
</style>
<?php

// MySQLに接続
$connection = new mysqli($db_host, $db_user, $db_password, $db_name);

// 接続エラーの確認
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// データベースから対応するスレッドの投稿を取得するクエリ
$sql = "SELECT users.lv, users.hp, users.weapon, users.exp, users.gold, recent_posts.*, item.name AS weapon_name, recent_posts.upload_flug, recent_posts.image_path, recent_posts.PostID
        FROM (
            SELECT * FROM post WHERE ThreadID = $KEY AND Display = 1 ORDER BY Timestamp DESC LIMIT 50
        ) AS recent_posts
        LEFT JOIN users ON users.Username = recent_posts.PostName
        LEFT JOIN item ON users.weapon = item.no
        ORDER BY recent_posts.Timestamp ASC";



$result = $connection->query($sql);

 
echo '<div class="section">';
if ($result->num_rows > 0) {
    // 結果を連想配列として取得
    while ($row = $result->fetch_assoc()) {


echo '  <div class="oneArea">';
echo '    <div class="onebox">';
if ($row['PostNo'] == "4649") {
    echo '      <div class="imgArea"><img src="img/icon' . $row['image'] . '.jpg" alt="うつだYO"></div>';
} else if($row['PostNo'] == "2525"){
    echo '      <div class="imgArea"><img src="img/icon5.jpg" alt="ワタミン"></div>';
} else {
    echo '      <div class="imgArea"><img src="img/icon0.jpg" alt="うつだYO"></div>';
}
echo '      <div class="fukiArea">';
echo '<div class="name" id="' . $row['PostID'] . '">' . 'PostID：' . $row['PostID'] . '　' . '<b>' . $row['PostName'] . '</b>' . ' レベル：' . $row['lv'] . ' HP：' . $row['hp'] . ' 武器：' . $row['weapon_name'] . ' 経験値：' . $row['exp'] . ' Gold：' . $row['gold'] . '</div>';
// まず、htmlspecialchars を適用して特殊文字をエスケープ
$contentEscaped = htmlspecialchars($row['Content'], ENT_QUOTES, 'UTF-8');

// エスケープ後のテキストに対して preg_replace を適用
$pattern = '/&gt;&gt;(\d+)/'; // エスケープ後のパターンに注意
$replacement = '<a href="#$1">&gt;&gt;$1</a>';
$contentWithLinks = preg_replace($pattern, $replacement, $contentEscaped);
// 最後に nl2br を適用
$contentFinal = nl2br($contentWithLinks);

// 生成したコンテンツを出力
echo '<div class="fukidasi">' . $contentFinal . ($row['upload_flug'] == 1 ? '<br><a href="' . htmlspecialchars($row['image_path']) . '" target="_blank"><img src="' . htmlspecialchars($row['image_path']) . '" width="50" height="50"></a>' : '') . '</div>';






echo '      </div>';
echo '    </div>';
echo '  </div>';


}//whileの閉じかっこ   
}//ifの閉じかっこ
echo '</div>';

// 接続を閉じる
$connection->close();
?>

<div id="form_write">
    <form method="POST" action="write.php" enctype="multipart/form-data">
        <input type="hidden" name="BBS" value="24624">
        <input type="hidden" name="KEY" value="<?php echo $KEY; ?>">
        <input type="hidden" name="DIR" value="internet">
        <label for="imagefile">画像ファイルを選択（.jpg、.pngのみ）：</label>
        <input type="file" id="imagefile" name="imagefile" accept=".jpg, .jpeg, .png">
        <input type="submit" value="書き込む">
        <br>
        <textarea rows="5" cols="70" wrap="OFF" name="MESSAGE"></textarea><br>
        <div id="res_image_preview_box"></div>
    </form>
</div>

</body>
</html>


