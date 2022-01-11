<?php
include('./db_connect.php');
include('./header.php');
?>
<?php
/*Get Data From POST Http Request*/
$datas = file_get_contents('php://input');
/*Decode Json From LINE Data Body*/
$arrayJson = json_decode($datas, true);
file_put_contents('log.txt', file_get_contents('php://input') . PHP_EOL, FILE_APPEND);
$accessToken = "9gqqJaNRBhiHQkphEEU0VKOPi7pWWcMLL/bCHQRAK2+fTD5AExD9+VRxZR2Xv9Qi6NA2jjzdrB1jmj/RWtJ5EY5/+7fE6WgA4n4vfzc61EQK0V2vMe9sgrfpElt8stbnvWbc8UlH3Eaz6YCwOlL36AdB04t89/1O/w1cDnyilFU=";
$replyToken = $arrayJson['events'][0]['replyToken'];
$messageType = $arrayJson['events'][0]['message']['type'];
$messageID = $arrayJson['events'][0]['message']['id'];
$message = $arrayJson['events'][0]['message']['text'];
$userId = $arrayJson['events'][0]['source']['userId'];
$UserName = profile($userId, $accessToken);
$arrayHeader = array();
$arrayHeader[] = "Content-Type: application/json";
$arrayHeader[] = "Authorization: Bearer {$accessToken}";
$arrayPostData['replyToken'] = $replyToken;
//Set Line
$date = new DateTime;
$result = $date->format('Y-m-d H:i:s');
//Set Date
//Create floder

if ($messageType == 'text') {

    $Arraytext =  explode("#",  $message);
    if ($Arraytext[1] != "") {
        $arrayPostData['messages'][0]['type'] = "text";
        $arrayPostData['messages'][0]['text'] = Bill_Intent_Temp($Arraytext[1], $userId);
        //if have #
    } else {
        $arrayPostData['messages'][0]['type'] = "text";
        $arrayPostData['messages'][0]['text'] = 'ข้อความทั่วไป';
    }
    $arrayPostData['messages'][1]['type'] = "text";
    $arrayPostData['messages'][1]['text'] =  Check_User($userId, $accessToken);
    // Check_Haveuser($userId,$accessToken,$arrayHeader,$arrayPostData);
    replyMsg($arrayHeader, $arrayPostData);
} else if ($messageType == 'image') {
    $results = getContent($messageID, $accessToken);
    if ($results['result'] == 'S') {
        $arrayPostData['messages'][0]['type'] = "text";
        $arrayPostData['messages'][0]['text'] = SaveBill_Transaction($userId, $results['response']);;
        replyMsg($arrayHeader, $arrayPostData);
    } else {
        $arrayPostData['messages'][0]['type'] = "text";
        $arrayPostData['messages'][0]['text'] = $results['message'];
        replyMsg($arrayHeader, $arrayPostData);
    }
}

//Set Intent

function getContent($messageID, $accessToken)
{
    $datasReturn = [];
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_URL => "https://api-data.line.me/v2/bot/message/" . $messageID . "/content",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_POSTFIELDS => "",
        CURLOPT_HTTPHEADER => array(
            "Authorization: Bearer " . $accessToken,
            "cache-control: no-cache"
        ),
    ));
    $response = curl_exec($curl);
    $err = curl_error($curl);
    curl_close($curl);

    if ($err) {
        $datasReturn['result'] = 'E';
        $datasReturn['message'] = $err;
    } else {
        $datasReturn['result'] = 'S';
        $datasReturn['message'] = 'Success';
        $datasReturn['response'] = $response;
    }

    return $datasReturn;
}

function replyMsg($arrayHeader, $arrayPostData)
{
    $strUrl = "https://api.line.me/v2/bot/message/reply";
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $strUrl);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $arrayHeader);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrayPostData));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $result = curl_exec($ch);
    curl_close($ch);
}
function profile($userId, $accessToken)
{
    $urlprofile = 'https://api.line.me/v2/bot/profile/' . $userId;
    $headers = array('Authorization: Bearer ' . $accessToken);
    $profileline = curl_init($urlprofile);
    curl_setopt($profileline, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($profileline, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($profileline, CURLOPT_FOLLOWLOCATION, 1);
    $resultprofile = curl_exec($profileline);
    curl_close($profileline);
    return json_decode($resultprofile, true);
}
function CreateFloder($BillName)
{
    if (!file_exists("tmp_image")) {
        mkdir("tmp_image");
    }
    if (!file_exists("tmp_image/" . date("Y"))) {
        mkdir("tmp_image/" . date("Y"));
    }
    if (!file_exists("tmp_image/" . date("Y") . "/" . date("m")."/".$BillName)) {
        mkdir("tmp_image/" . date("Y") . "/" . date("m")."/".$BillName);
    }
}
function Check_User($userId, $accessToken)
{
    include('./db_connect.php');
    $username = profile($userId, $accessToken)['displayName'];
    $pictureUrl = profile($userId, $accessToken)['pictureUrl'];

    $sql = "SELECT `usertoken` FROM `User` WHERE `usertoken` =  '$userId' ";
    $result = $conn->query($sql);
    if (mysqli_num_rows($result)) {
        $arrayPostData['messages'][1]['type'] = "text";
        $arrayPostData['messages'][1]['text'] = "Have Token ";
        return "Have User In Database";
    } else {
        $sql2 = "INSERT INTO `User`(`ID`, `username`, `usertoken`, `pictureUrl`) VALUES (NULL,'$username','$userId','$pictureUrl')";
        $result2 = $conn->query($sql2);
        if ($result2) {
            return "Save this User to Database";
        }
    }
}

function Bill_Intent_Temp($Bill_text, $userId)
{
    include('./db_connect.php');
    $sql = "SELECT `Bill_Intent`,`userToken` FROM `Bill_Intent_Temp` WHERE userToken = '$userId'";
    $result = $conn->query($sql);
    if (mysqli_num_rows($result)) {
        $sql_Update = "UPDATE `Bill_Intent_Temp` SET `Bill_Intent`= '$Bill_text' WHERE `userToken`='$userId' ";
        $result2 = $conn->query($sql_Update);
        if ($result2) {
            return "Update Bill_Intent Complete";
        }
    } else {
        $sql_Insert = "INSERT INTO `Bill_Intent_Temp`(`Bill_Intent`, `userToken`) VALUES ('$Bill_text','$userId')";
        $result3 = $conn->query($sql_Insert);
        if ($result3) {
            return "Insert Bill_Intent Complete";
        }
    }
}

function SaveBill_Transaction($userId, $Image)
{
    include('./db_connect.php');
    
    $sql = "SELECT `Bill_Intent` FROM `Bill_Intent_Temp` WHERE `userToken` = '$userId'";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $BillName  =  $row["Bill_Intent"];
    }

    CreateFloder($BillName);
    $Pathname = 'tmp_image/' . date("Y") . "/" . date("m") . "/" . $BillName .'/';
    define('UPLOAD_DIR', $Pathname);
    //Set File Save To
    $filename = uniqid();
    $file = UPLOAD_DIR . $filename . '.png';
    //Set File name 
    file_put_contents($file, $Image);
    //Save File
    $pathlink = 'https://pcr-constuction.com/deploy/Puster%20Factory/'.$file;
    $sql_insertTran = "INSERT INTO `Bill_Transaction`( `ImagePath`, `Insert_Date`, `userToken`) VALUES ('$pathlink',('Select now()'),'$userId')";
    $result2 = $conn->query($sql_insertTran);
    if ($result2) {
        return $pathlink;
    }
}
