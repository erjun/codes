<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');



class Manage extends CI_Controller {

	 public function manage()
     {
            parent::__construct();
            // Your own constructor code

			$this->load->model('Loginmodel');

			$this->load->model('Usermodel');

			$this->load->helper('form');			
     }

	 public function amazonbot_register()
     {
		$data['user_name'] = "";
		$adminData['id'] = "";

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/account',$adminData);
		$this->load->view('qiang_admin/sidebar');
     }

	public function updateAdminUser()
	{
		$this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[5]|is_unique[admin.username]');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
		$this->form_validation->set_rules('passconf', 'Password Confirmation', 'trim|required|matches[password]');
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|is_unique[admin.email]');
		$this->form_validation->set_rules('phone_number', 'Phone number', 'trim|required|min_length[8]|is_unique[admin.phone_number]');
		if($this->form_validation->run()){
			$id = $this->input->post("id");
			unset($_POST['id']);
			unset($_POST['passconf']);
			if($id != ""){
			}
			else
			{
				$_POST["offer_source"] = "amazon";
				$this->Usermodel->addAdminUserData($_POST);
				$this->load->view('qiang_admin/register_success.php');
			}
		}else{
			$this->session->set_userdata('msg',validation_errors());
			redirect('/manage/amazonbot_register');
		}
	}
	
