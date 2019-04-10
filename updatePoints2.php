<?php

include 'dblogin_interface.php';
include 'autolog.php';
include 'targets.php'; 
$response   = file_get_contents('php://input');
$decoder    = json_decode($response, true);

$target = targetIs('auto'); 

$write = "+ updatePoints() 2 was called. Received:  \n"; 
$write .= print_r($decoder, true) . "\n" ; autolog($write, $target); 


// $decoder = array('testId' => '1', 'qId' => '1', 'feedback' => 'testcase add(2,3)=5 failed', 'subpoints' => '0');
if (! $feedback = updatePoints($conn, $decoder)) { //calls the function getQUEST() ; 
		$error = "backend updatePoints() failed."; 
		$report = array("type" => "updatePoints", "error" => $error); 
		echo json_encode ($report); //terminate 
} else {
		echo $feedback; 
}

function updatePoints($conn, $decoder) {
    $target = targetIs('auto'); 
		$testId = $decoder['testId'];
		$qId = $decoder['qId'];
		$feedback = $decoder['feedback']; 
		$feedback = addslashes($feedback); 
		$subpoints = $decoder['subpoints']; 
		
		$sql = "INSERT INTO Feedback (testId, questionId, feedback) VALUES ('$testId', '$qId', '$feedback')";
		if (! $result = $conn->query($sql)) {
				$sqlerror = $conn->error; 
				$error .= "sql " . $sqlerror . " "; 
			 $write = "updatePoints SQL : " . $error . "\n"; autolog($write, $target);  
		}//if result conn query	

		/* task A : get the points for this current test and question */ 

		$sql2 = "SELECT points FROM QuestionStudentRelation WHERE testId = '$testId' AND questionId = '$qId'"; 	  if (! $result2 = $conn->query($sql2)) {
				$sqlerror2 = $conn->error; 
				$error2 .= "sql2 " . $sqlerror2 . " "; 
			 $write = "updatePoints SQL : " . $error . "\n"; autolog($write, $target);  
		} else { 
			    while($row  = mysqli_fetch_assoc($result2)) {
    	    	  $points = $row['points'] - $subpoints; 			  
					}
    }

	  /* task B : submit the new points to the database */ 
	
	  $sql3 = "UPDATE rd248.QuestionStudentRelation SET points = '$points' WHERE QuestionStudentRelation.questionId = '$qId' AND QuestionStudentRelation.testId = '$testId' "; 
		if (! $result3 = $conn->query($sql3)) {
				$sqlerror3 = $conn->error; 
	   $error3 .= "sql3 " . $sqlerror3 . " "; 
			 $write = "updatePoints SQL : " . $error . "\n"; autolog($write, $target);  
	  }
   
	 
		if(empty($error)) {
				$error = 0; 
		}
	
  $write = "+ updatePoints()2 returned error: \n"; 
  $write = "error: " . $error . "\n"; 
	 autolog($write, $target); 
  $feedback = stripslashes($feedback); 
		$package = array("type" => "updatePoints", "error" => $error, "testId" => $testId, "qId" => $qId, "feedback" => $feedback, 'points' => $points);
		return json_encode($package);

} //updatePoints
?>
