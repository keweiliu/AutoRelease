<?php
class MyCommand{
    private $cwd;
    private $gitShell;

    public function set_cwd($cwd){
        $this->cwd = $cwd;
    }

    public function get_cwd($cwd){
        return $this->cwd;
    }

    public function __construct($cwd, $gitShell = null){
        $this->cwd = $cwd;
        $this->gitShell = $gitShell;
    }

    public function run_command($command){
        $descriptorspec = array(
        0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
        1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
        2 => array("pipe", "w") // stderr is a file to write to
        );

        $result = array();
        $return_value = -1;
        $response="";
        $error="";

        if (!empty($this->gitShell)){
            $process = proc_open($this->gitShell, $descriptorspec, $pipes, $this->cwd);
        }else{
            $process = proc_open($command, $descriptorspec, $pipes, $this->cwd);
        }
        if(is_resource($process)){
            if (!empty($this->gitShell)){
                fwrite($pipes[0], $command);
                fclose($pipes[0]);
            }
            $response = stream_get_contents($pipes[1])."\n";
            $error = stream_get_contents($pipes[2])."\n";

            fclose($pipes[1]);
            fclose($pipes[2]);

            $return_value = proc_close($process);
            $result['return_value'] = $return_value;
            $result['response'] = $response;
            $result['error'] = $error;
        }
        return $result;
    }
}