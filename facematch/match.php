<?php
	if (!extension_loaded("facematch")) {
	 	exit("skip facematch extension not loaded");
	}

	
	$imagestr1 = $_REQUEST['imgLeft'];
	$imagestr2 = $_REQUEST['imgRight'];
	$image1 = "";
	$image2 = "";
	

	if ($imagestr1 != "") {
		$image1 = base64_decode($imagestr1);
	}

	if ($imagestr2 != "") {
		$image2 = base64_decode($imagestr2);
	}
	
	//load database
	$model = file_get_contents("db/model.nn");
    $modelproto = file_get_contents("db/modelproto.nn");
    $license = file_get_contents("db/accuraface.license");
    if ($license == null)
    {
    	ob_end_clean();
    	exit("Could not find file './db/accuraface.license' <br /> License Key Missing");
    }
	    
    $engine = new FaceMatch($model, $modelproto, $license);
    if ($engine == null){
    	exit("facematch engine creation failed!");
   	}  	

	$ret = $engine->doMatchProcess($image1, $image2);
	
	
	$ret["message"] = sprintf("%0.1f %%", $ret["score"] * 100);
	
	if ($ret["score"] == -100){//license failed
		$resDev = json_decode($engine->getDevInfo());
        	$strHDD = $resDev->{'HDD'};
        	$strDomain = $resDev->{'Domain'};
		
		$strErr = $engine->getErrorMsg();
		if (strpos($strErr, 'Invalid') === false)
			$ret["message"] = $strErr;
		else
			$ret["message"] = "Your HDD Serial Key is ".$strHDD."<br />Your Domain is ".$strDomain."<br /><br />".$strErr;
	}
	
	$ret["index"] = $_REQUEST['index'];
	if ($ret["retimg1"] != "") {
		$ret["retimg1"] = "data:image/jpg;base64," . base64_encode($ret["retimg1"]);
	}
	if ($ret["retimg2"] != "") {
		$ret["retimg2"] = "data:image/jpg;base64," . base64_encode($ret["retimg2"]);
	}

	echo json_encode($ret);
?>
