<?php
class Api_model extends CI_Model
{
	
	public function __construct()
    {
        parent::__construct();
    }
	
	function Common_model()
	{
		
		global $accessInfo;
		global $isPersonal;
		
		$this->accessInfo = $this->session->userdata('accessPermission');
		$this->isPersonal = $this->session->userdata('accessIsPersonal');
			
	}
	
	//Insert Function 
	function insertData($tablename,$insertData=array())
	{   
		
		if(count($insertData)>0)
		{
			if(count($tablename)>0)
			{
				$this->db->insert($tablename,$insertData);
				return $this->db->insert_id(); //// return last insert record id
			}
		}
	}
	
	
	//Insert (Duplicate) Function 
	function insertUpdateData($tablename,$insertData=array())
	{   
		if(count($insertData)>0)
		{
			if(count($tablename)>0)
			{
				$sql = $this->_duplicate_insert($tablename, $insertData);
				
				$this->db->query($sql);
				return $this->db->insert_id();  //// return last insert record id
			}
		}
	}
	
	function currentDbDateTime(){
		$dateNow = date("Y-m-d H:i:s");
		$qry = "SELECT NOW( ) AS DateTimeNow";	
		$result = $this->db->query($qry);
		$rowData = $result->result_array();
		if(isset($rowData[0]["DateTimeNow"])){
			$dateNow = $rowData[0]["DateTimeNow"];
		}
		return $dateNow;
	}
	
	function _duplicate_insert($table, $values)
	{
		$updatestr = array();
		$keystr    = array();
		$valstr    = array();
		
		foreach($values as $key => $val)
		{
			$updatestr[] = $key." = '".$val."'";
			$keystr[]    = $key;
			$valstr[]    = $val;
		}
		
		$sql  = "INSERT INTO ".$table." (".implode(", ",$keystr).") ";
		$sql .= "VALUES ('".implode("', '",$valstr)."') ";
		$sql .= "ON DUPLICATE KEY UPDATE ".implode(", ",$updatestr);
		
		return $sql;
	} 
	//Update Function 
	function updateData($tablename,$updateData=array(),$where=array(),$like=array(),$whereByOneII=array(),$whereByTwoII=array())
	{
		if(count($where)>0)
		{
			$this->db->where($where);
		}
		if(count($whereByOneII)>0 && count($whereByTwoII)>0 && count($whereByOneII)>0 == count($whereByTwoII)){
			for($whereStart=0;$whereStart<count($whereByOneII);$whereStart++){
				$this->db->where($whereByOneII[$whereStart], $whereByTwoII[$whereStart]);
			}
		}
		if(count($like)>0)
		{
			$this->db->like($like);
		}
		if(count($updateData)>0)
		{
			if(count($tablename)>0)
			{
				$var=$this->db->update($tablename,$updateData);
				 
				return $var;
			}
		}
	}
	//Delete Function
	function deleteData($tablename,$where=array())
	{
	
		if(count($where)>0)
		{
			$this->db->where($where);
		}
		if(count($tablename)>0)
		{
			$this->db->delete($tablename);
		}
	}
	//Select Query Function
	function selectData($tablename,$fieldname=array(),$where=array(),$like=array(),$orderby="",$limitStart="",$limitRatio="",$whereDirect=FALSE,$groupby="")
	{ 
		if(count($where)>0)
		{
			$this->db->where($where);
		}
		if($whereDirect == TRUE && $where != NULL){
			$this->db->where($where);
		}
		if(count($like)>0)
		{
			$this->db->like($like);
		}
		if($orderby)
		{
			$this->db->order_by($orderby);
		}
		if(count($fieldname)>0)
		{
			$this->db->select($fieldname);
		}
		
		if($limitStart != "" || $limitRatio != ""){

			if($limitRatio == ""){
			$this->db->limit($limitStart);
			}else{
				$this->db->limit($limitRatio,$limitStart);
			}
		}
		if($groupby){
			$this->db->group_by($groupby);
		}
		if(count($tablename)>0)
		{
			$var=$this->db->get($tablename);
		
		//var_dump($var->result());die;
			return $var; 
		}
	}
	function syncronizeMenuWeight($table,$condition,$oldWeight,$weightField="Weight"){
	
		if(count($condition) > 0){
			$this->db->where($condition);
		}
		$this->db->where($weightField.' >',$oldWeight);
		$this->db->set($weightField,$weightField.'-1',FALSE);
		$syncroId = $this->db->update($table);
	}
	
