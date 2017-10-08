<?php 
// PLANT ============================================================================
function mdc_plant_view_data() {
	$cat = array(1=>'Active', 2=>'Disabled');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Plant / Lokasi'),),
			array('data'=>t('Status'),),
			array('data'=>t('Edit'),),
			array('data'=>t('Delete'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_plant ORDER BY idPlant ASC");
	while($row = db_fetch_array($db_data)) {
		$edit 	= "<a href='" .base_path(). "mdc/online/plant/set/?id=" .$row['idPlant']. "'>edit</a>";		
		$cek_plant	= mdc_cek_wh_plant($row['idPlant']);
		if($cek_plant){
			$delete	= "<a href='#' title='id Plant Masih digunakan' onclick='alert(\"TIDAK dpt dihapus, ID Plant Masih Digunakan!\")'>hapus</a>";
		}else{
			$delete	= "<a href='" .base_path(). "mdc/online/plant/delete/?id=" .$row['idPlant']. "' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>hapus</a>";
		}
		$isi[] 	= array(++$xyz, $row['description'], $cat[$row['isActive']], $edit, $delete);
	}
	db_set_active();

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_cek_wh_plant($idPlant) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse WHERE idPlant = $idPlant");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['idWarehouse'];
	}
	db_set_active();
	return $hasil;
}
function mdc_plant_list() {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_plant WHERE isActive = 1");
	while($row = db_fetch_array($db_data)) {
		$hasil[$row['idPlant']]	= $row['description'];
	}
	db_set_active();
	return $hasil;
}
function mdc_plant_view() {
	$data = mdc_plant_view_data();
	$output = theme_table($data['judul'], $data['isi']);
	$hasil = "<a href='" .base_path(). "mdc/online/plant/set'>Add Plant</a> | <a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $hasil.$output;
}
function mdc_cek_plant($idWarehouse) { // idWarehouse => plantName
	$idW		= mdc_warehouse_data($idWarehouse);
	$idPlant	= $idW['idPlant'];
	$idP		= mdc_plant_data($idPlant);
	$plantName	= $idP['description'];
	return $plantName;
}
function mdc_plant_data($id) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_plant WHERE idPlant = $id ORDER BY plantCode ASC");
	while($row = db_fetch_array($db_data)) {
		$hasil['plantCode']		= $row['plantCode'];
		$hasil['description']	= $row['description'];
		$hasil['status'] 		= $row['isActive'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}
function mdc_plant_set() {
	$status_pil = array(1=>'Active', 2=>'Disabled');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	if(isset($_GET['id'])){
		$id 		= $_GET['id'];
		$data 		= mdc_plant_data($id);
		$plantCode	= $data['plantCode'];
		$description= $data['description'];
		$status 	= $data['status'];

		$form['edit_id'] = array(
				'#type' => 'hidden',
				'#default_value' => $id
		);
		$disable = 'TRUE';
	}
	$form['plantCode'] = array(
			'#type' => 'textfield',
			'#title' => t('Plant Code'),
			'#size' => 20,
			'#maxlength' => 10,
			'#weight' => 1,
			'#default_value' =>  $plantCode,
			'#required' => TRUE,
			'#disabled' => $disable,
	);
	$form['description'] = array(
			'#type' => 'textfield',
			'#title' => t('Description'),
			'#size' => 40,
			'#maxlength' => 240,
			'#weight' => 2,
			'#default_value' =>  $description,
			'#required' => TRUE,
	);
	$form['status'] = array(
			'#title' => t('Status'),
			'#type' => 'select',
			'#options' => $status_pil,
			'#default_value' =>  variable_get('status', $status),
			'#weight' => 3,
			'#required' => TRUE,
	);
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Save',
			'#weight' => 98,
	);
	$form['mdc_category_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/plant/view"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}
function mdc_plant_set_submit($form, &$form_state) {
	$edit_id 	= $form_state['edit_id'];
	$plantCode 	= $form_state['plantCode'];
	$description= $form_state['description'];
	$status 	= $form_state['status'];

	// cek apakah kodeplant sudah ada
// 	$cek_plant	= planttoid($plantCode);
	$cek_plant	= mdc_cek_plant_code($plantCode, $description);
	if($cek_plant){
		drupal_set_message('Tambah/Edit Plant GAGAL. Kode/Description Plant sudah ada', 'error');
		drupal_goto('mdc/online/plant/view');
	}	
	
	db_set_active('pep');
	if($edit_id){
		$hasil_update = db_query("UPDATE mdc_plant SET
				plantCode 			= '$plantCode',
				description			= '$description',
				isActive			= $status
				WHERE idPlant = $edit_id");
		if($hasil_update){
			drupal_set_message('Plant UPDATE Success ...');
		}else{
			drupal_set_message('SAVE EDIT Plant FAILED !', 'error');
		}
		$ket['idPlant'] = $edit_id;
		$page = 'Edit Plant';
	}else{
		$hasil = db_query("INSERT INTO mdc_plant (plantCode,description,isActive) VALUES ('%s','%s',%d)", $plantCode,$description,$status);
		if($hasil){
			drupal_set_message('Plant SAVE Success ...');
		}else{
			drupal_set_message('SAVE Plant FAILED !', 'error');
		}
		$page = 'Insert Plant';
	}
	db_set_active();
	$ket['plantCode'] = $plantCode ; $ket['description'] = $description; $ket['isActive'] = $status;
	$hasil = mdc_logs($page,$ket);
	//$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; PlantCode : '. $plantCode .'; Description : '. $description);
	drupal_goto('mdc/online/plant/view');
}
function mdc_cek_plant_code($plCode, $desc) {
	$plCode1 	= strtoupper($plCode);
	$desc1 		= strtolower($desc);
	$desc2 		= strtoupper($desc);
	$desc3 		= ucwords($desc);
	$desc4 		= ucfirst($desc);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_plant WHERE ((plantCode = '$plCode' || plantCode = '$plCode1') || (description = '$desc' || description = '$desc1' || description = '$desc2' || description = '$desc3' || description = '$desc4'))");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['idPlant'];
	}
	db_set_active();
	return $hasil;
}
function mdc_plant_delete() {
	$id 		= $_GET['id'];
	db_set_active('pep');
	$db_query 	= "DELETE FROM mdc_plant WHERE idPlant = $id";
	$result 	= mysql_query($db_query);
	if($result){
		drupal_set_message('Data Plant DELETE Success ...','error');
	}
	db_set_active();
	drupal_goto('mdc/online/plant/view');
}
// END PLANT ========================================================================