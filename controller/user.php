<?php 
// USER ============================================================================
function mdc_user_view_data() {
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Username'),),
			array('data'=>t('Fullname'),),
			array('data'=>t('Lokasi'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Hak Akses'),),
			array('data'=>t('Edit'),),
			array('data'=>t('Delete'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_user ORDER BY lokasi,fullname");
	while($row = db_fetch_array($db_data)) {
		$edit 	= "<a href='" .base_path(). "mdc/online/user/set/?id=" .$row['username']. "'>edit</a>";
		$delete	= "<a href='" .base_path(). "mdc/online/user/delete/?id=" .$row['username']. "' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>hapus</a>";
		
		$usr	= mdc_user_roles(); 	// cek data login
		$adm	= $usr['admin'];
		$sa		= $usr['sa'];
		$un		= $usr['nama'];
		$usr_mdc= mdc_user_data($un); 	// cek data mdc
		$lokasi	= $usr_mdc['lokasi'];   // int
		$fungDat= mdc_fungsi_data($row['fungsi']);
		$fungsi	= $fungDat['description'];
		
		if($sa){
			$isi[] 	= array(++$xyz, $row['username'], $row['fullname'], cek_lokasi_mdc($row['lokasi']), $fungsi, cek_akses_mdc($row['username']), $edit, $delete);
		}elseif($lokasi == $row['lokasi']){
			$isi[] 	= array(++$xyz, $row['username'], $row['fullname'], cek_lokasi_mdc($row['lokasi']), $fungsi, cek_akses_mdc($row['username']), $edit, $delete);
		}
		
	}
	db_set_active();
	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_user_data($id) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_user WHERE username = '$id'");
	while($row = db_fetch_array($db_data)) {
		$hasil['username'] 	= $row['username'];
		$hasil['fullname'] 	= $row['fullname'];
		$hasil['lokasi']	= $row['lokasi'];
		$hasil['fungsi']	= $row['fungsi'];
		$hasil['akses'][182]	= $row['akses5'];
		$hasil['akses'][183]	= $row['akses1'];
		$hasil['akses'][181]	= $row['akses2'];
		$hasil['akses'][179]	= $row['akses3'];
		$hasil['akses'][180]	= $row['akses4'];
		$hasil['akses'][178]	= $row['akses6'];
		$hasil['atasan1']	= $row['atasan1'];
		$hasil['atasan2']	= $row['atasan2'];
		$hasil['atasan3']	= $row['atasan3'];
	}
	db_set_active();
	return $hasil;
}
function mdc_cek_user_lokasi(){
	$name		= $GLOBALS['user']->name;
	$data		= mdc_user_data($name);		// nama user (username)
	$lokasi		= $data['lokasi'];
	return $lokasi; 						// lokasi (int)
}
function cek_akses_name_mdc($rid) {
	db_set_active('default');
	$db_data = db_query("SELECT * FROM role WHERE rid = $rid");
	while($row = db_fetch_array($db_data)) {
		if($row['name']=='mdc super admin'){
			$nama 	= "Super Admin";
		}else if($row['name'] == 'mdc admin lokasi'){
			$nama 	= "Admin Lokasi";
		}else if($row['name'] == 'mdc good issue'){
			$nama = "Good Issue";
		}else if($row['name'] == 'mdc good receive'){
			$nama = "Good Recieve";
		}else if($row['name'] == 'mdc keuangan'){
			$nama = "Keuangan";
		}else if($row['name'] == 'mdc viewer'){
			$nama = "Viewer";
		}
		
	}
	db_set_active();
	return $nama;
}
function cek_akses_mdc($username) {
	$uid	= mdc_usernametouid($username);	
	
	db_set_active('default');
	$db_data = db_query("SELECT uid,rid FROM users_roles WHERE uid = $uid && rid IN (182,183,181,179,180,178)"); // mdc SA, mdc issuer, 181 keuangan, Man Apprv, gudang, mdc Admin 
	while($row = db_fetch_array($db_data)) {
		$uidx[] 	= cek_akses_name_mdc($row['rid']);
	}
	db_set_active();
	
	if(isset($uidx)){
		$hasil = implode($uidx, '<br>');
	}
	return $hasil;
}
function mdc_user_view() {
	$data = mdc_user_view_data();
	$output = theme_table($data['judul'], $data['isi']);
	$hasil = "<a href='" .base_path(). "mdc/online/user/set'>Add User MDC</a> | <a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $hasil.$output;
}
function mdc_user_roles(){
	$hasil['nama']		= $GLOBALS['user']->name;
	$hasil['uid'] 		= $GLOBALS['user']->uid;
	$hasil['sa']		= $GLOBALS['user']->roles[182]; // mdc super admin
	$hasil['issuer']	= $GLOBALS['user']->roles[183]; // mdc good issue
	$hasil['keuangan']	= $GLOBALS['user']->roles[181]; // mdc keuangan
	$hasil['mgrApp']	= $GLOBALS['user']->roles[179]; // mdc viewer
	$hasil['gudang']	= $GLOBALS['user']->roles[180]; // mdc good receive
	$hasil['admin']		= $GLOBALS['user']->roles[178]; // mdc admin lokasi
	
	return $hasil;
}
function mdc_user_name($string) {			// autofill 
	db_set_active('pep');
	$db_data = db_query("SELECT a.person_id, a.name, b.person_id, b.position_id, b.employee_no, b.fullname, c.position_id, c.name
							FROM top_person AS a
								LEFT JOIN org_employee AS b ON b.person_id = a.person_id
								LEFT JOIN org_position AS c ON c.position_id = b.position_id 
							WHERE a.name LIKE '%" .$string. "%' OR b.fullname LIKE '%" .$string. "%' OR c.name LIKE '%" .$string. "%'
							ORDER BY b.fullname");
	while($row = mysql_fetch_array($db_data)) {
		$username			= $row[1];
		$nopek				= $row[4];
		$fullname 			= $row[5];
		$stat 				= $row[7];		
		$data[$username] = $fullname .' - '. $nopek .' - '. $stat;		
	}
	db_set_active();
	
	print drupal_to_js($data);
	exit();
}
function cek_lokasi_mdc($id = NULL) {
	$site_options 	= mdc_plant_list();
	if($id==''){
		return $site_options;		
	}elseif($id>=0){
		return $site_options[$id];
	}
}
function mdc_cekusername($username, $key = NULL) {
	$db_data = db_query("SELECT uid,name FROM users WHERE name = '$username'");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['name'];	
	}
	if($key){
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_user WHERE username = '$username'");
		while($row = db_fetch_array($db_data)) {
			$hasil 	= 'error'; // ada data yg sama
		}
		db_set_active();		
	}
	return $hasil;
}
function mdc_user_set() {
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$user_lokasi		= mdc_cek_user_lokasi();
	$data_roles			= mdc_user_roles();
	$user_roles			= $data_roles['admin'];
	
	$name		= $GLOBALS['user']->name;
	$userData	= mdc_user_data($name);
	$user_fungsi=$userData['fungsi'];
	$fungsi_options	= mdc_fungsi_list();
	
	if($user_roles){ // jika "Admin"
		$lokasi 		= cek_lokasi_mdc($user_lokasi);
		$site_options[$user_lokasi] = $lokasi;		
		$akses			= array(179 => t('Viewer'), 183 => t('Good Issue'), 180 => t('Good Receive'));
	}else{
		$site_options 	= cek_lokasi_mdc();
// 		$akses			= array(170 => t('User (Wajib Mengisikan Atasan)'), 171 => t('SCM Approval'), 172 => t('Atasan Approval'), 173 => t('Gudang'), 169 => t('Super Admin'), 174 => t('Admin'));
		$akses			= array(179 => t('Viewer'), 183 => t('Goods Issue'), 180 => t('Goods Receive'), 178 => t('Admin Lokasi'), 182 => t('Super Admin'), 181 => t('Keuangan'));
	}
	
	if(isset($_GET['id'])){
		$id				= $_GET['id'];
		$data 			= mdc_user_data($id);
		$username 		= $data['username'];
		$fullname 		= $data['fullname'];
		$lokasi 		= $data['lokasi'];
		$aksesx	 		= $data['akses'];
		$atasan1 		= $data['atasan1'];
		$atasan2 		= $data['atasan2'];
		$atasan3 		= $data['atasan3'];
		$dis			= 'disabled';		
		$form['edit_id'] = array(
				'#type' => 'hidden',
				'#default_value' => $id
		);
	}
	$form['username'] = array(
		  	'#title' => t('Username'),
		    '#type' => 'textfield',	    
		    '#size' => 70,	
		    '#maxlength' => 60, 
		    '#weight' => 0, 
		    '#autocomplete_path' => 'mdc/online/user/name' ,
		    '#default_value' =>  $username,
		    '#required' => TRUE,	
			'#disabled' => $dis
	);
	$form['fullname'] = array(
		  	'#title' => t('Full Name'),
		    '#type' => 'textfield',	    
		    '#size' => 30,	
		    '#maxlength' => 100, 
		    '#weight' => 1, 
		    '#default_value' =>  $fullname,
		    '#required' => False,
			'#disabled' => 'disabled'
	);	  
	$form['lokasi'] = array(
			'#title' => t('Lokasi'),
			'#type' => 'select',
			'#options' => $site_options,
			'#default_value' =>  variable_get('lokasi', $lokasi),
			'#weight' => 2,
			'#required' => TRUE,
	);	  
	$form['fungsi'] = array(
			'#title' => t('Fungsi'),
			'#type' => 'select',
			'#options' => $fungsi_options,
			'#default_value' =>  variable_get('fungsi', $user_fungsi),
			'#weight' => 3,
			'#required' => TRUE,
	);
	$form['akses'] = array(
			'#type' => 'checkboxes',
			'#title' => t('Hak Akses'),
			'#weight' => 4,
			'#default_value' => variable_get(1,$aksesx),
			'#options' => $akses
	);
// 	$form['atasan1'] = array(
// 		  	'#title' => t('Atasan 1'),
// 		    '#type' => 'textfield',	    
// 		    '#size' => 70,	
// 		    '#maxlength' => 150, 
// 		    '#weight' => 4,
// 		    '#autocomplete_path' => 'mdc/online/user/name' ,
// 		    '#default_value' =>  $atasan1,
// 		    '#required' => FALSE,	  
// 	);	
// 	$form['atasan2'] = array(
// 		  	'#title' => t('Atasan 2'),
// 		    '#type' => 'textfield',	    
// 		    '#size' => 70,	
// 		    '#maxlength' => 150, 
// 		    '#weight' => 5,
// 		    '#autocomplete_path' => 'mdc/online/user/name' ,
// 		    '#default_value' =>  $atasan2,
// 		    '#required' => FALSE,	  
// 	);	
// 	$form['atasan3'] = array(
// 		  	'#title' => t('Atasan 3'),
// 		    '#type' => 'textfield',	    
// 		    '#size' => 70,	
// 		    '#maxlength' => 150, 
// 		    '#weight' => 6,
// 		    '#autocomplete_path' => 'mdc/online/user/name' ,
// 		    '#default_value' =>  $atasan3,
// 		    '#required' => FALSE,	  
// 	);	
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Save',
			'#weight' => 98,
	);
	$form['mdc_stock_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/user/view"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}
function mdc_cek_fullname($username){
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM top_person WHERE name = '$username'");
	while($row = db_fetch_array($db_data)) {
		$person_id 	= $row['person_id'];
	}
	db_set_active();
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM org_employee WHERE person_id = $person_id");
	while($row = db_fetch_array($db_data)) {
		$fullname 	= $row['fullname'];
	}
	db_set_active();
	return $fullname;
}
function mdc_user_set_submit($form, &$form_state) {
	$edit_id 	= $form_state['edit_id'];
	$username	= $form_state['username'];
	$fungsi		= $form_state['fungsi'];
	$cekusername=mdc_cekusername($username);
	if(!$cekusername){		
		drupal_set_message("Gagal simpan, username TIDAK TERDAFTAR !",'error');
		drupal_goto('mdc/online/user/set');
	}
	$cekusername1=mdc_cekusername($username,1);
	if(($cekusername1=='error') && (!$edit_id)){		
		drupal_set_message("Gagal simpan, username SUDAH ADA !",'error');
		drupal_goto('mdc/online/user/set');
	}
	
	$fullname	= mdc_cek_fullname($username);
	$lokasi		= $form_state['lokasi'];
	$akses		= $form_state['akses'];			// array()
	
// 	$ceknopek	= get_nopek_org($username);
// 	$cekrole	= $akses[183];
// 	$parent		= $form_state['atasan1'];
// 	if($ceknopek && $cekrole && !$parent){		
// 		drupal_set_message("Untuk User Pekerja HARUS MEMILIKI ATASAN !",'error');
// 		if($edit_id){
// 			drupal_goto('mdc/online/user/set/', 'id=' . $username);
// 		}else{
// 			drupal_goto('mdc/online/user/set');
// 		}
// 	}
	
	foreach ($akses as $key => $value){
		if($value==0){
			$hapus[] = $key;
		}else{
			$adduserrole = mdc_role_save($username,$value);
		}
		switch ($key){			// 181 : $akses2 => SCM -> sdh tdk dipakai
			case 178:
				$akses6 = $value;
				break;		
			case 179:
				$akses3 = $value;
				break;
			case 180:
				$akses4 = $value;
				break;	
			case 181:
				$akses2 = $value;
				break;		
			case 182:
				$akses5 = $value;
				break;	
			case 183:
				$akses1 = $value;
				break;	
		}
	}
	if(isset($hapus)){
		foreach ($hapus as $key => $rid){
			if($edit_id){
				$deleteusersrole = mdc_role_delete($username,$rid);
			}
		}
	}
// 	$atasan1	= $form_state['atasan1'];
// 	$atasan2	= $form_state['atasan2'];
// 	$atasan3	= $form_state['atasan3'];
	
	$data_roles			= mdc_user_roles();	
	$user_roles			= $data_roles['admin'];	
// 	$user_roles			= $data_roles['sa'];	
	
	if($edit_id){
		
		if($user_roles){
			db_set_active('pep');
			$hasil_update = db_query("UPDATE mdc_user SET
					fullname	= '$fullname',
					lokasi		= $lokasi,
					fungsi		= $fungsi,
					akses1		= $akses1,
					akses2		= $akses2,			
					akses3		= $akses3,
					akses4		= $akses4,
					akses5		= $akses5,
					akses6		= $akses6
					WHERE username = '$edit_id'"); // akses3		= $akses3, => 172 : ----
		}else{
			db_set_active('pep');
			$hasil_update = db_query("UPDATE mdc_user SET
					fullname	= '$fullname',
					lokasi		= $lokasi,
					fungsi		= $fungsi,
					akses1		= $akses1,
					akses2		= $akses2,
					akses3		= $akses3,
					akses4		= $akses4,
					akses5		= $akses5,
					akses6		= $akses6
					WHERE username = '$edit_id'"); // akses3		= $akses3, => 172 : ----
		}
		
		if($hasil_update){
			drupal_set_message('user UPDATE Success ...');
		}else{
			drupal_set_message('SAVE EDIT user FAILED !', 'error');
		}
		$ket['fullname'] = $fullname;
		$page = 'Edit User';
	}else{
		db_set_active('pep');
		$hasil = db_query("INSERT INTO mdc_user (username,fullname,lokasi,fungsi,akses1,akses2,akses3,akses5,akses6) VALUES ('%s','%s',%d,%d,%d,%d,%d,%d,%d)", $username,$fullname,$lokasi,$fungsi,$akses1,$akses2,$akses3,$akses5,$akses6);
		if($hasil){
			drupal_set_message('user SAVE Success ...');
		}else{
			drupal_set_message('SAVE user FAILED !', 'error');
		}
		$ket['username'] = $username;
		$page = 'Insert User';
	}
	db_set_active();
	$ket['lokasi'] = $lokasi; $ket['akses1'] = $akses1; $ket['akses2'] = $akses2; $ket['akses3'] = $akses3;
	$ket['akses5'] = $akses5; $ket['akses6'] = $akses6; $ket['fungsi'] = $fungsi;
	$hasil = mdc_logs($page,$ket);
	//$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; Username : '. $username .'; Lokasi : '. $lokasi);
	drupal_goto('mdc/online/user/view');
}
function mdc_usernametouid($username){
	db_set_active('default');
	$db_data = db_query("SELECT * FROM users WHERE name = '$username'");
	while($row = db_fetch_array($db_data)) {
		$uid 	= $row['uid'];
	}
	db_set_active();
	return $uid;
}
function mdc_user_delete() {
	$id 		= $_GET['id'];
	db_set_active('pep');
	$db_query 	= "DELETE FROM mdc_user WHERE username = '$id'";
	$result 	= db_query($db_query);
	if($result){
		drupal_set_message('Data User DELETE Success ...','error');
	}
	db_set_active();
	$page 		= 'Delete User';
	$hasil = mdc_logs($page,$id);
	//$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; username : '. $id);
	drupal_goto('mdc/online/user/view');
}
function mdc_role_delete($username,$rid) {
	$uid	= mdc_usernametouid($username);	
	db_set_active('default');
	$db_query 	= "DELETE FROM users_roles WHERE uid = $uid && rid = $rid";	
	$result 	= db_query($db_query); // mysql_query($db_query)
	if($result){
// 		drupal_set_message('Data User Role DELETE Success ...','error');
	}	
	db_set_active();
}
function mdc_cekadauidrid($uid,$rid){
	db_set_active('default');
	$db_data 		= db_query("SELECT * FROM users_roles WHERE uid = $uid && rid = $rid");
	while($row = db_fetch_array($db_data)) {
		$uidx 		= $row['uid'];
	}
	db_set_active();
	return $uidx;
}
function mdc_role_save($username,$rid) {
	$uid	= mdc_usernametouid($username);
	$cekada	= mdc_cekadauidrid($uid,$rid);	
	if(!$cekada){
		db_set_active('default');
		$hasil 		= db_query("INSERT INTO users_roles (uid,rid) VALUES (%d,%d)", $uid,$rid);
		if($hasil){
// 			drupal_set_message('user role SAVE Success ...');
		}else{
// 			drupal_set_message('SAVE user role FAILED !', 'error');
		}
		db_set_active();
	}
}
// END USER ========================================================================