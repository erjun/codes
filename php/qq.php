<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
    class Qq extends CI_Controller {
        var $home_page = "offer/offerList";
        function Qq(){
            parent::__construct();
            $this->load->library('mongo');
        }

        // Tools E
        // Page S
        function get_qq_list(){

            $callback = $this->input->get("callback");
            $string_data = $this->input->get("string_data");
            $data = json_decode($string_data);
            $gid = 0;
            $add_number = 0;
            $up_number = 0;
            foreach ($data as $key => $value) {
                $gid = $value->gid;
                $qq = $this->check_qq_member($value->uin, $gid);
                // print_r($qq);
                if(!$qq){
                    $this->add_qq_member($value); 
                    $add_number++;  
                }else if($value->last_speak_time > $qq["last_speak_time"]){
                    $this->up_qq_member($value);
                    $up_number++;
                }
            }
            $message = array("message" => $gid,"add_number" => $add_number, "up_number" => $up_number);
            $this->echo_json_ok($message);
        }

        function get_qq_info(){
            $gid = $this->input->get("gid");
            if(!$gid) return;
            $last_speak_time = -1;
            $last_join_time = -1;
            $last_join = $this->mongo->db->qq_member->find(array("gid" => $gid))->sort(array("join_time" => -1))->limit(1);
            if($last_join->count() > 0){
                $item = $this->get_array($last_join);
                $last_join_time = $item[0]["join_time"];
            }

            $last_speak = $this->mongo->db->qq_member->find(array("gid" => $gid))->sort(array("last_speak_time" => -1))->limit(1);
            if($last_speak->count() > 0){
                $item = $this->get_array($last_speak);
                $last_speak_time = $item[0]["last_speak_time"];
            }

            $result = array(
                    "gid" => $gid,
                    "last_join_time" => $last_join_time,
                    "last_speak_time" => $last_speak_time
                );
            $this->echo_json_ok($result);
        }
        private function get_array($cursor){
            $arr = array();
            foreach ($cursor as $document) {
                $arr[] = $document;
            }
            return $arr;
        }
        function add_qq_member($item){
            $data = array(
                "uin" => $item->uin,
                "card" => $item->card,
                "flag" => $item->flag,
                "g" => $item->g,
                "join_time" => $item->join_time,
                "last_speak_time" => $item->last_speak_time,
                "nick" => $item->nick,
                "qage" => $item->qage,
                "role" => $item->role,
                "tags" => $item->tags,
                "gid" => $item->gid,
                "lv_level" => $item->lv_level,
                "lv_point" => $item->lv_point,
            );

            $this->mongo->db->qq_member->insert($data);
            $this->up_qq_member($item);
        }
        function up_qq_member($member){
            $last = $member->last_speak_time;
            $id = $member->uin;
            $gid = $member->gid;
            $data = array(
                "uin" => $id,
                "gid" => $gid,
                "last_speak_time" => $last
            );
            $this->mongo->db->qq_member_speak->insert($data);
        }

        function check_qq_member($qid, $gid){
            $cursor = $this->mongo->db->qq_member->findOne(array("uin" => $qid, "gid" => $gid));
            return $cursor;
        }

        function save_group(){
            $callback = $this->input->get("callback");
            $string_data = $this->input->get("string_data");
            $data = json_decode($string_data);
            $exist = $this->check_qq_group($data->gc);
            if(!$exist){
                $this->add_qq_group($data);    
            }
            $this->echo_json_ok();
        }
        function check_qq_group($gc){
            $cursor = $this->mongo->db->qq_group->find(array("gc" => $gc));
             if($cursor->count() > 0){
                return true;
            }
            return false;
        }
        function add_qq_group($group){
            $data = array(
                "gc" => $group->gc,
                "gn" => $group->gn,
                "owner" => $group->owner,
            );
            $this->mongo->db->qq_group->insert($data);
        }
     
        // API END
    }
    

?>