	function checkUserAccess($moduleType,$checkModuleType,$sendType=TRUE){
		if(trim(strtoupper($checkModuleType)) == "N"){
			if((isset($this->accessInfo[$moduleType]) && in_array("N",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
				if($sendType == TRUE){
					redirect('admin/home/unAuthorziedUser');
				}else{redirect('admin/home/unAuthorziedUser');}
			}
		}else{
			if(trim(strtoupper($checkModuleType)) == "A,E" || trim(strtoupper($checkModuleType)) == "E,A" || trim(strtoupper($checkModuleType)) == "A, E" || trim(strtoupper($checkModuleType)) == "E, A"){
				if((isset($this->accessInfo[$moduleType]) && (!in_array("A",$this->accessInfo[$moduleType]) && !in_array("E",$this->accessInfo[$moduleType]))) || (!isset($this->accessInfo[$moduleType]))){
					if($sendType == TRUE){
						redirect('admin/home/unAuthorziedUser');
					}else{redirect('admin/home/unAuthorziedUser');}	
				}
			}
			if(trim(strtoupper($checkModuleType)) == "A"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("A",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					if($sendType == TRUE){
						redirect('admin/home/unAuthorziedUser');
					}else{redirect('admin/home/unAuthorziedUser');}	
				}
			}
			if(trim(strtoupper($checkModuleType)) == "E"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("E",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					if($sendType == TRUE){
						redirect('admin/home/unAuthorziedUser');
					}else{redirect('admin/home/unAuthorziedUser');}	
				}
			}
			if(trim(strtoupper($checkModuleType)) == "D"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("D",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					if($sendType == TRUE){
						redirect('admin/home/unAuthorziedUser');
					}else{redirect('admin/home/unAuthorziedUser');}	
				}
			}
			
			if(trim(strtoupper($checkModuleType)) == "V"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("V",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					if($sendType == TRUE){
						redirect('admin/home/unAuthorziedUser');
					}else{redirect('admin/home/unAuthorziedUser');}	
				}
			}
		}
	}
	
	function days_in_month($year, $month) {
		return( date( "t", mktime( 0, 0, 0, $month, 1, $year) ) );
	} 
	function checkUserAccessResult($moduleType,$checkModuleType,$sendType=TRUE){
		if(trim(strtoupper($checkModuleType)) == "N"){
			if((isset($this->accessInfo[$moduleType]) && in_array("N",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
				return FALSE;
			}else{return TRUE;}
		}else{
			if(trim(strtoupper($checkModuleType)) == "A,E" || trim(strtoupper($checkModuleType)) == "E,A" || trim(strtoupper($checkModuleType)) == "A, E" || trim(strtoupper($checkModuleType)) == "E, A"){
				if((isset($this->accessInfo[$moduleType]) && (!in_array("A",$this->accessInfo[$moduleType]) && !in_array("E",$this->accessInfo[$moduleType]))) || (!isset($this->accessInfo[$moduleType]))){
					return FALSE;
				}else{return TRUE;}
			}
			if(trim(strtoupper($checkModuleType)) == "A"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("A",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					return FALSE;
				}else{return TRUE;}
			}
			if(trim(strtoupper($checkModuleType)) == "E"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("E",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					return FALSE;
				}else{return TRUE;}
			}
			if(trim(strtoupper($checkModuleType)) == "D"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("D",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					return FALSE;
				}else{return TRUE;}
			}
			
			if(trim(strtoupper($checkModuleType)) == "V"){
				if((isset($this->accessInfo[$moduleType]) && !in_array("V",$this->accessInfo[$moduleType])) || (!isset($this->accessInfo[$moduleType]))){
					return FALSE;
				}else{return TRUE;}
			}
		}
	}
	
	function checkUserAccessPersonal($moduleType,$checkModuleType,$sendType=TRUE){
	
		$modulePersonalName = $moduleType . '_IsPersonal';
		if($this->isPersonal[$modulePersonalName] == 'Y'){
			if(trim(strtoupper($checkModuleType)) == "A"){
				if(isset($this->accessInfo[$moduleType]) && in_array("A",$this->accessInfo[$moduleType]) && in_array("P",$this->accessInfo[$moduleType])){
					return TRUE;
				}else{
					return FALSE;
				}
			}
			if(trim(strtoupper($checkModuleType)) == "E"){
				if(isset($this->accessInfo[$moduleType]) && in_array("E",$this->accessInfo[$moduleType]) && in_array("P",$this->accessInfo[$moduleType])){
					return TRUE;
				}else{
					return FALSE;
				}	
			}
			if(trim(strtoupper($checkModuleType)) == "V"){
				if(isset($this->accessInfo[$moduleType]) && in_array("V",$this->accessInfo[$moduleType]) && in_array("P",$this->accessInfo[$moduleType])){
					return TRUE;
				}else{
					return FALSE;
				}
			}
			if(trim(strtoupper($checkModuleType)) == "D"){
				if(isset($this->accessInfo[$moduleType]) && in_array("D",$this->accessInfo[$moduleType]) && in_array("P",$this->accessInfo[$moduleType])){
					return TRUE;
				}else{
					return FALSE;
				}
			}
			return FALSE;
		}
	}
	
	
	function imageDetailsAdd($photoGalleryId  , $fieldName){
		
		//Gallery Info
		$tableInfo = $this->db->dbprefix("photo_galleries")." Left Join ".$this->db->dbprefix("gallery_sizes")." on (".$this->db->dbprefix("photo_galleries").".Size_Id=".$this->db->dbprefix("gallery_sizes").".Id)";
		$condition = array($this->db->dbprefix("photo_galleries").'.Id'=>$photoGalleryId);
		$photoGalleryInfo = $this->selectData($tableInfo,$this->db->dbprefix("photo_galleries").'.Gallery_Name,'.$this->db->dbprefix("photo_galleries").'.Gallery_Directory, '.$this->db->dbprefix("photo_galleries").'.Thumb_Width, '.$this->db->dbprefix("photo_galleries").'.Thumb_Height,'.$this->db->dbprefix("gallery_sizes").'.Width,'.$this->db->dbprefix("gallery_sizes").'.Height',$condition,NULL);
		
		if($photoGalleryInfo->num_rows() > 0){ 
		$images = array(); $imageData = array();
		
		//if($fieldName =='Gallery_Image'){echo"G=". count($_FILES[$fieldName]['name']); echo "<pre>"; print_r($_FILES[$fieldName]);die;}
		$imageData = $_FILES[$fieldName];
		
		for($i=0;$i<count($imageData['name']);$i++){
			
			$randomPhotoName ='';$PhotoExtension ='';
			    $_FILES[$fieldName]['name'] = $imageData['name'][$i];
                $_FILES[$fieldName]['type'] = $imageData['type'][$i];
                $_FILES[$fieldName]['tmp_name'] = $imageData['tmp_name'][$i];
                $_FILES[$fieldName]['error'] = $imageData['error'][$i];
                $_FILES[$fieldName]['size'] = $imageData['size'][$i];
			
			$photoGalleryResult = $photoGalleryInfo->result();
		
			$Gallery_Name = $photoGalleryResult[0]->Gallery_Name;
			$Gallery_Directory = $photoGalleryResult[0]->Gallery_Directory;
			//$FullFolderName = "image_".$photoGalleryResult[0]->Width."X".$photoGalleryResult[0]->Height;
			$FullFolderName = 'manage';
			$photoWidth = $photoGalleryResult[0]->Width;
			$photoHeight = $photoGalleryResult[0]->Height;
			$ThumbWidth = $photoGalleryResult[0]->Thumb_Width;
			$ThumbHeight = $photoGalleryResult[0]->Thumb_Height;
			
			
			$randomPhotoName = uniqid();
			if (!file_exists('application/photo_gallery/'.$Gallery_Directory.'/original')) {mkdir('application/photo_gallery/'.$Gallery_Directory.'/original', 0777, true);}  //if folder doesn't exists
			
			//echo "<pre>";print_r($imageData);die;
			
			$Photo_Path['upload_path'] = 'application/photo_gallery/'.$Gallery_Directory.'/original';
			$Photo_Path['allowed_types'] = 'gif|jpg|png';
			$Photo_Path['file_name'] = $randomPhotoName;
			$a=$this->load->library('upload', $Photo_Path);
			$this->upload->initialize($Photo_Path);
			$PhotoExtension = '';
			
			if(isset($_FILES[$fieldName]['type'])){
				//echo "4546545";die;
						if($_FILES[$fieldName]['type'] == 'image/jpeg'){
							$PhotoExtension = ".jpg"; 
						}elseif($_FILES[$fieldName]['type'] == 'image/png'){
							$PhotoExtension = ".png";
						}elseif($_FILES[$fieldName]['type'] == 'image/gif'){
							$PhotoExtension = ".gif";
						}
					}
					
					
			 $FullFileOriginalPathName = $Photo_Path['upload_path'].'/'.$randomPhotoName.$PhotoExtension;
		     $returnPhoto = $this->upload->do_upload($fieldName);
			
			if($returnPhoto){
				
			if (!file_exists('application/photo_gallery/'.$Gallery_Directory.'/thumb')) {mkdir('application/photo_gallery/'.$Gallery_Directory.'/thumb', 0777, true);}  //if folder doesn't exists
			$config['image_library'] = 'gd2';
						$config['source_image'] = $FullFileOriginalPathName;
						$config['new_image'] = 'application/photo_gallery/'.$Gallery_Directory.'/thumb/'.$randomPhotoName.$PhotoExtension;
						$config['create_thumb'] = FALSE;
						$config['maintain_ratio'] = FALSE;
						$config['width'] = 500;
						$config['height'] = 500;
						$this->load->library('image_lib');
						
						$this->image_lib->initialize($config);
						$this->image_lib->resize();
						$this->image_lib->clear();
						
						if (!file_exists('application/photo_gallery/'.$Gallery_Directory.'/'.$FullFolderName.'')) {mkdir('application/photo_gallery/'.$Gallery_Directory.'/'.$FullFolderName.'', 0777, true);}  //if folder doesn't exists
						$config['new_image'] = 'application/photo_gallery/'.$Gallery_Directory.'/'.$FullFolderName.'/'.$randomPhotoName.$PhotoExtension;
						$config['create_thumb'] = FALSE;
						$config['maintain_ratio'] = FALSE;
						$config['width'] = $photoWidth;
						$config['height'] = $photoHeight;
						$this->image_lib->initialize($config);
						$this->image_lib->resize();	
						$this->image_lib->clear();
						
						$images[$i] =$randomPhotoName.$PhotoExtension; 
						
				}
		}
			
			if($images[0]!=''){ return $images;} else { return false;}
		
		
		
		}
		
  }
 
 /* Function for sendApplePushNotification  */ 
 
 function sendApplePushNotification($deviceToken , $message, $notificationType , $Unread_Notification){
	 
	 //Create context
		$ctx = stream_context_create();
		stream_context_set_option($ctx, 'ssl', 'local_cert', 'server_certificates_bundle_sandbox.pem');
		stream_context_set_option($ctx, 'ssl', 'passphrase', 'entrust_root_certification_authority.pem');
	
		//Establish connection
		$fp = stream_socket_client('ssl://gateway.sandbox.push.apple.com:2195', $err, $errstr, 60, STREAM_CLIENT_CONNECT|STREAM_CLIENT_PERSISTENT, $ctx);
										
		if (!$fp)
			exit("Failed to connect: $err $errstr" . PHP_EOL);
			
	   	// Create the payload body
		$body['aps'] = array(
			'alert' => $message,
			'sound' => 'default',
			'notificationType'=>$notificationType,
			'Unread_Notification'=> $Unread_Notification
			);
		// Encode the payload as JSON
		$payload = json_encode($body);
		
		// Build the binary notification
		$msg = chr(0) . pack('n', 32) . pack('H*', $deviceToken) . pack('n', strlen($payload)) . $payload;
		
		// Send it to the server
		$result = fwrite($fp, $msg, strlen($msg));
		
		//echo $result;die;
		//If want to keep track of delivery can be done from here.
		//if (!$result)
//			echo "<br/>Message not delivered-->$deviceToken" . PHP_EOL;
//		else
//			echo "<br/>Message successfully delivered-->$deviceToken" . PHP_EOL;        
		
		fclose($fp); // Close the connection to the server
	 
	 return true;
	 
 }
 
 
 
 
 
}
?>