<?php 
// STOCK ============================================================================
function mdc_stock_view_data() {	
	$cat = array(1=>'Active', 2=>'Disabled');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('KIMAP'),),
			array('data'=>t('Material'),),
			array('data'=>t('Image'),),
			array('data'=>t('Warehouse'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Lokasi'),),
			array('data'=>t('Qty'),),
			array('data'=>t(''),),
			array('data'=>t('Edit'),),
			array('data'=>t('Delete'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT a.idStock, a.idMaterial, a.idWarehouse, a.idFungsi, a.qty, b.idPlant, a.file_image FROM pep.mdc_stock AS a
					LEFT JOIN pep.mdc_warehouse AS b ON b.idWarehouse = a.idWarehouse ORDER BY b.idPlant ASC,a.idWarehouse,a.qty ASC"); // WHERE b.idPlant=$lokasi_usr
	
	while($row = db_fetch_array($db_data)) {
		$edit 	= "<a href='" .base_path(). "mdc/online/stock/set/?id=" .$row['idStock']. "'>edit</a>";		
		if($row['qty'] > 0){
			$delete	= "<a href='#' title='masih ada stok' onclick='alert(\"TIDAK dpt dihapus, STOCK msh ada !\")'>hapus</a>";
		}else{
			$delete	= "<a href='" .base_path(). "mdc/online/stock/delete/?id=" .$row['idStock']. "' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>hapus</a>";
		}
		
		$usr	= mdc_user_roles(); 	// cek data login
		$un		= $usr['nama'];
		$sa		= $usr['sa']; 			// is Super Admin ??? 	(1)
		$usr_mdc= mdc_user_data($un); 	// cek data mdc
		$lokasi	= $usr_mdc['lokasi'];	// cek lokasi user mdc	(2) -> idPlant(lokasi) -> user login
		$idW	= mdc_warehouse_data($row['idWarehouse']);
		$idPlant= $idW['idPlant'];		// idPlant(lokasi) -> item(barang)
		$sat	= mdc_material_data($row['idMaterial']);
		$satuan	= $sat['satuan'];
		if($row['file_image']){
			$view = "<a class='fancybox-effects-d' href='" .base_path(). "files/portal/mdc/image/" .$row['file_image']. "' title='" .mdc_material_data_select($row['idMaterial']). "'>view</a>";
		}else{
			$view = "no image";
		}
		
		if($sa){						// cek, jika bukan SA, -> tampilkan sebatas wilayah nya saja; mdc_material_data_select($row['idMaterial']) => $sat['description']
			$isi[] 	= array(++$xyz,$sat['materialCode'] , $sat['description'], $view, mdc_warehouse_data_select($row['idWarehouse']), mdc_fungsi_data_select($row['idFungsi']), mdc_cek_plant($row['idWarehouse']), $row['qty'], $satuan, $edit, $delete);			
		}elseif($lokasi == $idPlant){
			$isi[] 	= array(++$xyz,$sat['materialCode'] , $sat['description'], $view, mdc_warehouse_data_select($row['idWarehouse']), mdc_fungsi_data_select($row['idFungsi']), mdc_cek_plant($row['idWarehouse']), $row['qty'], $satuan, $edit, $delete);
		}
		$view = '';
	}
	db_set_active();

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_warehouse_data_select($id = null) {
	db_set_active('pep');
	if($id){
		$db_data = db_query("SELECT * FROM mdc_warehouse WHERE idWarehouse = $id");
	}else{
		$db_data = db_query("SELECT * FROM mdc_warehouse WHERE isActive = 1 ORDER BY description ASC");
	}	
	while($row = db_fetch_array($db_data)) {		
		if($id){
			$hasil = $row['description'];
		}else{
			$hasil[$row['idWarehouse']] = $row['description'] .' - '. mdc_cek_plant($row['idWarehouse']);
		}		
	}
	db_set_active();
	return $hasil;
}
function mdc_fungsi_data_select($id = null) {
	db_set_active('pep');
	if($id){
		$db_data = db_query("SELECT * FROM mdc_fungsi WHERE idFungsi = $id");
	}else{
		$db_data = db_query("SELECT * FROM mdc_fungsi WHERE isActive = 1 ORDER BY description ASC");
	}	
	while($row = db_fetch_array($db_data)) {			
		if($id){
			$hasil = $row['description'];
		}else{
			$hasil[$row['idFungsi']] = $row['description'];
		}
	}
	db_set_active();
	return $hasil;
}
function mdc_material_data_select($id = null) {
	db_set_active('pep');
	if($id){
		$db_data = db_query("SELECT * FROM mdc_material WHERE id = $id");
	}else{
		$db_data = db_query("SELECT * FROM mdc_material WHERE isActive = 1 ORDER BY description ASC");
	}	
	while($row = db_fetch_array($db_data)) {
		if($id){
			$hasil = $row['materialCode']." - ".$row['description'];
		}else{
			$hasil[$row['id']] = $row['materialCode']." - ".$row['description'];
		}
	}
	db_set_active();
	return $hasil;
}
function mdc_stock_data($id) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_stock WHERE idstock = $id");
	while($row = db_fetch_array($db_data)) {
		$hasil['idMaterial'] 	= $row['idMaterial'];
		$hasil['idWarehouse']	= $row['idWarehouse'];
		$hasil['idFungsi']		= $row['idFungsi'];
		$hasil['tujuan']		= $row['tujuan_penggunaan'];
		$hasil['tgl']			= $row['tanggal_penggunaan'];
		$hasil['qty']			= $row['qty'];
		$hasil['rsvQty']		= $row['rsvQty'];
		$hasil['po']			= $row['po'];
		$hasil['price']			= $row['price'];
		$hasil['file_image']	= $row['file_image'];
	}
	db_set_active();
	return $hasil;
}
function mdc_stock_view() {
	drupal_add_js(drupal_get_path('module', 'atk') . "/js/jquery-1.8.2.min.js");
	drupal_add_js(drupal_get_path('module', 'atk') . "/js/jquery.fancybox.js");
	drupal_add_css(drupal_get_path('module', 'atk').'/css/jquery.fancybox.css');
	$jss = "<script type='text/javascript'>
		$(document).ready(function() {
			$('.fancybox-effects-d').fancybox({
				padding: 0,
				openEffect : 'elastic',
				openSpeed  : 150,
				closeEffect : 'elastic',
				closeSpeed  : 150,
				closeClick : true,
				helpers : {
				overlay : null
				}
			});
		});
	</script>";	
	$data = mdc_stock_view_data();
	$output = theme_table($data['judul'], $data['isi']);
	$hasil = "<a href='" .base_path(). "mdc/online/stock/set'>Set Initial Stock</a> | <a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $jss.$hasil.$output;
}
function mdc_warehouse_same_plant($idPlant) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse WHERE idPlant = $idPlant");
	while($row = db_fetch_array($db_data)) {
		$hasil[$row['idWarehouse']] = $row['description'] .' - '. mdc_cek_plant($row['idWarehouse']);
	}
	db_set_active();
	return $hasil;
}
function mdc_material_name($string) {			// autofill
	db_set_active('pep');
	$db_data = db_query("SELECT id, materialCode, description FROM mdc_material 
							WHERE  isActive = 1 && (materialCode LIKE '%" .$string. "%' OR description LIKE '%" .$string. "%' ORDER BY materialCode)");
	while($row = mysql_fetch_array($db_data)) {
		$id				= $row['id'];
		$materialCode	= $row['materialCode'];
		$description	= $row['description'];
		$data[$id .'-'. $materialCode .'-'. $description] = $materialCode .' - '. $description;
	}
	db_set_active();

	print drupal_to_js($data);
	exit();
}
function mdc_stock_set() {
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$user_lokasi		= mdc_cek_user_lokasi(); // hasil plant (int)
	$data_roles			= mdc_user_roles();
	$user_roles			= $data_roles['admin'];
	$data_plant			= mdc_plant_data($user_lokasi);
	$nama_lokasi		= $data_plant['description'];
	
	if($user_roles){ // jika user ADMIN
		$wh_option			= mdc_warehouse_same_plant($user_lokasi);
	}else{
		$wh_option			= mdc_warehouse_data_select();
	}
	
	if(!isset($wh_option)){
		$form['mdc_stock1_markup'] = array(
				'#value' => t('Belum ada warehouse utk lokasi '.$nama_lokasi),
				'#weight' => 98,
		);
		$form['mdc_stock2_markup'] = array(
				'#value' => t('<p><a href="' .base_path(). 'mdc/online/stock/view"><input type="button" value="Cancel" /></a>'),
				'#weight' => 99,
		);
		return $form;
	}
	
	if(isset($_GET['id'])){
		$id				= $_GET['id'];
		$data 			= mdc_stock_data($id);
		$idMaterial 	= $data['idMaterial'];
		$idWarehouse 	= $data['idWarehouse'];
		$idFungsi 		= $data['idFungsi'];
		$qty 			= $data['qty'];
		$file_image		= $data['file_image'];
		$disabled		= 'disabled';
		
		$form['edit_id'] = array(
				'#type' => 'hidden',
				'#default_value' => $id
		);
	}
	$form['idMaterial'] = array(
		  	'#title' => t('Material'),
		    '#type' => 'textfield',	    
		    '#size' => 50,	
		    '#maxlength' => 60, 
		    '#weight' => 1, 
		    '#autocomplete_path' => 'mdc/online/material/name' ,
		    '#default_value' =>  $material,
		    '#required' => TRUE,	
			'#disabled' => $disabled
	);
// 	$form['idMaterial'] = array(
// 			'#title' => t('Material'),
// 			'#type' => 'select',
// 			'#options' => mdc_material_data_select(),
// 			'#default_value' =>  variable_get('idMaterial', $idMaterial),
// 			'#weight' => 1,
// 			'#disabled' => $disabled,
// 			'#required' => TRUE,
// 	);
	$form['idWarehouse'] = array(
			'#title' => t('Warehouse'),
			'#type' => 'select',
			'#options' => $wh_option,
			'#default_value' =>  variable_get('idWarehouse', $idWarehouse),
			'#weight' => 2,
			'#disabled' => $disabled,
			'#required' => TRUE,
	);
	$form['idFungsi'] = array(
			'#title' => t('Fungsi'),
			'#type' => 'select',
			'#options' => mdc_fungsi_data_select(),
			'#default_value' =>  variable_get('idFungsi', $idFungsi),
			'#weight' => 3,
			'#required' => TRUE,
	);
	$form['qty'] = array(
			'#type' => 'textfield',
			'#title' => t('Quantity'),
			'#size' => 10,
			'#maxlength' => 5,
			'#weight' => 4,
			'#default_value' =>  $qty,
			'#required' => TRUE,
	);
	
	if($file_image){
		$ket = 'Image sdh ada, akan me-replace image sebelumnya';
	}else{
		$file_image = '';
		$ket = '';
	}	
	$form['file_image'] = array(
			'#title' => 'File Image',
			'#type' => 'file',
			'#weight' => 9,
			'#default_value' => $file_image,
			'#description' => $ket,
			'#required' => FALSE,
	); 
	
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Save',
			'#weight' => 98,
	);
	$form['mdc_stock_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/stock/view"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}
function mdc_stock_set_submit($form, &$form_state) {
	$edit_id 	= $form_state['edit_id'];
	$idMat		= $form_state['idMaterial'];
	$idMateri	= explode('-',$idMat);
	$idMaterial = $idMateri[0];
	$idWarehouse= $form_state['idWarehouse'];
	$idFungsi	= $form_state['idFungsi'];
	$qty		= $form_state['qty'];
	
	// Simpan File Image
	$check_file = file_check_upload('file_image');
	if($check_file) {
		$nm_ext = explode('.',$check_file->filename);
		$nm_ext = $nm_ext[1];
		$nama_file	= '_' .date('His'). '_' .$idMaterial. '_' .$idWarehouse. '_' .$idFungsi. '_.' .$nm_ext;
		
		// jika UPDATE
		if($edit_id){
			$nama_file	= '_' .$edit_id. '_' .$idMaterial. '_' .$idWarehouse. '_' .$idFungsi. '_.' .$nm_ext;
		}
		// End jika UPDATE
		
		// cek nama file yg sama
		$lokasi = 'files/portal/mdc/image/'.$nama_file;
		if(is_file($lokasi)){
			$hapus = unlink($lokasi);
		}
		// END cek nama file yg sama
	
		$file = file_save_upload($check_file, 'files/portal/mdc/image/'.$nama_file);
		if ($file === 0) {
			drupal_set_message("Gagal simpan file: di dir > ".'files/portal/mdc/image/'.$nama_file,'error');
		}
	}else{
		$nama_file = $form_state['file_image'];
	}
	
	db_set_active('pep');
	if($edit_id){
		$hasil_update = db_query("UPDATE mdc_stock SET
				idMaterial 	= $idMaterial,
				idWarehouse = $idWarehouse,
				idFungsi 	= $idFungsi,
				qty			= $qty,
				file_image	= '$nama_file'
				WHERE idStock = $edit_id");// update (rsvQty		= $qty) di hapus !
		if($hasil_update){
			drupal_set_message('Stock UPDATE Success ...');
		}else{
			drupal_set_message('SAVE EDIT Stock FAILED !', 'error');
		}
		$ket['idStock'] = $edit_id;
		$page = 'Edit Stock';
		mdc_hist_stok($edit_id, 3, 0, $qty, "Update Stock Manual menjadi $qty");
	}else{
		$cek_item_sama = mdc_cek_item_sama($idMaterial,$idWarehouse);
		if(!$cek_item_sama){
			db_set_active('pep');
			$hasil = db_query("INSERT INTO mdc_stock (idMaterial,idWarehouse,idFungsi,qty,rsvQty,file_image) VALUES (%d,%d,%d,%d,%d,'%s')", $idMaterial,$idWarehouse,$idFungsi,$qty,0,$nama_file);
			if($hasil){
				drupal_set_message('Stock SAVE Success ...');
			}else{
				drupal_set_message('SAVE stock FAILED !', 'error');
			}
			$page = 'Insert Stock';
		}else{
			drupal_set_message('material tsb sdh ada pada lokasi yg sama !', 'error');
		}
		$query = db_query("select * from mdc_stock where idMaterial = $idMaterial and idWarehouse = $idWarehouse and idFungsi = $idFungsi");
		$result = db_fetch_array($query);
		mdc_hist_stok($result['idStock'], 3, 0, $qty, "Set Inital Stock Manual menjadi $qty");
	}
	db_set_active();
	$ket['idMaterial'] = $idMaterial ; $ket['idWarehouse'] = $idWarehouse; $ket['qty'] = $qty; $ket['file_image'] = $file_image;
	$hasil = mdc_logs($page,$ket);
	drupal_goto('mdc/online/stock/view');
}
function mdc_stock_delete() {
	$id 		= $_GET['id'];
	db_set_active('pep');
	$db_query 	= "DELETE FROM mdc_stock WHERE idStock = $id";
	$result 	= mysql_query($db_query);
	if($result){
		drupal_set_message('Data Stock DELETE Success ...','error');
	}
	db_set_active();
	$page 		= 'Delete Stock';
	$ket 		= 'idStock : ' .$id;
	$hasil = mdc_logs($page,$ket);
	drupal_goto('mdc/online/stock/view');
}
function mdc_stock_cek($idStock) { // mdc_stock idStock, qty
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_stock WHERE idStock = $idStock");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['qty'];
	}
	db_set_active();
	return $hasil;
}
function mdc_cek_item_sama($idMaterial,$idWarehouse) { // cek $idMaterial,$idWarehouse -> ada ?
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_stock WHERE idMaterial = $idMaterial && idWarehouse = $idWarehouse");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['qty'];
	}
	db_set_active();
	return $hasil;
}
// END STOCK ========================================================================