	public function listAdmin(){
		$this->isLoggedIn();
		if(!$this->is_administrators()){
			show_404();
		}
		$admin_data['is_administrators'] = $this->is_administrators();

		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['details'] = $this->get_admin_list();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/list-admin',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/add-offer-footer');
	}

	private function get_admin_list(){
            $this->isLoggedIn();

            $page_size = 100000;
            $page = 1;
            $this->db->select("id,username,email,last_login,offer_source,qq,wechat,phone_number,company_name,individual_name");
            $this->db->from("admin");

            $offset = ($page - 1) * $page_size;
            // echo $page;
            $this->db->limit($page_size, $offset);
			$arr = $this->db->get()->result_array();
			
            return $arr;
	}

	public function index()
	{
		if($this->session->userdata('admin_user_id')){
			$this->dashboard();
		}else{
			$this->login();
		}
	}
	public function login(){
		$this->load->view('qiang_admin/login');
	}

	public function authincate()
	{
		$username = $_POST['username'];
		$password = $_POST['password'];

		if($this->Loginmodel->checkLogin($username,$password))
			//echo 'true';
			redirect('/manage/dashboard', 'refresh');
		else
		{
			//echo 'false';
			$this->session->set_userdata('msg','Invalid Username or Password');
			redirect('/manage/login', 'refresh');
		}

	}

	public function dashboard(){
		$this->listOffer();
	}
	public function addOffer(){
		$this->isLoggedIn();
		$id = $this->input->get("id");
		$user_id = $this->session->userdata('admin_user_id');
		$offer = $this->get_default_offer();
		if($id != ""){
			$this->user_data_filter();
            $offers = $this->get_offer_data($id);
			if(count($offers) == 0){
				show_404();
			}
			$offer = $offers[0];
		}

		$user_data = $this->db->where('id',$user_id)->get('admin')->row();
		if($user_data->offer_source == "") exit("user offer_source is empty");
		$last_login = $user_data->last_login;
		$data["offer_source"] = explode(",", $user_data->offer_source);
		
		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['offer'] =  $offer;
		$data["offer_id"] = $id;
		$data['is_admin'] = $this->is_admin();
		$admin_data['is_administrators'] = $this->is_administrators();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/add-offer');
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/add-offer-footer');
	}
	private function get_offer_data($id){
		$this->db->select("title,subtitle,icon,bases.offer_id,promotion_price,description,buy_link,coupon_link,original_price,banner_url,banner banner_sort,max_num,start_time,end_time,status,offer_source");
		$this->db->from("promotion_products as apps");
		$this->db->join("offer_bases as bases","apps.offer_id = bases.offer_id");
		$this->db->where("apps.offer_id = " . $id);

		$offers = $this->db->get()->result_array();
		return $offers;
	}

	public function editOrder(){
		$this->isLoggedIn();

		$id = $this->input->get("id");
		$user_id = $this->session->userdata('admin_user_id');

		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['is_admin'] = $this->is_admin();
		$admin_data['is_administrators'] = $this->is_administrators();

		$order = $this->get_default_order();
		if($id != ""){
			$this->user_data_filter();
            $orders = $this->get_order_data($id);
			if(count($orders) == 0){
				show_404();
			}
			$order = $orders[0];
		}else{
			show_404();
		}
		$data["order"] = $order;
		$data["order_id"] = $id;
		$data['order_status_text'] = $this->get_order_status_text();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/edit-order');
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/add-offer-footer');
	}
	public function edit_order(){
		$this->isLoggedIn();

		$this->form_validation->set_rules('order_status', 'order_status', 'trim|required');

		if($this->form_validation->run()){
			if($_POST['order_id']!=""){
				$this->saveOrderData();
				$this->session->set_userdata('msg','订单更新成功.');
				redirect('/manage/editOrder?id='.$_POST['order_id']);
			}
		}

	}
	private function get_order_status_text(){
		$text = array(
			"1" => "未开始",
			"2" => "开始",
			"3" => "已获优惠码",
			"4" => "下单中",
			"5" => "等待认证",
			"6" => "成功",
			"7" => "失败",
			"8" => "主动放弃",
		);
		return $text;
	}
	private function saveOrderData(){
		$order_id = $this->input->post("order_id");

		$shop_order_id = $this->input->post("shop_order_id");
		$order_status = $this->input->post("order_status");
		$comment = $this->input->post("comment");
		$review_url = $this->input->post("review_url");

		$this->db->where("id", $order_id);
		$this->db->update("orders",array("order_status"=> $order_status, 
		"shop_order_id" => $shop_order_id,
		 "comment" => $comment,
		 "review_url" => $review_url,
		 ));
	}
	private function get_order_data($id){
		$this->db->select("shop_order_id,coupon_code,title,apps.offer_id,order_status,comment,review_url");	
		$this->db->from("orders");
		$this->db->join("order_base","order_base.id = orders.order_base_id");
		$this->db->join("offer_bases bases","order_base.offer_id = bases.offer_id");
		$this->db->join("promotion_products as apps","apps.offer_id = bases.offer_id");
		
		$this->db->where("orders.id = " . $id);

		$offers = $this->db->get()->result_array();
		return $offers;
	}
	private function get_default_order(){
		return array(

		);
	}

	public function contribute(){
		$offer = $this->get_default_offer();
		
		$data['user_name'] = "";
		$data['offer'] =  $offer;
		$data["offer_id"] = "";
		$data["is_admin"] = false;

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/add-offer');
		$this->load->view('qiang_admin/sidebar');
		$this->load->view('qiang_admin/add-offer-footer');
	}
	public function submit_contribute(){
		$_POST["status"] = 0;
		$_POST["author_id"] = 0;
		$_POST["offer_source"] = "taobao";
		$this->form_validation->set_rules('title', 'title', 'trim|required');

		if($this->form_validation->run()){
			$this->saveOfferData($_POST,$_FILES);
			$this->session->set_userdata('msg','新增投稿成功.');
			redirect('/manage/contribute');
		}
	}
	public function listOffer()
	{
		$this->isLoggedIn();

		$last_login = $this->db->where('id',$this->session->userdata('admin_user_id'))->get('admin')->row()->last_login;
		
		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['details'] = $this->get_offer_list();
		$admin_data['is_administrators'] = $this->is_administrators();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/list-offer',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/add-offer-footer');
		
	}

	public function get_offer_list(){
            $this->isLoggedIn();

			$user_id = $this->session->userdata('admin_user_id');
			$user_data = $this->db->where('id',$user_id)->get('admin')->row();

            $page_size = 100000;
            $page = 1;
            $this->user_data_filter();
            $this->db->select("bases.offer_id id,title,subtitle,original_price,buy_link,coupon_link,description,max_num,complete_number,icon,status,end_time,offer_source");
            $this->db->from("promotion_products as apps");
            $this->db->join("offer_bases as bases","apps.offer_id = bases.offer_id");


            $offset = ($page - 1) * $page_size;
            $this->db->limit($page_size, $offset);
			$arr = $this->db->get()->result_array();

			$new_arr = array();
			foreach($arr as $key => $value){
				$arr_item = $value;
				$arr_item["code_use"] = $this->get_offer_code_use($value);
				$code_count = $this->get_order_coupon_count($value);
				$arr_item["order_count"] = $this->get_offer_order_count($value);
				$arr_item["order_success_count"] = $this->get_offer_order_count($value,6);
				if($code_count > 0){
					$arr_item["count_surplus"] = $code_count - $this->get_offer_code_use($value, 8);
				}else{
					$arr_item["count_surplus"] = $code_count;
				}
				
				$new_arr[] = $arr_item;
			}
			
            return $new_arr;
	}

	private function get_order_coupon_count($value){
		$count_order = "";
		if($value["offer_source"] == "amazon"){
			$count = 0;
			$coupon = $value["coupon_link"];
			if($coupon){
				$coupon = trim(preg_replace('/\s\s+/', '', $coupon));
				$coupon_arr = explode("\n", trim($coupon));
				$count = count($coupon_arr);
			}
			$count_order = $count;
		}

		return $count_order;
	}

	private function get_offer_code_use($value, $order_status = null){
		$count_surplus = "";
		if($value["offer_source"] == "amazon"){
			$count = 0;
			$coupon = $value["coupon_link"];
			if($coupon){
				$coupon = trim(preg_replace('/\s\s+/', '', $coupon));
				$coupon_arr = explode("\n", trim($coupon));
				// print_r($coupon_arr);

				$this->user_data_filter();
				$this->get_list_order_sql();
				$this->db->where("apps.offer_id", $value["id"]);
				if($order_status){
					$this->db->where("orders.order_status != ", $order_status);
				}
				
				$all_result = $this->db->get()->result_array();
				$arr = array();
				foreach($all_result as $key => $value){
					if(!$value["coupon_code"]) continue;
					$arr[] = $value["coupon_code"];
				}
				
				// print_r($coupon_arr);

				foreach($coupon_arr as $key => $value){
					$coupon_code = $value;
					
					if(in_array($coupon_code, $arr)){
						$count++;
					}
				}
			}

			$count_surplus = $count;
		}

		return $count_surplus;
	}


	private function get_offer_order_count($value, $order_status_filter = null){
		$count = "";
		if($value["offer_source"] == "amazon"){
			$this->user_data_filter();
			$this->get_list_order_sql();
			$this->db->where("apps.offer_id", $value["id"]);
			if($order_status_filter != null){
				$this->db->where("orders.order_status", $order_status_filter);
			}
			$count = $this->db->count_all_results();
		}
		return $count;
	}
	
	private function get_default_offer(){
		$offer = array(
			"title" => "",
			"subtitle" => "",
			"icon" => "",
			"description" => "",
			"original_price"=>"",
			"promotion_price" => "",
			"banner_url" => "",
			"banner_sort" => "",
			"coupon_link" => "",
			"buy_link" => "",
			"max_num" => "",
			"start_time" => "",
			"end_time" => "",
			"status" => "",
			"offer_source"=>"",
		);
		return $offer;
	}

	public function listOrder()
	{
		$this->isLoggedIn();
		$offer_id = $this->input->get("offer_id");

		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['order_status'] = $this->input->get("order_status");
		$admin_data['is_administrators'] = $this->is_administrators();
		$admin_data["offer_id"] = $offer_id;
		$admin_data["user_id"] = $this->input->get("user_id");
		$data["facebook_user_id"] = $this->get_login_user_fb();
		$page = $this->get_login_user_page();
		$data["page_id"] = "";
		$data["access_token"] = "";
		if(isset($page) && count($page) > 0){
			$data["page_id"] = $page->page_id;
			$data["access_token"] = $page->access_token;
		}
		$data['order_status_text'] = $this->get_order_status_text();
		$data["herokuapp_url"] = $this->get_herokuapp_url();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/list-order',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/list-order-footer');
	}

	public function listLog()
	{
		$this->isLoggedIn();
		// $offer_id = $this->input->get("offer_id");

		$data['user_name'] = $this->session->userdata('admin_user_name');
		// $data['order_status'] = $this->input->get("order_status");
		// $admin_data['is_administrators'] = $this->is_administrators();
		// $admin_data["offer_id"] = $offer_id;
		// $data["facebook_user_id"] = $this->get_login_user_fb();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/list-log',$data);
		$this->load->view('qiang_admin/sidebar');
		$this->load->view('qiang_admin/list-log-footer');
	}

	private function get_login_user_fb(){
		$fb = "";

		$user_id = $this->session->userdata('admin_user_id');
		$this->db->select("facebook_user_id");
		$this->db->from("admin");
		$this->db->join("users","users.id = admin.user_id");
		$this->db->where("admin.id", $user_id);
		$result = $this->db->get()->row();
		if(isset($result) && count($result) > 0){
			$fb = $result->facebook_user_id;
		}

		return $fb;
	}

	private function get_login_user_page(){
		$user_id = $this->session->userdata('admin_user_id');
		$this->db->select("facebook_page.*");
		$this->db->from("admin");
		$this->db->join("facebook_page","facebook_page.admin_owner = admin.id");
		$this->db->where("admin.id", $user_id);
		$result = $this->db->get()->row();
		return $result;
	}

	public function get_list_order(){
            $this->isLoggedIn();

			$start = $this->input->get("start");
			$page_size = $this->input->get("length");
			$draw = $this->input->get("draw");
			$search = $this->input->get("search")["value"];
			$offer_id = $this->input->get("offer_id");
			$order_status_filter = $this->input->get("order_status_filter");
			$order_shop_filter = $this->input->get("order_shop_filter");
			$admin_id = $this->input->get("admin_id");
			$user_id = $this->input->get("user_id");

			// $admin_id = $this->session->userdata('admin_user_id');
			// $user_data = $this->db->where('id',$admin_id)->get('admin')->row();
            
            $this->user_data_filter();
            $this->get_list_order_sql();	
			if($offer_id){
				$this->db->where("apps.offer_id", $offer_id);
			}
			if($order_status_filter){
				$this->db->where("orders.order_status", $order_status_filter);
			}
			if($order_shop_filter == "1"){
				$this->db->where("orders.shop_order_id !=","");
			}else if($order_shop_filter == "2"){
				$this->db->where("orders.shop_order_id", "");
			}
			if($admin_id){
				$this->db->where("admin.id", $admin_id);
			}
			if($user_id){
				$this->db->where("users.id", $user_id);
			}
			$this->get_order_search($search);

			$db = clone($this->db);
			$count = $db->count_all_results();
			
			$this->db->order_by('orders.created_time', 'DESC');
            $this->db->limit($page_size, $start);
			$result = $this->db->get();
			$arr = $this->get_new_list_order($result->result_array());
			$new_array = array();
			$new_array["draw"] = $draw;
			$new_array["recordsTotal"] = $count;
			$new_array["recordsFiltered"] = $count;
			$new_array["data"] = $arr;
			
			$this->output
              ->set_content_type('application/json')
                ->set_output(json_encode($new_array));
	}
	private function get_new_list_order($arr){
		$new_arr = array();
		foreach($arr as $key => $value){
			
			$id = $value["order_id"];
			//SELECT *,max(send_time) FROM `action_message_log` where target_id = 263 group by action_name,target_id 
			$this->db->from("action_message_log");
			$this->db->select("*");
			$this->db->select_max('send_time');
			$this->db->where("target_id", $id);
			$this->db->group_by(array("action_name", "target_id"));
			$result = $this->db->get();
			foreach($result->result_array() as $key2 => $value2){
				$action_name = $value2["action_name"];
				if($action_name == "urge_place_order" || $action_name == "urge_write_review"){
					$value[$action_name] = $value2["send_time"];
				}
			}
			$new_arr[] = $value;
		}
		return $new_arr;
	}
	private function init_order($value){
		// $value[$]
	}
	private function get_max_time($arr_item, $value){
		
		if(!isset($arr_item["urge_place_order"])){
			// print_r($arr_item);
			$arr_item["urge_place_order"] = "";
		}
		if(!isset($arr_item["urge_write_review"])){
			$arr_item["urge_write_review"] = "";
		}
		
		if($value["action_name"] == "urge_place_order" && $arr_item["urge_place_order"] < $value["send_time"]){
		
			return $value["send_time"];
		}

		if($value["action_name"] == "urge_write_review" && $arr_item["urge_write_review"] < $value["send_time"]){
			return $value["send_time"];
		}
	}
	private function in_array_by_key($arr, $word){
		foreach($arr as $key => $value){
			if($value["order_id"] == $word){
				return $key;
			}
		}
		return -1;
	}
	private function get_list_order_sql(){
		$this->db->select("order_base.id id,title,username,coupon_code,facebook_user_id,order_status,created_time,review_time completed_time,shop_order_id,amazon_profile_url,apps.offer_id,admin.id admin_id,review_url,orders.id order_id,comment");
           
		$this->db->from("order_base");
		$this->db->join("orders","order_base.id = orders.order_base_id");
		$this->db->join("promotion_products as apps", "apps.offer_id = order_base.offer_id");
		$this->db->join("offer_bases as bases","apps.offer_id = bases.offer_id");
		$this->db->join("admin","admin.id = apps.author_id");
		$this->db->join("users","order_base.wallet_id = users.wallet_id");
	}

	private function get_order_search($match){
		if($match == "") return;
		$this->db->like("shop_order_id", $match);
		$this->db->or_like("coupon_code", $match);
		$this->db->or_like("facebook_user_id", $match);
	}

	// 统计接口
	public function action_message_log(){
		$this->isLoggedIn();

		$sender_fbid = $this->input->post("sender_fbid");
		$receipt_fbid = $this->input->post("receipt_fbid");
		$action_name = $this->input->post("action_name");
		$target_id = $this->input->post("target_id");
		$admin_id = $this->session->userdata('admin_user_id');

		$data = array(
			"sender_fbid" => $sender_fbid,
			"receipt_fbid" => $receipt_fbid,
			"action_name" => $action_name,
			"target_id" => $target_id,
			"admin_id" => $admin_id,
		);

		$this->db->insert('action_message_log', $data);
	}

	public function get_list_log(){
		$this->isLoggedIn();

		if(!$this->is_admin()){
			$user_id = $this->session->userdata('admin_user_id');
			$this->db->where('admin.id',$user_id);
		}

		$this->db->select("*,action_message_log.id id");
		$this->db->from("action_message_log");
		$this->db->join("admin", "admin.id = action_message_log.admin_id");

		$start = $this->input->get("start");
		$page_size = $this->input->get("length");
		$draw = $this->input->get("draw");

		$db = clone($this->db);
		$count = $db->count_all_results();

		$this->db->limit($page_size, $start);
		$result = $this->db->get();
		$arr = $result->result_array();

		$new_array["draw"] = $draw;
		$new_array["recordsTotal"] = $count;
		$new_array["recordsFiltered"] = $count;
		$new_array["data"] = $arr;

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($new_array));
	}
	
	public function add_offer(){
		$this->isLoggedIn();
		$this->form_validation->set_rules('title', 'title', 'trim|required');

		if($this->form_validation->run()){
			if($_POST['offer_id']!=""){
				$this->saveOfferData($_POST,$_FILES);
				$this->session->set_userdata('msg','更新商品成功.');
				redirect('/manage/addOffer?id='.$_POST['offer_id']);
			}
			else
			{
				$_POST["author_id"] = $this->session->userdata('admin_user_id');
				$this->saveOfferData($_POST,$_FILES);
				$this->session->set_userdata('msg','新增商品成功.');
				redirect('/manage/addOffer');
			}
		}
	}

	function saveOfferData($formData,$imageData){
		if($formData['offer_id'] != "")
			$Offer_id = array_pop($formData);
		else
			$Offer_id = '';
		
		$data = $formData;
		unset($data["offer_id"]);

		if($Offer_id != '')
		{
			$this->db->where('apps.offer_id',$Offer_id);
			$this->db->where('apps.offer_id = bases.offer_id');
			$this->db->update('promotion_products as apps,offer_bases as bases', $data);
		}
		else
		{
			$base = array();
			
			
			$base["max_num"] = $data["max_num"];
			$base["start_time"] = $data["start_time"];
			$base["end_time"] = $data["end_time"];
			$base["offer_source"] = $data["offer_source"];
			$base["offer_type"] = 2;

			$this->db->insert('offer_bases', $base);
			$Offer_id = $this->db->insert_id();	

			if($Offer_id > 0){
				$products["title"] = $data["title"];
				$products["subtitle"] = $data["subtitle"];
				$products["original_price"] = $data["original_price"];
				$products["coupon_link"] = $data["coupon_link"];
				$products["buy_link"] = $data["buy_link"];
				$products["description"] = $data["description"];
				$products["banner"] = $data["banner"];
				$products["status"] = $data["status"];
				$products["author_id"] = $data["author_id"];
				$products["offer_id"] = $Offer_id;
				$products["promotion_price"] = $data["promotion_price"];
				$this->db->insert('promotion_products', $products);
			}
		}
		
		$this->save_img($imageData,"icon",$Offer_id);
		$this->save_img($imageData,"banner_url",$Offer_id);
		
	}
	private function save_img($imageData,$name,$offer_id){
		if(isset($imageData[$name]['name']) && $imageData[$name]['name'] != '')
		{
			$j = 0;
			
			$random_digit=rand(0000,9999);
			$file_name=$random_digit.$imageData[$name]['name'];
			$EXTENSION = pathinfo($file_name, PATHINFO_EXTENSION);
			$file_name = md5(str_replace(" ","_",$file_name)) . "." . $EXTENSION;
		
			// The file supplied is valid...Set up some variables for the location and name of the file.
			$thumb_folder = 'uploads/qiang/original/'; // This is the folder to which the images will be saved
			$tmp_file = $thumb_folder.$file_name; // save file in tmp folder
			// Now use the move_uploaded_file function to move the file from its temporary location to its new location as defined above.
			move_uploaded_file($_FILES[$name]['tmp_name'], $tmp_file);
			
			$data[$name] = $file_name;
			///
			
			$this->db->where('offer_id',$offer_id);
			$this->db->update('promotion_products',$data);	
			
		} // end if
	}


	private function user_data_filter(){
		$user_id = $this->session->userdata('admin_user_id');
		if($this->is_administrators()){
		}else if ($this->is_admin_offer_source("taobao_admin") && !$this->is_admin_offer_source("amazon_admin")) {
			$this->db->where('bases.offer_source',"taobao");
		}else if (!$this->is_admin_offer_source("taobao_admin") && $this->is_admin_offer_source("amazon_admin")) {
			$this->db->where('bases.offer_source',"amazon");
		}else{
			$this->db->where('apps.author_id',$user_id);
		}
	}
	// 是否是超级管理员
	private function is_administrators(){
		return $this->is_admin_offer_source("administrators");
	}
	// 只要offer_source包含admin就是管理员或超级管理员
	private function is_admin(){
		$offer_source = implode(",",$this->get_admin_offer_source());
		if (strpos($offer_source, 'admin') !== false) {
			return true;
		}
		return false;
	}
	private function is_admin_offer_source($identifying){
		$offer_source = $this->get_admin_offer_source();
		if (in_array($identifying, $offer_source)) {
			return true;
		}else{
			return false;
		}
	}
	private function get_admin_offer_source(){
		$user_id = $this->session->userdata('admin_user_id');
		$user_data = $this->db->where('id',$user_id)->get('admin')->row();

		$offer_source = explode(",",$user_data->offer_source);
		return $offer_source;
	}

	public function logout(){
		// update last login
		$data['last_login'] = date('Y-m-d H:i:s');
		$this->db->where('id',$this->session->userdata('admin_user_id'))->update('admin',$data);
		$this->session->sess_destroy();
		redirect('/manage');
	}

	private function isLoggedIn(){
		if($this->session->userdata('admin_user_id')){
			return true;
		}else{
			redirect('/manage');
		}
	}
	/**************************************************
	* 用户
	***************************************************/
	public function listUser(){
		$this->isLoggedIn();
		$offer_id = $this->input->get("offer_id");

		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['order_status'] = $this->input->get("order_status");
		$admin_data['is_administrators'] = $this->is_administrators();
		$admin_data["offer_id"] = $offer_id;
		$data["facebook_user_id"] = $this->get_login_user_fb();
		$page = $this->get_login_user_page();
		$data["page_id"] = "";
		$data["access_token"] = "";
		if(isset($page) && count($page) > 0){
			$data["page_id"] = $page->page_id;
			$data["access_token"] = $page->access_token;
		}
		$data['order_status_text'] = $this->get_order_status_text();

		$data["list_campaign"] = $this->get_user_list_campaign();
		$data["gender"] = $this->get_gender();
		$data["herokuapp_url"] = $this->get_herokuapp_url();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/list-user',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/list-user-footer');
		$this->load->view('qiang_admin/add-offer-footer');
	}

	private function get_user_list_campaign(){
		$this->isLoggedIn();

		if(!$this->is_admin()){
			$user_id = $this->session->userdata('admin_user_id');
			$this->db->where('admin.id',$user_id);
		}
		$this->db->select("*,campaigns.id id,campaigns.is_active is_active");
		$this->db->from("campaigns");
		$this->db->join("admin", "admin.id = campaigns.admin_id");

		$result = $this->db->get();
		$arr = $result->result_array();
		return $arr;
	}

	public function get_list_user(){
            $this->isLoggedIn();

			$start = $this->input->get("start");
			$page_size = $this->input->get("length");
			$draw = $this->input->get("draw");
			$tag_name = $this->input->get("tag_name");
			$username = $this->input->get("username");
			$gender = $this->input->get("gender");
			$locale = $this->input->get("locale");

			$user_id = $this->session->userdata('admin_user_id');
			$user_data = $this->db->where('id',$user_id)->get('admin')->row();
            
			$arr = array();
			$count =0;
            if($this->get_list_user_sql()){
				if($tag_name){
					$this->db->like("tags", $tag_name);
				}
				if($username){
					$this->db->like("first_name", $username);
					$this->db->or_like("last_name", $username);
				}
				if($gender){
					$this->db->where("gender", $gender);
				}
				if($locale){
					$this->db->like("locale", $locale);
				}

				$db = clone($this->db);
				$count = $db->count_all_results();
				
				$this->db->order_by('id', 'DESC');
				$result = $this->db->get();
				// echo($this->db->last_query());

				if($count > 0){
					$arr = $this->get_new_list_user($result->result_array());
					$arr = $this->user_filter_deal($arr);
					$count = count($arr);
					$arr = $this->user_filter_limit($arr, $page_size, $start);
					
				}
			}
			
			$new_array = array();
			$new_array["draw"] = $draw;
			$new_array["recordsTotal"] = $count;
			$new_array["recordsFiltered"] = $count;
			$new_array["data"] = $arr;
			
			$this->output
              ->set_content_type('application/json')
                ->set_output(json_encode($new_array));
	}
	private function user_filter_deal($data){
		$deal_count_min = $this->input->get("deal_count_min");
		$deal_count_max = $this->input->get("deal_count_max");
		$deal_time_start = $this->input->get("deal_time_start");
		$deal_time_end = $this->input->get("deal_time_end");
		
		$new_data = array();
		foreach($data as $key => $value){
			$count = 0;
			$time = 0;
			if(isset($value["time"])){
				$time = $value["time"];
			}
			if(isset($value["count"])){
				$count = $value["count"];
			}
			
			if($deal_count_min && $count < $deal_count_min){
				continue;
			}
			if($deal_count_max && $count > $deal_count_max){
				continue;
			}

			if($deal_time_start  && $time < $deal_time_start){
				continue;
			}
			if($deal_time_end && $time > $deal_time_end){
				continue;
			}
			$new_data[] = $value;
		}
		return $new_data;
	}
	private function user_filter_limit($data, $page_size, $start){
		$new_data = array();
		for($i = $start; $i < ($start + $page_size) && $i < count($data); $i++){
			$new_data[] = $data[$i];
		}
		return $new_data;
	}
	private function get_gender(){
		return array(
			"male"=>"男",
			"female" =>"女",
			"other" => "其他",
		);
	}
	private function get_herokuapp_url(){
		global $debug;
		if($debug){
			return "http://10.0.0.126:5000/";
			
		}
		return "https://bomdealsbot.herokuapp.com/";
	}
	
	private function get_list_user_sql(){
		$u_id = $this->session->userdata('admin_user_id');
		$this->db->from("facebook_page");
		$this->db->where("admin_owner", $u_id);
		$result = $this->db->get();
		if($result->num_rows == 0){ return false;}
		$admin_facebook_page_id = $result->row()->page_id;

		$this->get_user_filter_tags();
		$this->db->select("id, wallet_id ,profile_pic, first_name, last_name,tags,facebook_user_id,gender");
		$this->db->from("users");
		$this->db->or_where("facebook_page_id", $admin_facebook_page_id);
		
		return true;
	}
	private function get_user_filter_tags(){
		$arr = $this->get_admin_offer_source();
		$tags = array();
		foreach($arr as $key => $value){
			// print_r(preg_match("/^user_tags/", $value));
			if(preg_match("/^user_tags/", $value) == 1){
				$tags_string = str_replace("user_tags","",$value);
				$tags_string = str_replace("[","",$tags_string);
				$tags_string = str_replace("]","",$tags_string);
				$tags = explode("|", $tags_string);
			}
		}
		foreach($tags as $key => $value){
			$this->db->or_where("tags", $value);
		}
	}
	private function get_new_list_user($arr){
		$wallet_id_list = array();
		foreach($arr as $key => $value){
			$wallet_id_list[] = $value["wallet_id"];
		}
		$this->db->select("orders.id order_id, wallet_id, count(1) count, max(created_time) time");
		$this->db->from("orders");
		$this->db->join("order_base", "orders.order_base_id = order_base.id");
		$this->db->where_in("order_base.wallet_id", $wallet_id_list);
		$this->db->group_by("wallet_id");
		$result = $this->db->get();
		$new_arr = array();
		foreach($arr as $key => $value){
			$item = $this->get_item_us($result->result_array(), $value["wallet_id"]);
			$new_val = array_merge($value, $item);
			$new_val["send_time"] = $this->get_user_log_time($value["facebook_user_id"]);
			$new_arr[] = $new_val;
		}
		return $new_arr;
	}
	private function get_user_log_time($fbid){
		$this->db->from("action_message_log");
		$this->db->select_max('send_time');
		$this->db->where("receipt_fbid", $fbid);
		$this->db->where("action_name", "send_campaign");
		$this->db->join("campaigns","campaigns.id = action_message_log.target_id");
		$result = $this->db->get();
		if($result->num_rows >0){
			return $result->row()->send_time;
		}else{
			return "";
		}
	}
	private function get_item_us($arr, $wallet_id){
		$item = array();
		foreach($arr as $key => $value){
			if($value["wallet_id"] == $wallet_id) return $value;
		}
		return $item;
	}
	public function editUser(){
		$this->isLoggedIn();

		$data['user_name'] = $this->session->userdata('admin_user_name');
		// $data['admin_id'] = $this->session->userdata('admin_user_id');
		$data['order_status'] = 0;
		$admin_data['is_administrators'] = $this->is_administrators();
		$id = $this->input->get("id");
		$detail = $this->get_user_data($id);
		$data["detail"] = $detail;
		$data["user_id"] = $id;

		$data["facebook_user_id"] = $this->get_login_user_fb();
		$page = $this->get_login_user_page();
		$data["page_id"] = "";
		$data["access_token"] = "";
		if(isset($page) && count($page) > 0){
			$data["page_id"] = $page->page_id;
			$data["access_token"] = $page->access_token;
		}
		$data['order_status_text'] = $this->get_order_status_text();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/edit-user',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/list-order-footer');
	}
	private function get_user_data($id){
		if(!$this->is_admin()){
			$user_id = $this->session->userdata('admin_user_id');
			$this->db->where('admin.id',$user_id);
		}
		
		$this->db->from("users");
	
		$this->db->where("users.id", $id);
		$result = $this->db->get();
		if($result->num_rows < 1){
			show_404();
		}
		$array = get_object_vars($result->row());

		return $array;
	}
	/**************************************************
	* 营销活动
	***************************************************/
	public function listCampaign(){
		$this->isLoggedIn();
		$offer_id = $this->input->get("offer_id");

		$data['user_name'] = $this->session->userdata('admin_user_name');
		$admin_data['is_administrators'] = $this->is_administrators();

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/list-campaign',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
		$this->load->view('qiang_admin/list-campaign-footer');
	}
	
	public function editCampaign(){
		$this->isLoggedIn();

		$data['user_name'] = $this->session->userdata('admin_user_name');
		$data['admin_id'] = $this->session->userdata('admin_user_id');
		$admin_data['is_administrators'] = $this->is_administrators();
		$id = $this->input->get("id");
		$detail = $this->get_campaign_def_data();
		if($id!=""){
			$detail = $this->get_campaign_data($id);
		}
		$data["detail"] = $detail;

		$this->load->view('qiang_admin/header',$data);
		$this->load->view('qiang_admin/edit-campaign',$data);
		$this->load->view('qiang_admin/sidebar',$admin_data);
	}
	private function get_campaign_def_data(){
		return array(
			"campaign_id" => "",
			"campaign_name" => "",
			"message_template" => "",
			"is_active" => 1,
		);
	}
	private function get_campaign_data($id){
		if(!$this->is_admin()){
			$user_id = $this->session->userdata('admin_user_id');
			$this->db->where('admin.id',$user_id);
		}
		$this->db->select("*,campaigns.id campaign_id,campaigns.is_active is_active");
		$this->db->from("campaigns");
		$this->db->join("admin", "admin.id = campaigns.admin_id");
		$this->db->where("campaigns.id", $id);
		$result = $this->db->get();
		if($result->num_rows < 1){
			show_404();
		}
		$array = get_object_vars($result->row());

		return $array;
	}
	public function edit_campaign(){
		$this->isLoggedIn();
		$this->form_validation->set_rules('campaign_name', '活动名称', 'trim|required');
		$this->form_validation->set_rules('message_template', '信息内容', 'trim|required');
		$this->form_validation->set_rules('admin_id', '发布者', 'trim|required');

		if($this->form_validation->run()){
			if($_POST['campaign_id']==""){
				$this->saveCampaignData($_POST);
				$this->session->set_userdata('msg','活动新增成功.');
				redirect('/manage/editCampaign');
			}else{
				$this->saveCampaignData($_POST);
				$this->session->set_userdata('msg','活动更新成功.');
				redirect('/manage/editCampaign?id=' . $_POST['campaign_id']);
			}
		}else{
			$this->session->set_userdata('msg',validation_errors());
			redirect('/manage/editCampaign');
		}

	}
	public function delete_campaign(){
		$this->isLoggedIn();
		$id = $this->input->post("campaign_id");
		$this->db->where("id", $id);
		$this->db->delete('campaigns');
	}
	private function rule_campaign_name(){
		
		
	}
	private function saveCampaignData($formData){
		$id = $this->input->post("campaign_id");
		unset($formData['campaign_id']);

		if(isset($formData['is_active'])){
			$formData['is_active'] = 1;
		}else{
			$formData['is_active'] = 0;
		}

		if($id == ""){
			$this->db->insert('campaigns', $formData);
		}else{
			$data = array(
				"is_active" => $formData['is_active'],
				"campaign_name" => $formData['campaign_name'],
				"message_template" => $formData['message_template'],
			);
			$this->db->where("id", $id);
			$this->db->update("campaigns", $data);
		}
	}

	public function get_list_campaign(){
		$this->isLoggedIn();

		if(!$this->is_admin()){
			$user_id = $this->session->userdata('admin_user_id');
			$this->db->where('admin.id',$user_id);
		}
		$this->db->select("*,campaigns.id id,campaigns.is_active is_active");
		$this->db->from("campaigns");
		$this->db->join("admin", "admin.id = campaigns.admin_id");

		$start = $this->input->get("start");
		$page_size = $this->input->get("length");
		$draw = $this->input->get("draw");

		$db = clone($this->db);
		$count = $db->count_all_results();

		$this->db->limit($page_size, $start);
		$result = $this->db->get();
		$arr = $result->result_array();

		$new_array["draw"] = $draw;
		$new_array["recordsTotal"] = $count;
		$new_array["recordsFiltered"] = $count;
		$new_array["data"] = $arr;

		$this->output
			->set_content_type('application/json')
			->set_output(json_encode($new_array));
	}

}

