<?php
/*
 * This class performs a simple connection to the hosting databse and 
 * stores information fed to the service into the database, purges old 
 * data and returns an array of current rows in the database for the
 * caller.
 * @author Jasen Ward
 * @since July 2018-07-05
 */
class dbController{
    public function __construct($instance='default'){
        if($instance==='default' || strlen($instance)==0){
            $instance=$this->guid();
        }
        $this->properties = array(
            'name'      => 'Database Controller',
            'version'   =>  2,
            'description'=> 'The default database connection class.',
            'instance'  =>  $instance,
            'errorState'=>  false,
        );
        $this->dataType = 'NONE';
        $this->connection='';
        $this->maxRows=10;
    }
    
    /**
     * This is a standard guid function, used for years. No need to rewrite.
     * All I have done to it is to remove the {} symbols since this guid is not
     * being represented as a JSON object, but instead will double as the instance
     * ID and the record id. This is the stub that would allow for guaranteed
     * delivery since the promise can be tracked to the execution log directly 
     * by the id.
     * 
     * @author http://php.net/manual/en/function.com-create-guid.php#52354
     * @return string
     */
    function guid(){
        $result = '';
        if (function_exists('com_create_guid')){
            $result = com_create_guid();
        }else{
            mt_srand((double)microtime()*10000);//optional for php 4.2.0 and up.
            $charid = strtoupper(md5(uniqid(rand(), true)));
            $hyphen = chr(45);// "-"
            $uuid = chr(123)// "{"
                    .substr($charid, 0, 8).$hyphen
                    .substr($charid, 8, 4).$hyphen
                    .substr($charid,12, 4).$hyphen
                    .substr($charid,16, 4).$hyphen
                    .substr($charid,20,12)
                    .chr(125);// "}"
            $result = rtrim(ltrim($uuid, "{"),"}");
        }
        
        return $result;
    }

    /**
     * This will attempt to identify and parse the parameters of the subscriber
     * into data elements that can be validated by the service.
     * 
     * @author Jasen Ward <jasenward@gmail.com>
     * @param $type string  If not "default" then special handling is required
     *                      'pif' validate the email address in $data['email']
     * @return array
     */
    function parseData($type='default'){
        //What type of data and structure is in the request?
        if($type==='PIF'){
            $expectedCount=1;
            $checkEmail=true;
            $data=array(
                array(
                    'column'=>  'email',
                    'type'  =>  'string',
                    'value' =>  '',
                ),
            );
        }else{
            $expectedCount=3;
            $checkEmail=false;
            $data=array(
                array(
                    'column'=>  'first_name',
                    'type'  =>  'string',
                    'value' =>  'First',
                ),
                array(
                    'column'=>  'last_name',
                    'type'  =>  'string',
                    'value' =>  'Last',
                ),
                array(
                    'column'=>  'company_name',
                    'type'=>    'string',
                    'value'=>   'Company',
                )
            );
        }

        //Is POST JSON?
        $received = $this->is_json($_POST);

        //POST OR POST EMPTY and FILE
        if(isset($_POST) && $type!='PIF') {
            if(!$received){
                if(count($_POST)==0){
                    $this->dataType='FILE';
                }else{
                    $this->dataType='POST';
                }
            }else{
                $this->dataType='JSON';
            }
        //GET
        }elseif(isset($_GET) && $type!='PIF'){
            $this->dataType='GET';
        //None
        }else{
            if($type==='PIF'){
                $this->dataType=$type;
            }else{
                $this->dataType = 'NONE';
                $this->properties['errorState']=true;
            }
        }

        //Encode data by type
        switch ($this->dataType){
            //Requires exactly 3 values in order of first_name, last_name, company_name
            case 'GET':
                $g=0;
                foreach ($_GET as $key => $value){
                    if($g<3){
                        $data[$g]['value'] = htmlspecialchars($value);
                    }
                    $g++;
                }
                break;
            //Requires correctly formatted JSON for data in format of above data array
            case 'FILE':
                $file = json_decode(file_get_contents('php://input'), true);
                if(strlen($file)>0){
                    $data=json_decode($file);
                //File is empty string = no data
                }else{
                    $this->properties['errorState']=true;
                    $this->dataType='NONE';
                }
                break;
            //Requires correctly formatted JSON data in form of "array{fName=>string, lName=>string, cName=string)
            case 'JSON':
                $data[0]['value'] = $received['fName'];
                $data[1]['value'] = $received['lName'];
                $data[2]['value'] = $received['cName'];
                break;
            //Requires exactly 3 values in order of first_name, last_name, company_name
            case 'POST':
                $p=0;
                foreach ($_POST as $key => $value){
                    if($p<3){
                        $data[$p]=$value;
                    }
                    $p++;
                }
                break;
            case 'PIF':
                $data[0]['value']=$received['email'];
                break;
            case 'None':
                break;
            default:
                break;
        }

        //validate the data
        $result = $this->validate($data, $expectedCount, $checkEmail);

        return $result;
    }
    
