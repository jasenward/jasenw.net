<?php

/* 
 * This leverages the Bible Logos service to deliver content from that 
 * site.
 * 
 * @author  Jasen Ward, <jasenward@gmail.com>
 */
require_once $_SERVER['DOCUMENT_ROOT'].'/samplecode/controllers/authorCoreTools.php';
class bibleController extends authorCoreTools{
    public function __construct($instance='default',$token='default'){
        
        if($instance=='default'){
            $instance = $this->guid();
        }
        $this->versionProperties=array(
            'name'      => 'Bible Service Application',
            'version'   =>  2,
            'description'=> 'Bible service connects to the Logos Bible Service Entry point.',
            'instance'  => $instance,
        );
        //use API key from my account if one is not provided
        if($token=='default'){
            $token='8xA1Po2wJRUyYBZX2IlJwD8I02f58CamWepnuMlP';
        }
        
        //Populate Core Properties
        $this->instance= $instance;
        $this->token=$token;
        $this->protocol = 'https://';
        $this->base_url= 'bibles.org/v2/';
        $this->apis=array(
            'verse'=>'verses/',
        );
        $this->translations=array(
            'eng-GNTD',
        );
        $this->encoding_ext=array(
            '.xml',
            '.json',
        );
        
    }
    
    /**
     * Calls the YouVersion Bible App API to return a bible reference response
     * @author  Jasen Ward, <jasenward@gmail.com>
     * @param type $reference
     * @return string
     */
    public function callYouVersion($reference='default',$api='verse',$translation=0){
        //initialize result array
        $result['result']=true;
        $result['message']='';
        $result['curl']='';
        
        //validate reference pointer
        if($reference=='default'){
            $ref=$this->getRandomReference();
        }else{
            $ref=$reference;
        }
        $result['result']=$this->isValidReference($reference);
        
        if($result['result']){
            $token = $this->token;
            $url =   $this->protocol
                    .$this->base_url
                    .$this->apis[$api]
                    .$this->translations[$translation].':'
                    .$ref
                    .$this->encoding_ext[0];

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
            curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($ch, CURLOPT_USERPWD, "$token:X");

            // Do the request
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result['message']=$response;
            $result['content']=$this->extractPassage('verse', 'xml',$response)." (".$ref."). Content Provided by YouVersion.";
            $result['curl']=$url;
            
            
            
            //error_log('Using Token '.$token.' to parse \n'.print_r($result,true));
            $result['result']=$this->validateResponse($response);

        }else{
            $result['message']='Invalid Biblical Reference Pointer ['.$ref.'].';
        }
        
        return $result;
    }
    
    /**
     * @todo need to randomize the reference string using Number.Book.Chapter.Verse notation
     */
    public function getRandomReference(){
        return 'Acts.8.34';
    }
    
    /*
     * @todo need to actually parse the reference into a valid number.book.chapter.verse notation
     */
    public function isValidReference($reference='default'){
        return true;
    }
    
    /*
     * @todo need to parse curl response to be sure that there are no errors reported back and content is delivered
     */
    public function validateResponse($str){
        return true;
    }
    
    public function extractPassage($contentType='verse', $payloadType='xml', $payload){
        $result='';
        //instantiate response object
        switch ($payloadType){
            case 'xml':
                $objResponse= new SimpleXMLElement($payload);

                //extract content
                switch ($contentType){
                    case 'verse':
                        $result=$objResponse->verses->verse[0]->text;
                        break;
                    default:
                        $result=$objResponse->verses->verse[0]->text;
                        break;
                }
                break;
            case 'json':
                //@todo handle JSON response
                break;
            default: 
                $objResponse= new SimpleXMLElement($payload);
                break;
        }
        
        return $result;
    }
}