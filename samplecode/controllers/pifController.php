<?php
/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
/**
 * Description of fullContactEntryPoint
 *
 * @author Jasen
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/samplecode/controllers/dbController.php';
class pifController extends dbController {
    public function __construct() {
        //Create instances of authorCoreTools to create a guid
        parent::__construct();

        $this->properties['angularDrvPath'] = "https://jasenw.net/samplecode/controllers/pifApp.js";
        $this->properties['css']            = "https://jasenw.net/samplecode/styles/main.css";
        $this->properties['apiURL']         =  'https://api.fullcontact.com/';
        $this->properties['apiVersion']     =  'v2';
        $this->properties['apiQueryVar']    =  array(
                'email'     =>      'email',
                'key'       =>      'apiKey',
                'entryPoint'=>      'person.json',
            );
        $this->properties['author']         =  'Jasen Ward';
        $this->properties['title']          =  'Profile Image Finder';
        $this->properties['description']    =  'This project will look up an image from the Full Contact Application.';
        $this->properties['version']        =  '2.201909261417';
        $this->properties['verbose_log']    =   false;
        
        //initialize error library array
        $this->messages=array(
            'invalidEmail'          => 'The email address was not valid. Check the spelling and punctuation. ',
            'validResponse'         => 'Below is an image associated with the searched email. ',
            'invalidResponse'       => 'This application did not understand or get a response. ',
            'usingCachedResponse'   => 'Using cached response. ',
            'usingAPIResponse'      => 'Using API response. ',
            'invalidCall'           => 'The call to Full Contact API failed. ',
        );
        
        //Establish connection to the caching database
        $this->main();
    }

    /**
     * @author jasen ward, <jasenward@gmail.com>
     * @param string $emailQuery This is an email string from the page entry point
     */
    public function callFullContact($emailQuery='default'){
        //****************************************************
        //Initialize result array
        //****************************************************
        $result['result']= true;
        
        //Initialize Cache
        $result['cached']=false;
        $result['src']='';
        
        //Initialize Response
        $result['message']='';
        $result['content']='';
        $result['curl']='';

        $cache = $this->pullCache($emailQuery);
        if($cache['result']){
            $result['cached']=true;
            $result['cached_url']=$cache['cached_url'];
        }else {
            //
            //$token = $this->token;
            $url = $this->constructAPIURL($emailQuery);

            // Set up cURL
            $ch = curl_init();
            // Set the URL
            curl_setopt($ch, CURLOPT_URL, $url);
            // don't verify SSL certificate
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
            // Return the contents of the response as a string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // Follow redirects
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
            // Set up authentication
            //curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            //curl_setopt($ch, CURLOPT_USERPWD, "$token:X");

            // Do the request
            $response = curl_exec($ch);
            curl_close($ch);

            $result['content'] = $response;
            $result['curl'] = $url;
        }
            //Parse Response of Full Contact
        //perform final validation of the profile image call before replying
        $validation=$this->validateResponse($result);
        $result['result']=$validation['result'];
        $result['src']=$validation['src'];
        
        if($result['result']){
            //Cache the result
            if($result['cached']===false){
                $this->putCache($emailQuery, $result['src']);
            }
            $result['message'] .=$this->messages['validResponse'].' '.$validation['message'];
        }else{
            $result['message'] .=$this->messages['invalidResponse'];
        }

        //Log final Response before returning reply
        if($this->properties['verbose_log']){
            error_log("Request for image file made. Compiling response based on...");
            error_log(print_r($result,true));
        }

        //assemble reply for Angular
        return $result;
    }    

    /**
     * Get configuration information that is protected
     * and should never be logged or exposed
     * @return string
     */
    protected function getPrivateAPIKey(){
        require ($_SERVER['DOCUMENT_ROOT'].'/samplecode/web-config.php');
        return $dbconfig['apiKey'];
    }
    
    
    /**
     * Extract the image url from the response object and return it as
     * a string
     * @return string
     */
    public function getImageURL($jsonObj){
        return $jsonObj['photos'][0]['url'];
    }
    
    /**
     * This will construct the url to be called for the remote service
     * @param string $emailToQuery
     * @return string
     */
    public function constructAPIURL($emailToQuery){
        $result= $this->properties['apiURL']
                .$this->properties['apiVersion'].'/'
                .$this->properties['apiQueryVar']['entryPoint']
                .'?'.$this->properties['apiQueryVar']['key'].'='.$this->getPrivateAPIKey()
                .'&'.$this->properties['apiQueryVar']['email'].'='.$emailToQuery;
        return $result;
    }
    
    /**
     * Is the string passed valid JSON?
     * @author Jasen Ward, <jasenward@gmail.com>
     * @param string $str
     * @return boolean
     */
    public function pif_isJSON($str){
        // decode the JSON data
        // set second parameter boolean TRUE for associative array output.
        $result = is_string($str) 
                    && is_array(json_decode($str, true)) 
                    && (json_last_error() == JSON_ERROR_NONE);
        
        return $result;
    }
    
    /**
     * Return either the desired response attribute or an error string
     * @param array $ar
     * @return array
     */
    public function validateResponse($response){
        $result=array(
            'result'=>false,
            'src'=>'',
            'message'=>'',
        );
        
        if($response['cached']){
            $result['result']=true;
            $result['src']=$response['cached_url'];
            $result['message']=$this->messages['usingCachedResponse'];
        }elseIf($this->pif_isJSON($response['content'])){
            //Objectify the json string and parse it to get the image url
            $result['result']=true;
            $jsonObj = json_decode($response['content'], true);
            $result['src']=$this->getImageURL($jsonObj);
            $result['message']=$this->messages['usingAPIResponse'];
        }else{
            $result['message']=$this->messages['invalidResponse'];
        }
        
        return $result;
    }

    /**
     * This will purge the database cache table named pifcache
     * of data older than 1 day
     * @author Jasen Ward <jasenward@gmail.com
     * @return array
     */
    public function purgeCache(){
        $results=array(
            'result' => false,
            'message'=> ''
        );
        
        //set up conditional
        $now = date('Y-m-d h:m:s', strtotime("-1 days"));
        
        //build query and execute
        $stmt='DELETE FROM pifcache WHERE date < ?';
        $statement=mysqli_prepare($this->connection, $stmt);
        mysqli_stmt_bind_param($statement, 's', $now);
        mysqli_stmt_execute($statement);
        
        //if error report it.
        if($this->connection->connect_error){
            $stmreadadble=$stmt .' Parm Passed '. $now; 
            $results['message'] = ' Error Codes Encountered in Query: '.$stmreadadble. ' ' 
                        .print_r($this->connection->connect_errno,true)
                        .$this->connection->error;
        }else{
            $results['result']  = true;
            $results['message'] = $stmt.' Purged: '.$this->connection->affected_rows.' records older than '. $now;
        }
        
        //close statment and free resources
        mysqli_stmt_close($statement);

        return $results;
    }
    
    /** This will put the results of a single query of the API into the cache
     * 
     * @param type $strEmail    Email as a string of the query
     * @param type $strURL      URL as extracted from the getImageURL function
     * @return array
     */
    public function putCache($strEmail, $strURL){
        $results=array(
            'result' => false,
            'message'=> ''
        );
        
        //Date
        $now = date('Y-m-d h:m:s', time());
        
        //build query and execute
        $stmt='INSERT INTO pifcache VALUES (? ,? ,? ,?)';
        $statement=mysqli_prepare($this->connection, $stmt);
        mysqli_stmt_bind_param($statement, 'ssss', $this->properties['instance'],$strURL, $strEmail, $now);
        mysqli_stmt_execute($statement);
        
        //if error report it.
        if($this->connection->connect_error){
            $results['message'] = $stmt.'('
                                    .$this->properties['instance']. ' ,'
                                    .$strEmail.' ,'
                                    .$strURL.' ,'
                                    .$now.')'
                                    .') Affected '.mysqli_stmt_affected_rows($statement).' rows.'
                                  . ' Checking Codes Encountered in Query: <pre>'.print_r($this->connection,true).'</pre>';
        }else{
            $results['result']  = true;
            $results['message'] = $stmt.'('
                                    .$this->properties['instance']. ' ,'
                                    .$strEmail.' ,'
                                    .$strURL.' ,'
                                    .$now.')'
                                    .') Affected '.mysqli_stmt_affected_rows($statement).' rows.';
        }
        
        //close statment and free resources
        mysqli_stmt_close($statement);

        return $results;
    }    
    
    /** This will read from the cache to return the most recent entry that uses
     *  the queried email as a match
     * 
     * @param string $strEmail email string to look up in the table
     * @return array
     */
    public function pullCache($strEmail='default'){
        $result=array(
            'result'    => false,
            'message'   => '',
            'cached_url'   => '',
        );
        
        //Build Query
        $stmt='SELECT id, url FROM pifcache WHERE email = ? LIMIT 1';
        $statement=mysqli_prepare($this->connection, $stmt);
        mysqli_stmt_bind_param($statement, 's', $strEmail);
        mysqli_stmt_execute($statement);
        
        //Error Handle
        //if error report it.
        if($this->connection->connect_error){
            $result['message'] =$stmt.' Resulted in error for query of ('.$strEmail.'): '.print_r($this->connection->connect_errno,true)
                                .$this->connection->error;
        }else{
            $result['message'] = $stmt." Resulted in ".$this->connection->affected_rows;
            error_log($result['message']);
            //Bind Database Reply Column
            $id = NULL;
            $url = NULL;
            $statement->bind_result($id, $url);
            $i=0;
            while ($statement->fetch()){
                $i++;
                if(strlen($result['cached_url'])===0){
                    error_log("Retrieving cached url: ".$url);
                    $result['cached_url'] = $url;
                }
            }            
        }
        
        //Verify that there is one row
        if(strlen($result['cached_url'])>0){
            $result['result']=true;
        }
        
        //close statment and free resources
        mysqli_stmt_close($statement);
        
        return $result;
    }

}