<?php
class Linode {
    protected $_default_url = 'https://api.linode.com/';
    protected $_config = null;
    protected $_data = null;
    
    protected $_spec = null;
    
    private $api_version = null;
    private $methods = array();
    
    public function __construct($token,$url=null){
        // Config Load?
        $this->_config = array(
            'key'   => $token,
            'url'   => !is_null($url) ? $url : $this->_default_url
        );
        
        // Now lets make sure to initialize the spec
        $this->spec();
    }
    
    public function __call($method,$args){
        if(!method_exists($this,$method)){
            // Don't exist!
            $target = str_replace('_','.',$method);
            if(isset($this->methods[$target])){
                $function = $this->methods[$target];
                $required = 0;
                $data = array();
                $i = 0;
                foreach($function['PARAMETERS'] as $k => $pdata){
                    if($pdata['REQUIRED']){
                        $required++;
                    }
                    if(isset($args[$i])){
                        $data[$k] = $args[$i];
                    }
                    $i++;
                }
                if($required <= sizeof($args)){
                    return $this->execute($method,$data);
                } else {
                    throw new Exception("Missing required parameters, use Linode->describe(".$method.") to figure out what's needed.");
                }
            } else {
                throw new Exception("Linode API Function not found in spec.");
            }
        }
    }
    
    public function describe($command){
        if(stristr($command,"_")){
            $command = str_replace("_",".",$command);
        }
        if(isset($this->methods[$command])){
            $c = $this->methods[$command];
            $command = "<h2>".$command."</h2><code style=\"background-color:#FFFFFF;border:1px dotted #d9d9d9;padding:10px;display:block;\">mixed ".$command."(";
            $params = "";
            $closing = array();
            $index = 0;
            $previous_required = false;
            $size = sizeof($c['PARAMETERS']);
            foreach($c['PARAMETERS'] as $k => $d){
                if($d['REQUIRED'] === false){
                    $params .= "[";
                    if($index > 0){
                        $params .= ",";
                    }
                    $previous_required = true;
                    $closing[] = "]";
                }
                
                $params .= " ".str_replace('numeric','int',$d['TYPE'])." $".$k." ";
            
                $index++;
                
                if($index >= $size){
                    $params .= implode("",$closing);
                }
            }
            $command .= $params." );</code><br>";
            $command .= $c['DESCRIPTION'];
            $command .= "<hr>";
            if($size > 0){
                $command .= "<h2>Parameters</h2>";
                foreach($c['PARAMETERS'] as $k => $d){
                    $command .= "<h3>".$k." - ".($d['REQUIRED'] === false ? 'optional' : 'required')."</h3>";
                    $command .= "&nbsp;&nbsp;&nbsp;&nbsp;".$d['DESCRIPTION'];
                }
            }
        
            $command .= "<hr><h3>Linode Spec Data</h3>".print_r($c,true); 
            
            echo $command;
        } else {
            throw new Exception("Linode API Function not found in spec.");
        }
    }
    
    public function execute($command,$data=null){
        if(is_array($data)){
            $this->set_data($data);
        }
        
        $command = str_replace('_','.',$command);
        
        if(isset($this->methods[$command])){
            return json_decode($this->_execute($command));
        } else {
            throw new Exception("Linode API Function not found in spec.");
        }
    }
    
    public function _execute($command){
        $data = $this->_data;
        $this->_data = null;
        
        if(is_null($data)){
            $data = array();
        }
        
        $data['api_action'] = $command;
        $data['api_key'] = $this->_config['key'];
        
        $request = Request::forge($this->_config['url'],'curl');
        $request->set_params($data);
        $response = $request->execute();
        
        return $response;
    }
    
    public function set_data($key,$value=null){
        if(is_array($key)){
            foreach($key as $k=>$v){
                $this->set_data($k,$v);
            }
        } else {
            if(!is_array($this->_data)){
                $this->_data = array();
            }
            $this->_data[$key] = $value;
        }
        return $this;
    }
    
    public function spec(){
        // Spec stuff
        $spec = $this->_execute('api.spec');
        $this->_spec = json_decode($spec->response(),true);
        
        $this->api_version = $this->_spec['DATA']['VERSION'];
        $this->methods = $this->_spec['DATA']['METHODS'];
        
        return $this->_spec;
    }
    
}

// Ignore stuff below here, its a stub so that if you're working outside of FuelPHP that the class still works as expected.

if(!class_exists('Request')){
    class Request {
        public static function forge($url){
            return new LinodeCurl($url);
        }
    }
    
    class LinodeCurl {
        private $url = null;
        private $data = null;
        private $request = null;
        public function __construct($url){
            $this->url = $url;
            $this->request = curl_init();
            curl_setopt($this->request,CURLOPT_URL,$this->url);
            curl_setopt($this->request, CURLOPT_RETURNTRANSFER, 1);
        }
        
        public function set_method(){
            return $this;
        }
        
        public function set_params($data){
            $data_string = http_build_query($data);
            curl_setopt($this->request,CURLOPT_POST, count($data));
            curl_setopt($this->request,CURLOPT_POSTFIELDS, $data_string);
            return $this;
        }
        
        public function execute(){
            $data = curl_exec($this->request);
            curl_close($this->request);
            return $data;
        }
    }
}
