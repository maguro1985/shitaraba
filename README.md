既存のしたらば掲示板スレッドをミラーリングし、登録メンバーのみの非公開の形でコメント投稿できるようにするプログラムです。

以下データベースの設定です。
CREATE TABLE thread (
    Thread_ID VARCHAR(20),
    Title VARCHAR(255),
    Url VARCHAR(255),
    Creator VARCHAR(255),
    CreationDateTime TIMESTAMP,
    LastUpdatedDateTime TIMESTAMP NULL,
    ViewCount INT,
    ReplyCount INT,
    LastReply VARCHAR(255),
    Category VARCHAR(255),
    Status BOOLEAN,
    Tags VARCHAR(255),
    Improper BOOLEAN DEFAULT false,
    Display BOOLEAN DEFAULT true
);

CREATE TABLE users (
    Userid INT PRIMARY KEY AUTO_INCREMENT,
    Username VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL,
    Password VARCHAR(255) NOT NULL,
    Verification_code VARCHAR(255),
    Verified BOOLEAN DEFAULT FALSE,
    exp INT,
    gold INT,
    message VARCHAR(255),
    cx INT,
    cy INT,
    mx INT,
    my INT,
    weapon INT,
    def INT,
    str INT,
    dex INT,
    intel INT,
    luc INT,
    hp INT,
    mp INT,
    magic INT,
    lv INT,
    battle INT,
    AT INT,
    MAT INT,
    DF INT,
    MDF INT,
　　Lock BOOLEAN DEFAULT false,
    UserLock BOOLEAN
    image INT
);

CREATE TABLE post (
    PostID INT AUTO_INCREMENT PRIMARY KEY,
    PostNo INT,
    PostName VARCHAR(255),
    PostEmail VARCHAR(255),
    ThreadID INT,
    UserID VARCHAR(255),
    Content TEXT,
    Timestamp TIMESTAMP,
    ParentPostID INT,
    UserIP VARCHAR(255),
    SelfFlag BOOLEAN,
    Hidden BOOLEAN,
    UserName VARCHAR(255),
    Improper BOOLEAN DEFAULT false,
    Display BOOLEAN DEFAULT true,
    AIConvert BOOLEAN DEFAULT FALSE,
    image INT,
    file_name VARCHAR(255),
    image_name VARCHAR(255),
    image_path VARCHAR(255),
    upload_flug BOOLEAN DEFAULT false
);

