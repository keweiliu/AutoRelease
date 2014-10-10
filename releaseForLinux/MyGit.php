<?php
class MyGit{
    private $cwd;

    public function set_cwd($cwd){
        $this->cwd = $cwd;
    }
    
    public function get_cwd($cwd){
        return $this->cwd;
    }
    
    public function __construct($cwd){
        $this->cwd = $cwd;
    }
    
    public function run_command($command){
        $descriptorspec = array(
        1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        2 => array("pipe", "w") // stderr is a file to write to
        );

        $result = array();
        $return_value = -1;
        $response="";
        $error="";
        $process = proc_open($command, $descriptorspec, $pipes, $this->cwd);
        if(is_resource($process)){
            $response = stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            
            fclose($pipes[1]);
            fclose($pipes[2]);

            $return_value = proc_close($process);
            $result['result'] = false;
            if ($return_value === 0 || $return_value === 1){
                $result['result_text']=$response;
                $result['result'] = true;
            }else {                
                $result['result_text']=$error;
            }
        }
        return $result;
    }
}