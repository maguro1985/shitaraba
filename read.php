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
require 'simple_html_dom.php';

// 対象のURL
$url = "https://jbbs.shitaraba.net/bbs/read.cgi/internet/" . $DIR . "/" . $KEY . "/";
// HTMLを取得
$html = file_get_html($url);

// データベース接続情報
$db_host = "localhost";
$db_name = "new_shitaraba";
$db_user = "root";
$db_password = "xxxx";

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
// 最後のnの数値を取得
if ($comment_numbers) {
    $max_comment_number = max($comment_numbers);
} else {
    echo 'コメントが見つかりませんでした';
}

// 与えられたHTMLからdt要素とdd要素を取得
$dt_elements = $html->find('dt');
$dd_elements = $html->find('dd');


                // タイトルをデータベースから拾ってきてthread_title変数に代入する処理
                // MySQLに接続
                $connection = new mysqli($db_host, $db_user, $db_password, $db_name);

                // 接続エラーの確認
                if ($connection->connect_error) {
                    die("Connection failed: " . $connection->connect_error);
                }

                $sql = "SELECT Title FROM thread WHERE Thread_ID = $KEY";
                $result = $connection->query($sql);

                if ($result->num_rows > 0) {
                    $row = $result->fetch_assoc();
                    // SQLからcontentを拾ってくる。
                    $thread_title = $row['Title'];
                    // SQLの接続を閉じる
                    $connection->close();
                   }


// 最後のレス番までのレスの情報を取得
for ($n = 1; $n <= $max_comment_number; $n++) {
 $dt_element = $dt_elements[$n-1];
 $dd_element = $dd_elements[$n-1];
 $dd_text = $dd_element->plaintext;
if (preg_match('/：(.*?)：/', $dt_element->plaintext, $matches)) {
    $name_result = $matches[1];
} else {
    $name_result = "マッチする部分が見つかりませんでした.";
}

$dd_elementname = $dt_element->next_sibling();
if (preg_match('/：(.*?)：/', $dt_element->plaintext, $matches)) {
    $Name_result = $matches[1];
} else {
    $Name_result = "マッチする部分が見つかりませんでした.";
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
                        'Number' => $n,
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
    <meta charset="utf-8">
    <title><?php echo $thread_title ?></title>
    <style>
        body {
            background-color: #EFEFEF;
            color: #000000;
        }
        h1 {
            color: #FF0000;
            font-size: large;
        }
        a { color: #0000FF; }
        a:hover { color: #FF0000; }
        a:visited { color: #660099; }
    </style>
    <style>
        body {
            word-break: break-all;
            word-wrap: break-word;
        }
	b {
	    color: green;
	}
        h1.thread-title {
            margin: 0 0 0;
        }
        table.header-menu {
            border:solid #888;
            border-width:0 0 1px 0;
            margin-bottom:2px;
        }
        div.header-menu {
            padding-bottom: 3px;
            margin-bottom: 2px;
            border-bottom: solid #888 1px;
        }
        div.limit-over {
            background-color: red;
            color: white;
            line-height: 3em;
            margin: 1px;
            padding:1px;
        }
        div.limit-alert {
            background-color: red;
            color: white;
            margin: 1px;
            padding: 1px;
        }
        #thread-body-wrapper {
            float: left;
            margin-right: -200px;
            width: 100%;
        }
        #thread-body-wrapper dd {
            position: relative;
        }
        #thread-body {
            margin-right: 200px;
        }
        .highlighted {
            background-color: yellow;
            font-weight: bold;
        }
        .rep-comment {
            background-color: #CCC;
            border: 1px solid #000;
            left: 15px;
            margin: 0;
            padding-left: 10px;
            padding-right: 10px;
            position: absolute;
            z-index: 1;
        }
        .pc_res_image_lazy {
            width: 200px;
            max-width:200px;
        }
        #res_image_preview_box img {
            width: 100px;
            height: auto;
            margin: 10px;
            padding: 10px;
            border: solid 1px #d3d3d3;
            background: #fff;
            box-shadow: 6px 6px 10px 0 rgb(0 0 0 / 10%);
            border-radius: 3px;
        }
        .thumbnail_explain {
            font-size: 14px;
            vertical-align: top;
        }
    </style>
</head>
<body>
    <h1 class="thread-title"><?php echo $thread_title ?></h1>
    <div id="thread-body-wrapper">
        <dl id="thread-body">
<?php

// MySQLに接続
$connection = new mysqli($db_host, $db_user, $db_password, $db_name);

// 接続エラーの確認
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// データベースから対応するスレッドの投稿を取得するクエリ
switch ($VIEW) {
    case '10':
        // VIEWが10の時の処理
        $sql = "SELECT * FROM (
        SELECT * FROM post WHERE ThreadID = $KEY ORDER BY Timestamp DESC LIMIT 10
        ) AS recent_posts ORDER BY Timestamp ASC";
        break;
    case '50':
        // 最新50
        $sql = "SELECT * FROM (
        SELECT * FROM post WHERE ThreadID = $KEY ORDER BY Timestamp DESC LIMIT 50
) AS recent_posts ORDER BY Timestamp ASC";
        break;
    default:
        // どのcaseにも該当しない場合の処理
               $sql = "SELECT * FROM post WHERE ThreadID = $KEY ORDER BY Timestamp ASC";
}



