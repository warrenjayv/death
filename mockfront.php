
<?php
/*
//this is a sample question. curl to mid to test.
echo "sample question (curl to backend) ... " ."<br>"; 
$question = "are you okay?"; 
$answer1 = "yes";
$answer2 = "no"; 
$payload = array("question" => $question, "answers" => array($answer1 , $answer2)); 
$url = "https://web.njit.edu/~wbv4/Middle/midlogin.php"; 
$cart = launchIT($url, $payload);
echo $cart; 
echo "<br><br>"; 
*/
//curl to backend.
/*
$user = "student";
$pass = "stu"; 
*/
$url = "https://web.njit.edu/~wbv4/Middle/midlogin.php"; 
$user = "teacher"; 
$pass = "teach";
$payload2 = array("username" => $user, "password" => $pass); 
echo "testing teacher  account.. <br>"; 
$cart2  = launchIT($url, $payload2 ); 
echo $cart2;

echo "<br><br>"; 


function launchIT($dest, $load)  { 
 $round = curl_init(); 
  curl_setopt($round, CURLOPT_URL, $dest);
  curl_setopt($round, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($round, CURLOPT_POSTFIELDS, json_encode($load)); 
  curl_setopt($round, CURLOPT_FOLLOWLOCATION, true); 
if (curl_exec($round) == false)
       echo "error detected: " . curl_error($round) . "<br>";
if (curl_errno($round)){
       echo "error request: " . curl_error($round) . "<br>"; 
}

$recoil = curl_exec($round); 

curl_close($round); 
return $recoil; 
}
/*
worklog 02.27.19

retrieve a question for the database; put them in the question bank. 

worklog 02.22.19

this program is designed as a mock front to send a payload to midlogin.php. 
this payload is a JSON file that will have a question in it.

0616: CURL the payload to midlogin.php.
*/
?>

