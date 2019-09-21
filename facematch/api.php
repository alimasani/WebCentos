<?php

header("Access-Control-Allow-Orgin: *");
header("Access-Control-Allow-Methods: *");
header("Content-Type: application/json");

$serverMethod = $_SERVER['REQUEST_METHOD'];

if($serverMethod == 'POST'){
    
    $headerAuth = getallheaders();
    
    // validaate api key
    if($headerAuth['Api-Key']=="f671dd0a8c37e69ab60730dfd332654e"){
        if (!extension_loaded("facematch")) {
    	 	http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"Failed to load FACEMATCH extension.");
            echo json_encode($response);
            return;
    	}
    
    	
    	$allowedExt= array('jpg','jpeg','png','gif');
    	
    	/// image 1
    	
    	$file1 =$_FILES['image1']['name'];
    	
        $fileSize1=$_FILES['image1']['size'];
        $fileTmp1= $_FILES['image1']['tmp_name'];
        $fileExt1 = strtolower(pathinfo($file1,PATHINFO_EXTENSION));
        
        if(in_array($fileExt1,$allowedExt) === false)
        {
            http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
            echo json_encode($response);
            return;
        }
    
        if($fileSize1 > 2097152)
        {
            http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"File size must be under 2mb.");
            echo json_encode($response);
            return;
    
        }
        
        $type1 = strtolower(pathinfo($file1,PATHINFO_EXTENSION));
        $data1 = file_get_contents($fileTmp1);
        $imagestr1 = base64_encode($data1);
        
        /// image 2
        
        $file2 =$_FILES['image2']['name'];
    	
        $fileSize2=$_FILES['image2']['size'];
        $fileTmp2= $_FILES['image2']['tmp_name'];
        $fileExt2 = strtolower(pathinfo($file2,PATHINFO_EXTENSION));
    
        
        if(in_array($fileExt2,$allowedExt) === false)
        {
            http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"Invalid file type. Only JPG, JPEG, PNG & GIF files are allowed.");
            echo json_encode($response);
            return;
        }
    
        if($fileSize2 > 2097152)
        {
            http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"File size must be under 2mb.");
            echo json_encode($response);
            return;
    
        }
        $type2 = strtolower(pathinfo($file2,PATHINFO_EXTENSION));
        $data2 = file_get_contents($fileTmp2);
        $imagestr2 = base64_encode($data2);
    	
    	
    	
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
        	http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"License file is missing.");
            echo json_encode($response);
            return;
        }
    	
        $engine = new FaceMatch($model, $modelproto, $license);
        if ($engine == null){
        	http_response_code(400);
            $response = array("responseType"=>"Error","response"=>"Failed to initialize FACEMATCH engine.");
            echo json_encode($response);
            return;
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
    
        http_response_code(200);
        $response = array("responseType"=>"success","response"=>$ret);
    	echo json_encode($response);    
    }else {
        http_response_code(400);
        $response = array("responseType"=>"Error","response"=>"Invalid Api Key");
        echo json_encode($response);
        return;
    }
    
        
}else {
    http_response_code(405);
    $response = array("responseType"=>"Error","response"=>"Method not allowed");
    echo json_encode($response);
}


?>