$result = $connection->query($sql);

 

if ($result->num_rows > 0) {
    // 結果を連想配列として取得
    while ($row = $result->fetch_assoc()) {
// 日付を見やすい形に処理
// $row['Timestamp'] が '2023-11-23 11:17:59' のような形式
$timestamp = $row['Timestamp'];
// DateTimeオブジェクトを作成
$dateTime = new DateTime($timestamp);
// 曜日の日本語表記を取得
$dayOfWeek = $dateTime->format('N');
$dayOfWeekJapanese = ['(日)', '(月)', '(火)', '(水)', '(木)', '(金)', '(土)'][$dayOfWeek - 1];
// フォーマットを指定して日付を取得
$formattedDateTime = $dateTime->format('Y/m/d') . $dayOfWeekJapanese . $dateTime->format('(D) H:i:s');



        echo "<dt>";
        echo "{$row['PostNo']}：<b>{$row['PostName']}</b>：";
        echo "$formattedDateTime:";
        echo "ID:{$row['UserID']}";
        echo "</dt>";
        echo "<dd>";
        echo nl2br(htmlspecialchars($row['Content'], ENT_QUOTES, 'UTF-8'));

        echo "<br><br><br>";
        echo "</dd>";
}//whileの閉じかっこ   
}//ifの閉じかっこ

// 接続を閉じる
$connection->close();
?>
        </dl>
    </div>
	<!-- 広告スペース -->
    <div id="footer-menu">
        <table border="0" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td>
      <form action='user_page.php' method='POST'>
      <button class='form-button' type='submit'>ホームへ戻る</button>
      </form>
                </td>
                <td align="right"></td>
            </tr>
        </table>
    </div>
    <br>
    <table>
        <tr>
            <td>
            
<div id="form_write">
    <form method="POST" action="write.php" name="fcs">
        <input type="hidden" name="BBS" value="24624">
        <input type="hidden" name="KEY" value="<?php echo $KEY; ?>">
        <input type="hidden" name="DIR" value="internet">
        <input type="submit" value="書き込む">
        <br>
        <textarea rows="5" cols="70" wrap="OFF" name="MESSAGE"></textarea><br>
        <div id="res_image_preview_box"></div>
    </form>
</div>
                    <span class="thumbnail_explain">オリジナルのメンタル広場への書き込みは投稿されません。</span>
                </td>
            </tr>
        </table>
    <hr>
    <div id="smartphone_switcher" style="display:none; padding:1em;">
        <button style="width:100%; font-size:3em;" onclick="switch_smartphone()">スマートフォン版</button>
    </div>
    <table align="right">
        <tr>
            <td align="right" valign="top" nowrap>
            </td>
        </tr>
    </table>
    <small>
        掲示板管理者へ連絡
        Newメンタル広場</a>
    </small>
</body>
</html>