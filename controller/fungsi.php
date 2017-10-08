<?php 
// fungsi ========================================================================
function mdc_fungsi_view_data() {
	$stat = array(1=>'Active', 2=>'Disabled');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Lokasi'),),
			array('data'=>t('CostCenter'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Status'),),
			array('data'=>t('Edit'),),
			array('data'=>t('Delete'),)
	);	
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_fungsi ORDER BY idPlant Desc");	
	while($row = db_fetch_array($db_data)) {
// 		$cek_mat = mdc_fung_data($row['idFungsi']);
// 		if($cek_mat == TRUE){
// 			$delete	= "<a href='#' title='masih ada stok' onclick='alert(\"Tidak dapat dihapus, Fungsi masih digunakan!\")'>hapus</a>";
// 		}else{
			$delete	= "<a href='" .base_path(). "mdc/online/fungsi/delete/?id=" .$row['idFungsi']. "' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>hapus</a>";
// 		}
		$pl = mdc_plant_data($row['idPlant']); $plant = $pl['description'];
		$edit 	= "<a href='" .base_path(). "mdc/online/fungsi/set/?id=" .$row['idFungsi']. "'>edit</a>";		
		$isi[] 	= array(++$xyz, $plant, $row['cost'], $row['description'], $stat[$row['isActive']], $edit, $delete);
	}
	db_set_active();
	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
// function mdc_fung_data($idFung) {
// 	// cek data Material
// 	$hasil		= FALSE;
// 	db_set_active('pep');
// 	$db_data = db_query("SELECT * FROM mdc_material WHERE fungsi = $idFung");
// 	while($row = db_fetch_array($db_data)) {
// 		$hasil		= TRUE;
// 	}
// 	db_set_active();
// 	// End cek data Material
// 	return $hasil;
// }
function mdc_fungsi_view() {
	$data = mdc_fungsi_view_data();
	$output = theme_table($data['judul'], $data['isi']);
  	$hasil = "<a href='" .base_path(). "mdc/online/fungsi/set'>Add Fungsi</a> | <a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $hasil.$output;
}
function mdc_fungsi_name($idFungsi) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_fungsi WHERE idFungsi = $idFungsi");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['description'];
	}
	db_set_active();
	return $hasil;
}
function mdc_fungsi_data($idFungsi) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_fungsi WHERE idFungsi = $idFungsi");
	while($row = db_fetch_array($db_data)) {
		$hasil['idFungsi'] 		= $row['idFungsi'];
		$hasil['description'] 	= $row['description'];
		$hasil['isActive']		= $row['isActive'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}
function mdc_fungsi_set() {
	$status_pil = array(1=>'Active', 2=>'Disabled');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	if(isset($_GET['id'])){
		$id 		= $_GET['id'];	
		// ambil data
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_fungsi WHERE idFungsi = $id ORDER BY description ASC");
		while($row = db_fetch_array($db_data)) {
			$idPlant 	= $row['idPlant'];
			$fungsi 	= $row['description'];
			$cost 		= $row['cost'];
			$status 	= $row['isActive'];			
		}
		db_set_active();
		// End ambil data
		
		$disable = 'TRUE';
		$form['edit_id'] = array(
				'#type' => 'hidden',
				'#default_value' => $id
		);	
	}
	$form['idPlant'] = array(
			'#title' => t('Plant'),
			'#type' => 'select',
			'#options' => mdc_plant_data_select(),
			'#default_value' =>  variable_get('plant', $idPlant),
			'#weight' => 0,
			'#required' => TRUE,
			'#disabled' => $disable,
	);
	$form['fungsi'] = array(
		'#type' => 'textfield',
		'#title' => t('Fungsi'),
		'#size' => 20,	
		'#maxlength' => 240, 
		'#weight' => 1,
		'#default_value' =>  $fungsi,
		'#required' => TRUE, 	
	);
	$form['cost'] = array(
		'#type' => 'textfield',
		'#title' => t('CostCenter'),
		'#size' => 20,	
		'#maxlength' => 10, 
		'#weight' => 1,
		'#default_value' =>  $cost,
		'#required' => TRUE, 	
	);
	$form['status'] = array(
		'#title' => t('Status'),
		'#type' => 'select',	
		'#options' => $status_pil,
		'#default_value' =>  variable_get('status', $status),
		'#weight' => 2,
		'#required' => TRUE, 	
	);	
	$form['submit'] = array(
		'#type' => 'submit',
		'#value' => 'Save',
		'#weight' => 98,
	);
	$form['mdc_fungsi_markup'] = array(
		'#value' => t('<a href="' .base_path(). 'mdc/online/fungsi/view"><input type="button" value="Cancel" /></a>'),
		'#weight' => 99,
	);
	return $form;
}
function mdc_fungsi_set_submit($form, &$form_state) {
	$edit_id 	= $form_state['edit_id'];
	$cost 		= $form_state['cost'];
	$fungsi 	= $form_state['fungsi'];
	$status 	= $form_state['status'];	
	
	if(!$edit_id){
		$cek_data 	= mdc_cek_fungsi_data($fungsi);
		if($cek_data){
			drupal_set_message('Data Fungsi SUDAH ADA !', 'error');
			drupal_goto('mdc/online/fungsi/view');
		}
	}
	
	db_set_active('pep');
	if($edit_id){
		$hasil_update = db_query("UPDATE mdc_fungsi SET
				cost 				= '$cost',
				description 		= '$fungsi',
				isActive			= $status
			WHERE idFungsi = $edit_id");
		if($hasil_update){
			drupal_set_message('fungsi UPDATE Success ...');
		}else{
			drupal_set_message('SAVE EDIT fungsi FAILED !', 'error');
		}
		$ket['idFungsi'] = $edit_id;
		$page = 'Edit fungsi';
	}else{
		$hasil = db_query("INSERT INTO mdc_fungsi (cost,description,isActive) VALUES ('%s','%s',%d)", $cost,$fungsi,$status);
		if($hasil){
			drupal_set_message('fungsi SAVE Success ...');
		}else{
			drupal_set_message('SAVE fungsi FAILED !', 'error');
		}		
		$page = 'Insert fungsi';
	}
	db_set_active();
	$ket['Cost'] = $cost; $ket['Description'] = $fungsi; $ket['Status'] = $status;
	$hasil = mdc_logs($page,$ket);
	//$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; Status : '. $status .'; Description : '. $description);
	
	drupal_goto('mdc/online/fungsi/view');
}
function mdc_cek_fungsi_data($key) {
	// cek data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_fungsi WHERE description LIKE '$key'");
	while($row = db_fetch_array($db_data)) {
		$hasil 		= $row['idFungsi'];
	}
	db_set_active();
	// End cek data
	return $hasil;
}
function mdc_fungsi_delete() {
	$id 		= $_GET['id'];
	db_set_active('pep');
	$db_query 	= "DELETE FROM mdc_fungsi WHERE idFungsi = $id";
	$result 	= mysql_query($db_query);
	if($result){
		drupal_set_message('Data fungsi DELETE Success ...','error');
		$page = 'Delete WH';
	}
	db_set_active();
	
	$ket['idFungsi'] = $id ;
	$hasil = mdc_logs($page,$ket);
	//$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; idFungsi : '. $id);
	drupal_goto('mdc/online/fungsi/view');
}
function mdc_fungsi_all() {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_fungsi WHERE isActive = 1");
	while($row = db_fetch_array($db_data)) {
		$hasil[$row['idFungsi']]	= $row['description'];
	}
	db_set_active();
	return $hasil;
}
function mdc_fungsi_list($user_fungsi = NULL) {
	$fungsi_options 	= mdc_fungsi_all();
	if($user_fungsi==''){
		return $fungsi_options;
	}elseif($user_fungsi>=0){
		return $fungsi_options[$id];
	}
}
// END fungsi ========================================================================