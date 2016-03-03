<?php
    /* domain checker */
    /* pings a site to make sure that it's up using curl, and tests the response to ensure it is not empty. */

    //which websites should we check?
    $sites = array(
        "http://google.co.uk",
    );
    //email address to send notifications to if 1 or more sites are down
    $toEmail = "email@example.com";

    /* set up the error log so we can log failures */
    ini_set("log_errors", 1);
    ini_set("error_log", "domain-checker.log");

    $start = microtime(true);
    $message="";

    foreach ($sites as $site)
    {
        //ping it - are we awake?

        //check, if a valid url is provided
        if (filter_var($site, FILTER_VALIDATE_URL))
        {
            //initialize curl
            $ch = curl_init($site);

            $options = array(
                CURLOPT_RETURNTRANSFER => true,             // return web page
                CURLOPT_HEADER         => false,            // don't return headers
                CURLOPT_FOLLOWLOCATION => true,             // follow redirects
                CURLOPT_MAXREDIRS      => 10,               // stop after 10 redirects
                CURLOPT_ENCODING       => "",               // handle compressed
                CURLOPT_USERAGENT      => "domain-checker", // name of client
                CURLOPT_AUTOREFERER    => true,             // set referrer on redirect
                CURLOPT_CONNECTTIMEOUT => 30,               // time-out on connect
                CURLOPT_TIMEOUT        => 30,               // time-out on response
            );
            curl_setopt_array($ch, $options);

            //get answer
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $errorNo  = curl_errno($ch);
            $errors   = curl_error($ch);

            //close connection
            curl_close($ch);

            //an array with the important parts of the response....
            $avail = array("status"=>$httpCode, "response"=>$response, "errorNo"=>$errorNo, "errors"=>$errors);
        }
        else
        {
            $avail = false; //domain checked was not valid
        }

        $updown = false;
        if ($avail['status']>=200 && $avail['status']<400 && !empty($avail['response']) && $avail['response'])
        {
            //as best we can tell, it's up - http response code is not a failure code, and response is non-empty.
            $updown = true;
        }
        else
        {
            //site seems to be down. add a new line to $message, which will be emailed out to $toEmail.
            $message.="\r\r".date("Y-M-D H:i:s")." : ".$site." : status ".$avail['status']." : ".($updown ? "UP" : "DOWN");

            if (empty($avail['response']) || !$avail['response'])
            {
                //was the response empty (may signify a server-side script issue)?
                //we should note that in the error message - add it to the end of the error string.
                if (strlen($avail['errors'])>0)
                {
                    //if errors are present, add a comma to separate at the end.
                    $avail['errors'].=", ";
                }
                $avail['errors'].="Empty Response";
            }

            //include more detail in the message, and log to the error log.
            $message.="\rError ".$avail['errorNo']. " :: ".$avail['errors'];
	        error_log(": ".$site." : ".$avail['status']." : ".($updown ? "UP" : "DOWN")." : ".$avail['errorNo']." : ".$avail['errors']);
        }
    }

    if ($message)
    {
        //there are downed sites to report. construct an email to send.
	    $message = "domain checker instance commencing ".date("Y-M-D H:i:s")."\r\r".$message;
	    $message.="\r\r".count($sites)." websites checked in ".(microtime(true)-$start)." seconds";
	    mail($toEmail, "domain checker results", $message);
    }

    exit(); // not really required but makes me feel better.
?>
