<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    class Amazon extends CI_Controller {
        function Amazon(){
            parent::__construct();
            $this->load->model('api_model');
        }

        public function save_item(){
            $profile_id = $this->input->get("profile_id");
            $profile_url = $this->input->get("profile_url");
            $email = $this->input->get("email");

            if(!$profile_url || !$profile_id){
                 $this->api_echo("必须有key");
                 return;
            }
            if($this->check_item($profile_url)){
                $this->api_echo($profile_url . "已经存在");
                return;
            }

            $insertdata = array();
            $insertdata['wallet_id'] = str_replace('0','5',md5(uniqid()));
            $insertdata["email"] = $email;
            $insertdata["amazon_profile_url"] = $profile_url;  
            $insertdata["tags"] = "top_reviewer";  
            $id = $this->api_model->insertData("users", $insertdata); 

            $insertdata = array();
            $insertdata["user"] = $id;
            $insertdata["profile_id"] = $profile_id;
            $this->api_model->insertData("amazon_user", $insertdata);

            $this->api_echo("成功保存" . $key);
        }
     
        public function check_item_exist(){
            $key = $this->input->get("key");
            $email = $this->input->get("email");
            if(!$key){
                 $this->api_echo("必须有key");
                 return;
            }
            if($this->check_item($key)){
                $data = array("exist" => true);
            }else{
                $data = array("exist" => false);
            }
            
            $this->echo_json($data);
        }

        private function check_item($profile_url){
            $this->db->where("amazon_profile_url", $profile_url);
            $result = $this->db->get("users");
            if($result->num_rows > 0) return true;
            return false;
        }

        private function api_echo($text){
            $data = array("message" => $text);
            $this->echo_json($data);
        }

        function echo_json($data){
             $this->output
              ->set_content_type('application/json')
                ->set_output(json_encode($data));
        }
    }
    

?>