    /**
     * Quick and dirty test of a json string submission
     * @author Jasen Ward, <jasenward@gmail.com>
     * @param type $str
     * @return boolean|stdClass
     */
    function is_json($str){
        // decode the JSON data
        // set second parameter boolean TRUE for associative array output.
        $result = is_string($str) 
                    && is_array(json_decode($str, true)) 
                    && (json_last_error() == JSON_ERROR_NONE);
        if ($result) {
            // JSON is valid
        }elseif(gettype($str)=='array'){
            //Due to oddities with PHP/Angular is the POST Array Key a JSON string?
            foreach ($str as $key=> $value){
                $angularJSON = json_decode($key);
                $result['fName']=$angularJSON->fName;
                $result['lName']=$angularJSON->lName;
                $result['cName']=$angularJSON->cName;
                $result['email']=str_replace(":",".",$angularJSON->email);
            }
        }else{
            // JSON error encountered
            $result=false;
        }
        
        return $result;
    }
    
    
    /**
     * Make a connection to the local database with protected method to 
     * prevent leaking of password credentials via service dumps, etc.
     * 
     * @author Jasen Ward
     * @return array    in the form of 'version(str), message(str), active(connobj), errorstate(bool)'
     */
    private function connect_db(){
        require ($_SERVER['DOCUMENT_ROOT'].'/samplecode/web-config.php');
        $result= array(
            'version'=>$dbconfig['version'],
            'message'=>'',
            'active'=>'',
        );
        
        $mysqli = new mysqli($dbconfig['host'], $dbconfig['user'], $dbconfig['password'], $dbconfig['database']);
        if ($mysqli->connect_errno){
            $this->properties['errorState']=true;
            $result['message'] = "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
            error_log("Connection Failure in [".$this->properties['instance']."]".$result['message']);
        }else{
            $this->properties['errorState']=false;
            $this->connection=$mysqli;
            $result['active']=true;
            $result['message']="Successful Connection made to: ".$mysqli->host_info;
        }

        return $result;
    }
    
    /**
     * This can parse two queries depending on input and data. in this sample,
     * no parity is guaranteed between the data types as that would put into a 
     * production code and all variables are controlled at input. Since this is
     * a sample only, further development to tighten these types of holes is 
     * implied. Also, there would be more error correction/handling here to 
     * guarantee preparation, execution and delivery, but this is a sample only 
     * and simplicity is expected to show minimum viable functionality.
     * 
     * @author Jasen Ward
     * @param number $type This can be 1 for Selects and 2 for Insert, select is default
     * @param array $data in format of validated
     */
    function queryDB($type=1,$data){
        $result=array(
            'result'=>false,
            'message'=>'',
            'dataOut'=>array(),
        );
        
        //Prepare Statement
        switch ($type){
            case 1:
                //Prepare Select Statement
                $stmt='SELECT id, first_name, last_name, company, date_visited from visitor_log LIMIT ?';
                $stmreadadble='SELECT id, first_name, last_name, company from visitor_log LIMIT '.$this->maxRows;
                $statement=mysqli_prepare($this->connection, $stmt);

                if(false === $statement){
                    error_log('SELECT prepare() failed: ' . htmlspecialchars($this->connection->error));
                }else{
                    //Bind Parameters
                    mysqli_stmt_bind_param($statement, 'i', $this->maxRows);
                }

                break;
            case 2:
                //Prepare Insert Statement
                $date_visited = new DateTime();
                $stmt='INSERT INTO visitor_log VALUES (?,?,?,?,?)';
                $stmreadadble='INSERT INTO visitor_log VALUES ('
                                . $this->properties['instance'].','
                                . $data['data'][0]['value'].','
                                . $data['data'][1]['value'].','
                                . $data['data'][2]['value'].','
                                . $date_visited->format('Y-m-d H:i:s').')';
                $statement=mysqli_prepare($this->connection, $stmt);

                if(false === $statement){
                    error_log('INSERT prepare() failed: ' . htmlspecialchars($this->connection->error));
                }else{
                    //Bind Parameters
                    mysqli_stmt_bind_param($statement, 'sssss', $this->properties['instance'] , $data['data'][0]['value'],$data['data'][1]['value'],$data['data'][2]['value'],$date_visited->format('Y-m-d H:i:s'));
                }

                break;
            case 3:
                //Prepare Delete Statement
                $stmt='DELETE FROM visitor_log';
                $stmreadadble=$stmt;
                $statement=mysqli_prepare($this->connection, $stmt);
            default:
                break;
        }
        
        //Execute and Return Result
        mysqli_stmt_execute($statement);
        
        if($type==1){
            $id = NULL;
            $first = NULL;
            $last = NULL;
            $company = NULL;
            $statement->bind_result($id, $first, $last, $company, $date_visited);
            $i=0;
            while ($statement->fetch()){
                $i++;
                array_push($result['dataOut'],
                        $first. " ". $last. " from ". $company. " visited on ". $date_visited. "."
                );
            }
        }

        $result['result']=true;
        $result['message']="Query Sent: [".$stmreadadble."]";
        
        switch($type){
            case 1: 
                $result['message'].= "Found [.". $i.'] rows of data.';
                //self purge the database since we don't want to just keep this forever in a demo
                if($i>=10){
                    $this->queryDB(3,null);
                }
                break;
            case 2:
                $result['message'].= "Inserted [". mysqli_stmt_affected_rows($statement)."] rows of data.";
                break;
            default:
                break;
        }

        /* close statement and connection */
        //mysqli_stmt_close($statement);
        
        return $result;
    }
    
