<?php     
date_default_timezone_set("America/New_York"); 
include 'autolog.php'; 
include 'targets.php'; 
include 'sqlCheck.php'; 
//include 'curlop.php'; //this is called in midlogin.php 

if (! empty($argv[1])) {
     include 'curlop.php';
     grade(); 
}
function grade() {
	/* task A : get all the tests with sub 1 */
	$target = targetIs('auto'); 
	$write = "[+] page accessed AUTOGRADER " . date("Y-m-d h:i:sa") . "\n"; 
	$write .= "********************************************************\n\n";
	$write .= "+ target file size of : " . $target . " = " . filesize($target) . "\n"; 
	autolog($write, $target); 
	if (filesize($target) >= 100000000) {
		autoclear($target); 
		$write = "+ the log reached 100  mb; it has been cleared \n"; autolog($write, $target); 
	}
	$arrayofTests = array(); $submittedTests = array(); 
	$bullet = array("type" => "autograder", "rels" => array("0", "1")); 
	if (! $hole = getExam2($bullet)) {
		$write = "error. failure to execute getexam\n"; 
		autolog($write, $target); 
	} else {
		$write = "+ getExam2() obtained :\n"; 
		$write .= print_r($hole, true) + "\n";
		$write .=  "+ decoding the json file from getExam2()\n"; 
		$hit = json_decode($hole, true); 
		$arrayofTests = $hit['tests'];  

		if (empty($hit)) { 
			$write = "+ getExam2 () returned null or empty.\n"; 
			autolog($write, $target); 
		}
	}

	$source = '/afs/cad/u/w/b/wbv4/public_html/Middle/firstpy.py'; 
	foreach ($arrayofTests as $key=>$x) {
		if($x['sub'] == 1) {
			/* check if this test is graded already */ 
			if (($check = checkGrade($x['id'])) == true ) {
				$write= "+ checkGrade returned true . skipping ". $x['id'] . "\n"; 
				autolog($write, $target); continue; 
			} else {
				$write= "+ checkGrade returned false. test: " . $x['id'] . " ungraded\n"; 
				$write= "+ " . $x['id'] . " will now be graded.\n"; 
				setGrade($x['id']); 
				autolog($write, $target); 

			}
			$temp = array('id' => $x['id'], 'ques' => $x['ques']); 
			array_push($submittedTests, $temp); 
    }	

  }//foreach array as x 

      if (empty($submittedTests)){
          $write = "+ array of submittedTests are empty, returning false and terminating.\n"; 
          autolog($write, $target); 
          return false; 
      }//if empty submittedTests

	$write = "+ an array of submittedTests: \n"; 
	$write .= print_r($submittedTests, true) . "\n";
	$write .= "+ proceed with task B: obtain test cases\n"; 
	autolog($write, $target); 

	/*task B : get all the testcases for each question from each sub testId */ 
	$write = "+ obtaining the array of ques from subm. tests\n";
	autolog($write, $target); 

	foreach ($submittedTests as $y) {
		$ques = $y['ques']; $id = $y['id']; 
		$qIds=array(); 
		foreach ($ques as $z) { array_push($qIds, $z['id']); }//foreach ques as z
		$bullet2 = array("type" => "autograder", "qIds" => $qIds, "testId" => $id); 
		$write = "+ sending paramaters to getAnswers() :\n"; 
		$write .= print_r($bullet2, true) . "\n"; autolog($write, $target); 
		$arrayofAnswers = array(); 
		if (! $hole2 = getAnswers2($bullet2)) {
			$write = "error; failure to execute getAnswer('bullet2')\n";
			autolog($write, $target); 
		} else { 
			$hit2 = json_decode($hole2, 'true'); 
			if (empty($hit2['answers'])) { 
				$write = "+ getAnswers(bullet2) answers array returned null or empty.\n";
				$write .= "+ continuing to the next test. \n";  
				autolog($write, $target); 
				continue; /* mv to next test */
			} else {
				$arrayofAnswers = $hit2['answers']; 
				$write = "+ getAnswers(bullet2) output and formed the arrayofAnswers: \n"; 
				$write .= print_r($hit2, true) . "\n"; 
				$write .= print_r($arrayofAnswers, true); autolog($write, $target); 
			}//if empty hit2 else 
		}//if hole2 getanswers 
	/*task C: insert function and output and text from each question for each test*/
		$counter=0;  
		foreach ($arrayofAnswers as $a) {
			$counter+=1; $write="counter is " . $counter . "\n"; autolog($write, $target); 
			$arrayofOuts = array(); 
			$qId = $a['qId']; $tests = $a['tests']; $text = $a['text']; 


  /* TASK F : check function name and colon before calling execom! */
  $write =  "+ TASK F is in session. checking function name and colon \n"; 
 	autolog($write, $target);  

      /*TASK F1: check for function name*/ 
      /* note: this has to happen inside of tests loop, IE within execom!  */ 

			$text = str_ireplace("\x0D", "", $text); 
			$write =  "+ clearning the python file for " . $qId . "\n"; autolog($write, $target); 
			autolog($write, $target); clear($source); 
		//	$write = "+ writing answer to python file : " . $text . "\n";  


			$cons = array(); $constat = false; 
			/* task E: check if the constraints are in the user answer */ 
			if (! $cons = getCons($qId)) {
				$write = "+ getCons(".$qId.") failed. cons returned empty.  \n"; 
        autolog($write, $target); 
        $constat = false; 
			} else {
				$write = "+ getCons (".$qId.") success:\n" . print_r($cons, true) . "\n"; 
				autolog($write, $target);
        $constat = true; 
				if (! $check = checkCons($text, $cons, $id, $qId)) {
					$write = "+ attempted to call checkCons() but failed at test ".$id.", qid " .$qId."\n"; 
					autolog($write, $target); 
				} else {
					$write = "+ checkCons() was succesful at test ".$id.", qid ".$qId."\n"; 
					autolog($write, $target); 
				}
      } //if cons getcons

      /* TASK F2: check for colon */ 
      if ($constat == true) {
             $colperc = '0.066'; 
      } else {
             $colperc = '0.10'; 
      }
      if ($ext = colonfixer($text)) {
					$write = "+ colon was not found in user answer\n"; 
          $write .= "+ new answer: \n " . $ext . "\n";
          autolog($write, $target); 
          $feed = "bp Colon not at end of function signature"; 
          update($id, $qId, $feed, $colperc , $colperc); 
          $text = $ext;
      } else {
			    $write = "+ colon was found in the user answer:\n";
          $write = $text . "\n"; 
          $feed = "gp Colon after function signature"; 
          autolog($write, $target); 
          update($id, $qId, $feed, '0' , $colperc); 
      }

      /* TASKG: run printkiller and get rid of print so that it doesnt kill our program; */ 

      if ($printkilled = printkiller($text)) {
             $text = $printkilled; 
      }//if printkilled text 

      /* TASKF1: check for func name */ 

				 if ($fun = funcom($text, $tests, $id, $qId, $constat)) {
				    $write = "+ replacing " . $text . " with " . $fun . "\n"; autolog($write, $target); 	
				   // $text = $fun; <-- this is wrong, why would u replace the answer with a function
					   $newtext = funrep($text, $fun);  
					  $write = "+ writing correct answer to python file: " . $newtext . "\n"; autolog($write, $target); 
            append($source, $newtext); //this should be the one with correct function! 
				 } else {
						 $write = "+ writing answer to python file: " . $text . "\n"; autolog($write, $target);
             append($source, $text); 
				 }//if fun funcom else 
			 
			foreach($tests as $b) {
				$write = "+ current testcase :  " .  $b . "\n";  
				$function = getFunc($b); $write .= "+ current function : " . $function . "\n"; 
				$printout = "print(" . $function . ")"; $write .= "+ printout : " . $printout . "\n"; 
				$write .= "+ obtaining the output part to be compared later.  \n"; 
        $output = getOut($b);
        if (empty($output)) {
            $write = "+ output was was detected empty at testcase: " . $b . "\n"; 
            autolog($write, $target);
            $output = "0"; 
        }
        array_push($arrayofOuts, $output);
				$write .= "+ formed the arrayofOuts : \n" . print_r($arrayofOuts, true) . "\n"; 
				$write .= "+ now we are writing the testcase function " . $function . " on the python file : \n"; 			   
				autolog($write, $target); append($source, $printout); 		 		
			}//foreach tests as b

			/* task D: run each testcase and compare (pass or fail) */ 
      // if (! $ex = execom($source, $tests, $arrayofOuts, $id, $qId)) {
      $exec_err = ""; // the error from execom. 
			if (! $ex = execom($source, $tests, $arrayofOuts, $id, $qId, $exec_err)) {
          $write = "+ execom failed. pls check logs. \n"; autolog($write, $target); 
         /* CHECK TASK HOTEL... 
				$write .= "+ calling updatePoints() to provide feedback\n"; 
				$feed = "bp user program failed to execute. "; 
				$write .= "+ " . $feed . "\n"; autolog($write, $target); 
				$bullet3  = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' =>         '.8', 'max' => '.80' ); 
				if (! $hole3  = updatePoints($bullet3)) {
					$write = "+error; failure to execute updatePoints('bullet3') for fail execom()\n"; 
					autolog($write, $target); 
					continue; 
				}
				$hole3  = json_decode($hole3); 
				$write = "+ updatePoints() : \n"; $write .= print_r($hole3, true) . "\n";
        autolog($write, $target); 
        */
			} else {
				$write = "+ execom was succesful. updatePoints() feedback\n"; 
				$feed = "np user program succesfully executed. "; 
				$write .= "+ " . $feed . "\n"; autolog($write, $target); 
				$bullet4 = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => '0', 'max' => '.80' );              
				if (! $hole4  = updatePoints($bullet4)) {
					$write = "+error; failure to execute updatePoints('bullet4') for fail execom()\n"; 
					autolog($write, $target); 
					continue; 
				}
				$hole4 = json_decode($hole4); 
				$write = "+ updatePoints() : \n"; $write .= print_r($hole4, true) . "\n";
				autolog($write, $target); 
      }//if ex execom source

      //SIX I relocated the cons checker before func name. 
      //

		}//foreach arrayofAnswers as a

		}//foreach submtests as y

	}	//grade() 

  function update($id, $qId, $feed, $subpoints, $max) {
		   $target = targetIs('auto'); 
       $bullet = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => $subpoints, 'max' => $max); 
       if (! $hole = updatePoints($bullet)) {
        	$write = "+ error. failure in updatePoints() in autograder.\n"; 
          autolog($write, $target); 
          return false;     
       } else {
          $hole = json_decode($hole); 
          return true; 
          $write = "+ updatePoints succeeded: \n"; $write .= print_r($hole, true) . "\n"; 
          autolog($write, $target); 
       }
  }//updatePoints
  
  function colonfixer($text) {
  /* returns the correct text that includes the colon =] */
		$pranto=')'; $col=':';

    $length = strlen($text);
    $end = stripos($text, PHP_EOL, 0); 
    if(! $end) $end=$length; 
    $func = substr($text, 0, $end); 
 
    if (($pos = stripos($func, $col, 0)) === false) {
        $start = stripos($text, $pranto, 0);
        $newtext = substr_replace($text, $col, $start+1, 0);
        $newtext = colonkiller($newtext, $end);
      return $newtext; 
    } else { 
      return false; 
    }
  }//colonfixer

  function colonkiller($text, $start) {
      /*destroys colons anywhere in the python body*/ 
      $target = substr($text, $start); 
      $for = strpos($target, 'for', 0);
      $while = strpos($target, 'while', 0);
      if ((!$for) && (!$while)) {
         $clearbody = str_replace(":", " ", $target);
         $newtext = substr_replace($text, $clearbody, $start);
         return $newtext;
      } else {
         return $text; 
      }
  }
  
	function getCons($qId) {
		/* returns an array of constraints */ 
		$target = targetIs('auto'); 
		$bullet = array("qId" => $qId);
		$tgt = "https://web.njit.edu/~wbv4/Middle/getCons.php";  

		$write = "+ gunCons() retrieving array of cons\n"; 
		$cons = curlop($bullet, $tgt); 
    $conson = json_decode($cons, true); 
		$write .= "+ array of cons: \n" .  print_r($conson['cons'], true) .  "\n"; 
		autolog($write, $target); 
		if (empty($conson['cons'])) {
			$write = "+ getCons() has empty cons array. returning 0.\n"; 
			autolog($write, $target); 
			return 0; 
		} else {
			return $conson['cons']; 
		}
	}//getCons

	function checkCons($text, $cons, $id, $qId) {
		/* checks if the cons are in the user answer */ 
		/* returns an array of feeds */ 
		$feeds = array(); 
		$target = targetIs('auto'); 
		$write = "+ checkCons() is checking if constraints are found in user answer\n"; 
		autolog($write, $target); 
		if (empty($cons)) {
			$write = "+ cons is empty. checkCons() terminating\n"; autolog($write, $target); 
			return false; 
    } else {
        $consize = count($cons); 
           $sub = .066  / $consize;  //if consize is 2, sub is 0.05; if 3, .03
           $max = .066  / $consize;  //if consize is 1, sub is 0.1 
           
			foreach($cons as $q) {
				if (($pos = stripos($text, $q)) ===  false) {
					$write = "+ checkCons() did not find " . $q . " in user answer\n"; autolog($write, $target); 
          $feed = "bp  " . $q . " was not found."; 
          if (stripos($q, "print") === false ) {
                $feed = "bp " . $q . " loop was not found."; 
          }
					$bullet = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => $sub, 'max' => $max ); 
					if (! $hole = updatePoints($bullet)) {
						$write = "+ error; checkCons failed to execute updatePoints()!\n"; 
						autolog($write, $target); return false;   
					} 
				} else {
					$write = "+ checkCons() found " . $q . " in user answer\n"; autolog($write, $target); 
          $feed = "gp " . $q . " was found."; 
          if (stripos($q, "print") === false) {
                $feed = "gp " . $q . " loop was found."; 
          }
					$bullet = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => '0', 'max' => $max); 
					if (! $hole = updatePoints($bullet)) {
						$write = "+ error; checkCons failed to execute updatePoints()!\n"; 
						autolog($write, $target); return false;  
					}
				}
			}//foreach cons as q 
			return true; 
		}
	}//checkcons() 

	function checkGrade($id) {
		/* return true if this test is graded already */
		$target = targetIs('auto'); 
		$write = "+ called checkGrade for testId . " . $id . "\n"; autolog($write, $target); 
		$bullet = array('type' => 'check', 'testId' => $id); 
		$tgt = "https://web.njit.edu/~wbv4/Middle/checkGrade.php"; 
		if (! $hole = curlop($bullet, $tgt)) {
			$write = "+ error: checkGrade() failed for testId " . $id . "\n"; 
			$write .= "+ contents: " . print_r($hole, true) . "\n"; 
			autolog($write, $target); 
			echo "+ checkGrade() failed for testId " . $id; 

		} else {
			$hit = json_decode($hole, true); 
			$write = "+ checkGrade() returned : \n" . print_r($hit, true) . "\n"; 
			autolog($write, $target); 
			if ($hit['grade'] == '1') { 
				return true; 
			} else {
				return false; 
			} 
		}
	}//checkGrade()

	function setGrade($id) {
		$target = targetIs('auto');
		$write = "+ called setGrade for testId " .  $id . "\n"; autolog($write, $target); 
		$bullet = array('type' => 'set', 'testId' => $id, 'grade' => '1'); 
		$tgt = "https://web.njit.edu/~wbv4/Middle/checkGrade.php"; 
		if (! $hole = curlop($bullet, $tgt)) {
			$write = "+ error: setGrade() failed for testId " . $id . "\n"; 
			$write .= "+ contents: " . print_r($hole, true) . "\n"; 
			autolog($write, $target); 
			echo "+ setGrade() failed for testId " . $id; 
		} else {
			$hit = json_decode($hole, true); 
			$write = "+setGrade() returned : \n" . print_r($hit, true) . "\n"; 
			autolog($write, $target); 
		}

		return 1; 

	}//setGrade(); 

	function execom($source, $tests, $arrayofOuts, $id, $qId) {
		/* takes in the python source file, gets each output, and compares to arrayofOuts */ 
		$target = targetIs('auto'); 
		$write = "+ running execom() with pars for id : " . $id . ", qId : " . $qId .  "\n";
		$test = "python " . $source . " 2>&1" ; 
		$write .= "+ execom command: " . $test . "\n";
    $write .= "+ python contents:\n"; 
    $contents = file_get_contents($source); 
    $write .= print_r($contents, true) . "\n"; 
    autolog($write, $target); 
    $size = sizeof($tests); //we need to count the testcases; and set the percentages. 
    $funperc = 80; 
    $write = "+ funperc was set to :" . $funperc . ", size: " . $size . "\n"; 
    $sub = ($funperc / $size) / 100; $max = ($funperc / $size) / 100; 
    $write .= "+ sub : " . $sub . ", max: " . $max . "\n"; autolog($write, $target); 
    $write = "+execom() calculated funperc: ".$funperc.", max: ".$max."\n"; 
            autolog($write, $target); 
		$exec = exec($test, $array, $status); 
    if (! $status ) { 
        $write .= "+ status of exec was 0. program executed succesfully\n"; 
        foreach($array as $key=>$c) {

				$write .= "+ comparing " . $tests[$key] . " with output : " . $arrayofOuts[$key] . "\n"; 
				$write .= "+ comparing c: " . $c . " with output : " . $arrayofOuts[$key] . "\n"; 
				$function = getFunc($tests[$key]);
				$output = getOut($tests[$key]);
        
				if (! isset($c)) {
					$write = "+ ".$c." is an empty or null output. skipping!\n"; autolog($write, $target); 
					continue; 
        }
       
				autolog($write, $target); 
				if ($c ===  $arrayofOuts[$key]) {
					$write = "pass!\n"; autolog($write, $target); 
					$write = "+ calling updatePoints() to provide feedback\n"; 
					/* g = good b = bad n = neutral */
					//	$feed = "gp testcase '". $tests[$key] . "' passed!"; 
					$function = getFunc($tests[$key]);
					//$output = getOut($tests[$key]);
					$feed = "gp Called " . $function . ", expected: \"" . $arrayofOuts[$key] . "\", got \"" . $c  ."\"" ;
					$write .= "+ " . $feed . "\n"; autolog($write, $target);   
          $bullet = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => '0',
              'max' => $max); 
					if (! $hole = updatePoints($bullet)) {
						$write = "+ error; failure to execute updatePoints('bullet')\n";
						autolog($write, $target); 
						continue; 
					} 
					$hole = json_decode($hole); 
					$write = "+ updatePoints() : \n"; $write .= print_r($hole, true) . "\n";
					autolog($write, $target); 				
				}//if c == arrayofOuts 
				else {
					$write = "fail!\n"; autolog($write, $target); 
					$write = "+ calling updatePoints() to provide feedback\n"; 
					// $feed = "bp testcase '" . $tests[$key] . "' failed!"; 
					$feed = "bp python called " . $function . ",  expected answer: " . $output . ", got user answer [" . $c . "]"; 
					$write .= "+ " . $feed . "\n"; autolog($write, $target); 
          $bullet = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => $sub,
              'max' => $max); 
					if (! $hole = updatePoints($bullet)) {
						$write = "+ error; failure to execute updatePoints('bullet')\n";
						autolog($write, $target); 
						continue; 
					} 
					$hole = json_decode($hole); 
					$write = "+ updatePoints() : \n"; $write .= print_r($hole, true) . "\n"; autolog($write, $target); 		
				}//if c == arrayofOuts else
			}//foreach array as c 
		}//if ! status
		else { //! status returned 1
        $write = "+ exec() failed.status returned 1. function did not match testcase or program syntax errors	\n"; 
        autolog($write, $target); 

        /* TASK HOTEL : negative feedback with stack trace! */ 
        $write .= "+ calling updatePoints() to provide stack trace for function fail. \n"; 
        $write .= "+ TASK HOTEL...\n"; 
        foreach($array as $key=>$e) {
           if ($key == 0) {
              $end = stripos($e, ",", 0);
              $e  = substr_replace($e, "python code", 0, $end);
           } else if ($key == 2) {
              continue; 
           }
           $err .= trim($e) . "\n"; 
        }
				$feed = "bp " . $err; 
				$write .= "+ " . $feed . "\n"; autolog($write, $target); 
				$bullet3  = array('testId' => $id, 'qId' => $qId, 'feedback' => $feed, 'subpoints' => '.8', 'max' => '.80' ); 
				/*subpoints should be a percent*/
				if (! $hole3  = updatePoints($bullet3)) {
					$write = "+error; failure to execute updatePoints('bullet3') for fail execom()\n"; 
					autolog($write, $target); 
					continue; 
				}
				$hole3  = json_decode($hole3); 
				$write = "+ updatePoints() : \n"; $write .= print_r($hole3, true) . "\n";
				autolog($write, $target); 
			return 0; 
		}//if ! status else
		$write = "+ execom() returned 1\n"; autolog($write, $target); 
		return true;  
	}//execom 

  function funcom($text , $tests, $id, $qId, $constat) {

    if ($constat) {
        $max = 0.066; 
        $subpoints = 0.066 ; 
    } else {
        $max = 0.1; 
        $subpoints = 0.1; 
    }
		$target = targetIs('auto'); 
		/* go through each test and obtain the function, string search the text for the function */
		// if (!  $eqpos = strPos($str, '=')) 
		$size = sizeof($tests);  
		$miss = 0; 
		$write = "+ operating funcom for userAnswer: " . $text . "\n"; autolog($write, $target); 
		foreach($tests as $x) {
			$function = getFunc($x); 
              $prant = '(';
              $length = sizeof($function);
              $end  = strpos($function, $prant, 0);
              $function = substr($function, 0, $end); 
			$write = "+ gp obtained function " . $function . " with funcom()\n"; autolog($write, $target); 
              $write = "+ finding if " . $function . " is in user answer " . $text  . "\n"; autolog($write, $target); 

      /*
			if (($pos = strPos($text, $function)) === false) {
				$miss += 1; 	
      } 
      */

       /* grab the function from user answer */
              $length = sizeof($text); 
              $start = stripos($text, "def", 0); $start+=4;
              $end = strpos($text, $prant, $start); 
              $userfunc = substr($text, $start, $end - $start); 

              if ((substr_compare($function, $userfunc, 0, 
                   strlen($userfunc)))) {
                   $miss += 1; 
               }

		}//foreach tests as x
		if ($miss >= $size) {
			$write = "+ function was not found at all in the user answer\n"; 
			$write .= "+ returning the correct function : " . $function . "\n"; autolog($write, $target); 
	                  		$feed = "bp Expecting function: \"".$function."\", found \"". wrongfunc($text) ."\"";
                        update($id, $qId, $feed, $subpoints, $max);  
                  			return $function; 
		} else {
			$write = "+ the function was found in the user answer\n"; autolog($write, $target); 
                        $feed = "gp Expecting function: \"".$function."\", found \"" .$function."\""; 
                        $subpoints = "0"; 
                        update($id, $qId, $feed, $subpoints, $max);  
			return 0; 
		}
	}//funcom

  function funrep($text, $function) {
       $target = targetIs('auto');  
       
       $write = "+ funrep() called, old answer: " .$text. "\n";    		 

       $prant='('; 
       $length = strlen($text);
       $start = stripos($text, "def", 0); $start+=4;
       $end = stripos($text, $prant, 0);
       $wrong = substr($text, $start, -($length - $end));
			 $right = $function; 
       $newtext = str_replace($wrong, $right, $text); 
      
       $write .= "+ funrep() called, new answer: " . $newtext. "\n"; 
       autolog($write, $target); 
       return $newtext; 
    
  }//funrep

	function getExam2($ammo) {
		$target = targetIs('auto'); 
		$tgt = 'https://web.njit.edu/~wbv4/Middle/getTest2.php';
		$proj = curl_init();
		curl_setopt($proj , CURLOPT_URL, $tgt);
		curl_setopt($proj , CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($proj , CURLOPT_POSTFIELDS, json_encode($ammo));
		curl_setopt($proj , CURLOPT_FOLLOWLOCATION, true);  
		if ( ! $recoil = curl_exec($proj)) {
			//if (curl_exec($proj) === false) 
			echo "type: getT;  curl_error:" . curl_error($proj) . "<br>";
			$write  = "type: getT; curl_error: " . curl_error($proj) . "\n"; 
			autolog($write, $target); 
		} else  {
			curl_close($proj); 
			return $recoil; 
		} 
	}//getExam()2 

	function getAnswers2($ammo) { 
		$target = targetIs('auto'); 
		$tgt = 'https://web.njit.edu/~wbv4/Middle/getAnswers.php';
		$proj = curl_init();
		curl_setopt($proj , CURLOPT_URL, $tgt);
		curl_setopt($proj , CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($proj , CURLOPT_POSTFIELDS, json_encode($ammo));
		curl_setopt($proj , CURLOPT_FOLLOWLOCATION, true);  
		if ( ! $recoil = curl_exec($proj)) {
			//if (curl_exec($proj) === false) 
			echo "type: getAnswer;  curl_error:" . curl_error($proj) . "<br>";
			$write  .= "type: getAnswer; curl_error: " . curl_error($proj) . "\n"; 
			autolog($write, $target); 
		} else  {
			curl_close($proj); 
			return $recoil; 
		}
	}//getAnswer()

	function updatePoints($ammo) {
		$target = targetIs('auto'); 
		$tgt = 'https://web.njit.edu/~wbv4/Middle/updatePoints2.php';
		$proj = curl_init();
		curl_setopt($proj , CURLOPT_URL, $tgt);
		curl_setopt($proj , CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($proj , CURLOPT_POSTFIELDS, json_encode($ammo));
		curl_setopt($proj , CURLOPT_FOLLOWLOCATION, true);  
		curl_setopt($proj , CURLOPT_HTTPHEADER, array('Accept: application/json'));
		curl_setopt($proj , CURLOPT_FAILONERROR, true); 
		curl_setopt($proj , CURLOPT_SSL_VERIFYPEER, FALSE); 
		curl_setopt($proj , CURLOPT_SSL_VERIFYHOST, FALSE); 
		curl_setopt($proj, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)'); 
		if ( ! $recoil = curl_exec($proj)) {
			//if (curl_exec($proj) === false) 
			echo "type: updatePoints;  curl_error:" . curl_error($proj) . "<br>";
			$write  = "type: updatePoints; curl_error: " . curl_error($proj) . "\n"; 
			autolog($write, $target); 
		} else  {
			//	curl_close($proj);
			return $recoil; 
		}
	}//updatepoints

	function getFunc($str) 
	{
		$target = targetIs('auto'); 
		if (!  $eqpos = strPos($str, '=')) {
			$write = "getFunc() failed;  eqpos was not found. \n";
			autolog($write, $target); 
		} else {
			if(! $func = substr($str,0,$eqpos)) {
				$write =  "substr failed in getFunc()  \n";
				autolog($write, $target); 
				return false;
			}
			trim($func);
			return $func; 
		}
	}

	function getOut($str) {
		$target = targetIs('auto'); 
		if(! $eqpos = strPos($str, '=')) {
			$write = "eqpos was not found in getOut(). \n"; 
			autolog($write); 
		} else {
			if (! $out  = substr($str, $eqpos+1, strlen($str))) {
				$write =  "substr failed at getOut()";
				autolog($write, $target); 
				return false;
			}
			trim($out); 
			return $out; 
		}
	}

	function clear($file) {
		$target = targetIs('auto'); 
		if (! $clean = fopen($file, 'w' )) {  //CLEAR THE FILE. 
			$write = "error: file failed to open file in clear()\n";
			autolog($write, $target);
		} else  {
			fwrite ($clean, "");
			fclose($clean); 
			return true; 
		}
	}//clear()

	function append($file, $input) {
		$target = targetIs('auto'); 
		if (! $target = fopen($file, 'a' )) {  
			$write =  "error: file failed to open file in append()\n";
			autolog($write, $target); 
		} else   {
			fwrite($target, PHP_EOL);
			fwrite($target, $input); 
			return true;
		}
  }//append()

  function printkiller($text) {
      /* detects print inside the function body 
        returns a new text with 'return' otherwise, false */ 
    
      $target = targetIs('auto'); 
      $write = "+ printkiller()  is called with text: " . $text . "\n"; 
          autolog($write, $target); 
      $length = strlen($text); 
      $start = stripos($text, ')', 0);
      $afterfunc = substr($text, $start, $length - 1);
      $pos = stripos($afterfunc, "print", 0);
          if ($pos === false) {
               return false; 
          } 
      $newafterfunc = str_replace("print", "return", $afterfunc); 
      $newtext = substr_replace($text, $newafterfunc, $start);
      $write = "+ printkiller() out: " . $newtext . "\n"; 
           autolog($write, $target); 
       
      return $newtext; 

  }//printkiller 

  function wrongfunc($text) {
      $target = targetIs('auto'); 
      $write = "+ wrongfunc() was called.\n";
      $prant='(';
      $length = strlen($text);
      $start = stripos($text, "def", 0); $start+=4;
      $end = stripos($text, $prant, 0);
      $wrong = substr($text, $start, -($length - $end));
      return $wrong;
      $write .= "+ wrongfunc() returned: " . $wrong . "\n"; 
      autolog($write, $target); 
  }//wrongfunc
	?>
