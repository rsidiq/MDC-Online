<?php 
// WAREHOUSE ============================================================================
function mdc_warehouse_view_data() {
	$cat = array(1=>'Active', 2=>'Disabled');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Plant'),),
			array('data'=>t('Warehouse / Gudang'),),
			array('data'=>t('Status'),),
			array('data'=>t('Edit'),),
			array('data'=>t('Delete'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse ORDER BY idPlant ASC");
	while($row = db_fetch_array($db_data)) {
		$cek_stok	= mdc_cek_stok_wh($row['idWarehouse']);
		$idP		= mdc_plant_data($row['idPlant']);
		$plantName	= $idP['description'];
		$edit 	= "<a href='" .base_path(). "mdc/online/warehouse/set/?id=" .$row['idWarehouse']. "'>edit</a>";
		if($cek_stok){
			$delete	= "<a href='#' title='masih ada stok' onclick='alert(\"TIDAK dpt dihapus, STOCK msh ada !\")'>hapus</a>";
		}else{
			$delete	= "<a href='" .base_path(). "mdc/online/warehouse/delete/?id=" .$row['idWarehouse']. "' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>hapus</a>";
		}
		$isi[] 	= array(++$xyz, $plantName, $row['description'], $cat[$row['isActive']], $edit, $delete);
	}
	db_set_active();

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_cek_stok_wh($idWarehouse) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_stock WHERE idWarehouse = $idWarehouse");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['idStock'];
	}
	db_set_active();
	return $hasil;
}
function mdc_plant_data_select() {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT idPlant, description FROM mdc_plant WHERE isActive = 1 ORDER BY idPlant ASC");
	while($row = db_fetch_array($db_data)) {
		$hasil[$row['idPlant']] = $row['description'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}
function mdc_warehouse_data($id) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse WHERE idWarehouse = $id");
	while($row = db_fetch_array($db_data)) {
		$hasil['idPlant'] 		= $row['idPlant'];
		$hasil['warehouseCode'] = $row['warehouseCode'];
		$hasil['description']	= $row['description'];
		$hasil['isActive']		= $row['isActive'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}
function mdc_warehouse_view() {
	$data = mdc_warehouse_view_data();
	$output = theme_table($data['judul'], $data['isi']);
	$hasil = "<a href='" .base_path(). "mdc/online/warehouse/set'>Add Warehouse</a> | <a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $hasil.$output;
}
function mdc_warehouse_set() {
	$status_pil = array(1=>'Active', 2=>'Disabled');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	if(isset($_GET['id'])){
		$id 			= $_GET['id'];
		$data 			= mdc_warehouse_data($id);
		$idPlant 		= $data['idPlant'];
		$warehouseCode 	= $data['warehouseCode'];
		$description 	= $data['description'];
		$status 		= $data['isActive'];
		
		$form['edit_id'] = array(
				'#type' => 'hidden',
				'#default_value' => $id
		);
		$disable = 'TRUE';
	}
	$form['idPlant'] = array(
			'#title' => t('Plant'),
			'#type' => 'select',
			'#options' => mdc_plant_data_select(),
			'#default_value' =>  variable_get('plant', $idPlant),
			'#weight' => 1,
			'#required' => TRUE,
			'#disabled' => $disable,
	);
	$form['warehouseCode'] = array(
			'#type' => 'textfield',
			'#title' => t('Warehouse Code'),
			'#size' => 10,
			'#maxlength' => 10,
			'#weight' => 2,
			'#default_value' =>  $warehouseCode,
			'#required' => TRUE,
	);
	$form['description'] = array(
			'#type' => 'textfield',
			'#title' => t('Warehouse Name'),
			'#size' => 20,
			'#maxlength' => 240,
			'#weight' => 3,
			'#default_value' =>  $description,
			'#required' => TRUE,
	);
	$form['status'] = array(
			'#title' => t('Status'),
			'#type' => 'select',
			'#options' => $status_pil,
			'#default_value' =>  variable_get('status', $status),
			'#weight' => 4,
			'#required' => TRUE,
	);
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Save',
			'#weight' => 98,
	);
	$form['mdc_warehouse_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/warehouse/view"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}
function mdc_warehouse_set_submit($form, &$form_state) {
	$edit_id 	= $form_state['edit_id'];
	$idPlant 	= $form_state['idPlant'];
	$warehouseCode 	= $form_state['warehouseCode'];
	$description 	= $form_state['description'];
	$status 	= $form_state['status'];
	
	// cek idPlant & description yg sama pada plant yg sama. => skip penyimpanan
	$cek_wh	= mdc_waretoid($description,$idPlant);
	if($cek_wh){
		drupal_set_message('Nama Warehouse Sudah Ada di Plant yang Sama', 'error');
		drupal_goto('mdc/online/warehouse/view');
	}
	
	db_set_active('pep');
	if($edit_id){		
		$hasil_update = db_query("UPDATE mdc_warehouse SET
				idPlant 			= '$idPlant',
				warehouseCode 		= '$warehouseCode',
				description 		= '$description',
				isActive			= $status
				WHERE idWarehouse = $edit_id");
		if($hasil_update){
			drupal_set_message('Warehouse UPDATE Success ...');
		}else{
			drupal_set_message('SAVE EDIT Warehouse FAILED !', 'error');
		}
		$page = 'Edit WH';
	}else{
		$hasil = db_query("INSERT INTO mdc_warehouse (idPlant,warehouseCode,description,isActive) VALUES (%d,'%s','%s',%d)", $idPlant,$warehouseCode,$description,$status);
		if($hasil){
			drupal_set_message('Warehouse SAVE Success ...');
		}else{
			drupal_set_message('SAVE Warehouse FAILED !', 'error');
		}
		$page = 'Insert WH';
	}
	db_set_active();	

	$ket['idPlant'] = $idPlant ; $ket['warehouseCode'] = $warehouseCode; $ket['description'] = $description;
	$hasil = mdc_logs($page,$ket);
// 	$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; WH Code : '. $warehouseCode .'; Description : '. $description .'; idPlant : '. $idPlant);
	drupal_goto('mdc/online/warehouse/view');
}
function mdc_warehouse_delete() {
	$id 		= $_GET['id'];
	db_set_active('pep');
	$db_query 	= "DELETE FROM mdc_warehouse WHERE idWarehouse = $id";
	$result 	= mysql_query($db_query);
	if($result){
		drupal_set_message('Data Warehouse DELETE Success ...','error');
		$page = 'Delete WH';
	}
	db_set_active();
	
	$ket['WH_Code'] = $id ;
	$hasil = mdc_logs($page,$ket);
// 	$hist = mdc_hist_stok(0, 0, 0, 0, $page .'; WH Code : '. $id);
	drupal_goto('mdc/online/warehouse/view');
}
// END WAREHOUSE ========================================================================