    /**
     * 
     * @return array
     */
    function main(){
        $result = $this->connect_db();
        return $result;
    }
    
    /**
     * Validates the data to be sent to the service
     * @author Jasen Ward
     * @param array $args Data points from the parameters in format of 
     *                      array(
     *                          array(
     *                              'col'=>''
     *                              'type'=>'',
     *                              'value'=>''.
     *                          ),
     *                      )
     * @param number $count How many data points are required?
     * @param boolean $checkAnEmail is the data an email address to be checked?
     * @return array
     */
    function validate($args,$count, $checkAnEmail=false){
        $result=array(
            'result'=>true,
            'counted_args'=>0,
            'data'=> $args,
            'message'=>'',
        );
        
        //Check count
        if(!$this->properties['errorState']){
            $result['counted_args']=count($args);
            if($result['counted_args'] === $count){
                $result['result']=true;
            }else{
                $result['result']=false;
            }
        }else{
            $result['result'] = false;
        }

        
        //Check Keys of first array to confirm structure
        if(!$result['result']){
            $result['message'] = "The number of arguments [".$result['counted_args']." did not match the expected count [".$count."].";
        }else{
            foreach ($args as $a){
                !array_key_exists('column',$a) ? $result['result']=false : true;
                !array_key_exists('type',$a) ? $result['result']=false : true;
                !array_key_exists('value',$a) ? $result['result']=false : true;
            }
        }
        //Check length
        if (!$result['result']){
            $result['message'] = 'One or more the keys in the data entry arrays did not exist as expected. Each data entry array must contain a column/type/value key.';            
        }else{
            foreach ($args as $b){
                if(strlen($b['value'])>0){
                    $result['result']=true;
                }
            }
        }
        
        //Check data type
        if (!$result['result']){
            $result['message'] = 'One or more values in the data entries was empty. This is not allowed.';            
        }else{
            foreach ($args as $c){
                if(strlen($c['type'])>0){
                    switch($c['type']){
                        case 's':
                            if(!gettype($c['value']=='string')){
                                $result['result']=false;
                                $result['message']='The value '.$c['value'].' is not a string as expected.';                         
                            }
                            break;
                        case 'n':
                            if(!gettype($c['value']=='number')){
                                $result['result']=false;
                                $result['message']='The value '.$c['value'].' is not a number as expected.';                         
                            }
                            break;
                        default:
                            break;
                    }
                }else{
                    $result['result']=false;
                    $result['message']='The type descriptor was not a legal value. It can be s for string or n for number.';
                }
            }            
        }

        //Check email structure, if requested (this assumes the first and only element is to be checked
        if($checkAnEmail){
            $result['result']=$this->validateEmail($args[0]['value']);
            if(!$result['result']){
                $this->properties['errorState']=true;
                $result['message']="The provided email was invalid.";
            }else{
                $this->properties['errorState']=false;
                $result['message']="The provided email was valid. Proceeding.";
            }
        }

        //If everything checks out then, we move forward with a successful message.
        if($result['result']){
            $result['message']='Validation was successful. The data can be parsed into this service query.';
        }
        
        return $result;
    }
    
    /*
     * Quits the database connection and frees the resources
     */
    function closeMe(){
        mysqli_close ($this->connection);
        $this->connection=null;
        return true;
    }

    /**
     * Confirm that this is a valid email expression.
     * This was faster to just use a predefined function call found here
     * https://stackoverflow.com/questions/5855811/how-to-validate-an-email-in-php
     *
     * @author
     * @param string $email
     * @return boolean
     */
    public function validateEmail($email){
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}