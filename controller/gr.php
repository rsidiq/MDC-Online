<?php 
// Upload Initial Stock ============================================================================
function mdc_upload_gr(){
	$set = arg(5);
	$txtJudul 	= 'Upload Initial Stock';
	drupal_add_js("$(document).ready(function() { $('div#app-title h2').text('" .$txtJudul."'); })", "inline");
	if(isset($set)){
		$hasil = mdc_update_disabled_users();
		foreach ($hasil as $uid){
			db_set_active('default');
			$hasil_update = db_query("UPDATE users SET status = 0 WHERE uid = $uid");
			var_dump($uid);
			db_set_active();
		}
		drupal_goto('mdc/online/master/upload/gr');
	}
	$lokasi = base_path().file_directory_path()."/mdc/gr/gr_tpl.xlsx";
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['gr'] = array(
			'#type' => 'fieldset',
			'#title' => t('Upload Initial Stock'),
			'#weight' => 1,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$form['gr']['gr_file'] = array(
			'#type' => 'file',
			'#title' => '<a href="' .$lokasi. '">Download Template Initial Stock</a><p>'.'Select your file',
			'#size' => 30,
	);
	$form['gr']['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Import',
	);
	return $form;
}
function mdc_update_disabled_users(){
	db_set_active('default');
	$db_data = db_query("SELECT * FROM _users_delete");
	while($row = db_fetch_array($db_data)) {
		$hasil[] 	= $row['uid'];
	}
	db_set_active();
	return $hasil;
}
function mdc_upload_gr_submit($form, &$form_state) {
	$check_file = file_check_upload('gr_file');
	$mime1 = 'application/vnd.ms-excel';
	$mime2 = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	$isimime = $check_file->filemime;
	if($isimime == $mime2) {
		$isi_sblm 		= file_directory_path().DIRECTORY_SEPARATOR."mdc/gr" .DIRECTORY_SEPARATOR;
		if ($isimime == $mime1) {
			$nama_file 	= 'dataExcel.xls';			
		}else{
			$nama_file 	= 'dataExcel.xlsx';
		}
		if($isi_sblm . $nama_file){
			unlink($isi_sblm . $nama_file);
		}
		
		//Save File
		$path 		= file_directory_path().DIRECTORY_SEPARATOR."mdc/gr" .DIRECTORY_SEPARATOR. $nama_file;
		$file 		= file_save_upload('gr_file',$path);
		// END Simpan File
		if ($file === 0) {
			drupal_set_message("Gagal simpan file !",'error');
		}else{
			$hasil = mdc_gr_from_excel_simpan($nama_file); // <= simpan ke database
		}
		drupal_goto('mdc/online/master/upload/gr');
	}
	drupal_set_message("Gagal simpan, Only Type : *.xlsx ",'error');
	return FALSE;
}
function mdc_gr_from_excel_simpan($nama_file) {
	$lokasi 		= file_directory_path()."/mdc/gr/" .$nama_file;
	$objReader 		= PHPExcel_IOFactory::createReader('Excel2007');
	$objPHPExcel 	= $objReader->load($lokasi);
	$objWorksheet 	= $objPHPExcel->setActiveSheetIndex(0);
	$totalRow 		= $objWorksheet->getHighestRow();
	$sheetCount 	= $objPHPExcel->getSheetCount();
	// 	$sheetNames = $objPHPExcel->getSheetNames();

	// Cek AA1
	$cek_template	= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('AA1')->getValue());
	// Cek ke-Asli-an Template : AA1 => 'ASLI'
	if($cek_template != 'ASLI'){
		drupal_set_message("Format Data TIDAK SAMA !<br>Gunakan template yg disediakan.",'error');
		drupal_goto('mdc/online/master/upload/gr');
	}
	
	$gagal 	= 0;
	for($x=2;$x<=$totalRow;$x++){
		$kimaps						= $objPHPExcel->setActiveSheetIndex(0)->getCell('B'.$x)->getValue();
		if($kimaps){
			$data[$kimaps]['kimaps']	= trim($kimaps); 																		// mdc_material -> materialCode
			$data[$kimaps]['plant']		= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('C'.$x)->getCalculatedValue()); 	// mdc_plant
			$data[$kimaps]['wh']		= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('D'.$x)->getValue()); 				// mdc_warehouse
			$data[$kimaps]['fungsi']	= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('E'.$x)->getValue()); 				// Fungsi
			$data[$kimaps]['po']		= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('F'.$x)->getValue()); 				// PO
			$data[$kimaps]['gr']		= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('G'.$x)->getValue()); 				// GR
			$data[$kimaps]['qty']		= $objPHPExcel->setActiveSheetIndex(0)->getCell('H'.$x)->getValue(); 					// mdc_stock (idStock,idMaterial,idWarehouse,qty,rsvQty)
			$data[$kimaps]['harga']		= $objPHPExcel->setActiveSheetIndex(0)->getCell('I'.$x)->getValue(); 					// price : mdc_material$data[$kimaps]['desc']		= $objPHPExcel->setActiveSheetIndex(0)->getCell('H'.$x)->getValue();				
			
			// ====================== CEK : jika ada Simpan ; tidak ada, batalkan semua ===================================================
			$cek_kimap			= mdc_get_material_code($kimaps);
			if(!$cek_kimap){ // jika TDK ada, -> simpan ke array
				$gagal 			= 1;
				$kimap_gagal[]	= $kimaps;
			}
			// ====================== END CEK : jika ada Simpan ; tidak ada, batalkan semua ================================================
		}
	}
	
	// ====================== CEK : jika ada Simpan ; tidak ada, batalkan semua ===================================================
	if($gagal == 0){
		// loop array data yg ada
		foreach ($data as $key => $value){
			$plant		= $value['plant'];
			$wh			= $value['wh'];
			$fungsi		= $value['fungsi'];
			$po			= $value['po'];
			$gr			= $value['gr'];
			$qty		= $value['qty'];
			$harga		= $value['harga'];
			
			// cek stok ADA/BLM : $idMaterial && $idWarehouse			
			$planttoid 	= mdc_planttoid($plant);
			$waretoid 	= mdc_waretoid($wh, $planttoid); // $warehouse, $idPlant  // description idPlant			
			$data_mat	= mdc_get_material_kimap($key); // $key => KIMAPS
			$idFungsi 	= mdc_fungsitoid($fungsi);
			$mat_id		= $data_mat['id'];
			$cek_stok 	= mdc_cek_stok($mat_id, $waretoid); // $mattoid,$idWarehouse
			$stok_id	= $cek_stok['idStock'];
			$tmbhQty	= $qty + $cek_stok['qty'];
			if($harga > 0){
				$harga		= $value['harga'];
			}else{
				$harga		= $cek_stok['price'];
			}
			
			if(isset($cek_stok)){
				db_set_active('pep');
				$hasil_update = db_query("UPDATE mdc_stock SET
						idFungsi		= $idFungsi,
						po				= '$po',
						gr				= '$gr',
						qty				= $tmbhQty,
						price			= $harga
						WHERE idStock 	= $stok_id");
				db_set_active();

				$qty2 = $tmbhQty - $qty;
				$page = 'Update Data Stok (Upload Initial Stock)';
				$ket['idMaterial'] = $mat_id; $ket['idWarehouse'] = $idWH; $ket['idFungsi'] = $idFungsi;
				$ket['po'] = $po; $ket['gr'] = $gr; $ket['qty'] = $qty; $ket['price'] = $harga;
				$hasil = mdc_logs($page,$ket);
				$hist = mdc_hist_stok($stok_id, 3, $qty, $qty2, $page .'; PO : '. $po .'; GR : '. $gr);
			}else{
// 				drupal_set_message("Kombinasi Material dan Warehouse tidak ditemukan, Proses Simpan GAGAL !","error");
// 				drupal_goto('mdc/online/master/upload/gr');
				
				// cek keberadaan GUDANG / WH 
				// simpan_warehouse($warehouse,$idPlant)
				$idWH = mdc_cek_wh($wh);
				if(!$idWH){
					$simpan = mdc_simpan_warehouse($wh,$planttoid);
				}
				$idWH = mdc_cek_wh($wh);				
				// idMaterial	idWarehouse	qty price : <<= harus disimpen
				db_set_active('pep');
				$hasil = db_query("INSERT INTO mdc_stock (idMaterial,idWarehouse,idFungsi,po,gr,qty,price) VALUES (%d,%d,%d,'%s','%s',%d,%d)", $mat_id,$idWH,$idFungsi,$po,$gr,$qty,$harga);
				db_set_active();
				
				$page = 'Insert Data Baru Stok (Upload GR)';
				$ket['idMaterial'] = $mat_id; $ket['idWarehouse'] = $idWH; $ket['idFungsi'] = $idFungsi;
				$ket['po'] = $po; $ket['gr'] = $gr; $ket['qty'] = $qty; $ket['price'] = $harga;
				$hasil = mdc_logs($page,$ket);
				$hist = mdc_hist_stok($stok_id, 3, 0, $qty, $page .'; PO : '. $po .'; GR : '. $gr);
			}
		}
		// END - loop array data yg ada
		
		drupal_set_message("File Save to Database, Complete !");
		drupal_goto('mdc/online/master/upload/gr');
	}else{
		$page = 'Ada KIMAPS yg TDK TERDAFTAR, Proses Simpan GAGAL';
		$ket['Empty'] = '-';
		$hasil = mdc_logs($page,$ket);
		
		// tampilkan no KIMAPS yg tdk terdaftar (loop array)
		$hasil = "<p><strong>LIST KIMAPS yg TDK TERDAFTAR :</strong></p>";
		$hasil .= "<p><a href='" .base_path(). "mdc/online/master/upload/gr'>[back]</a></p>";
		
		$page = 'KIMAP TDK ADA';
		foreach ($kimap_gagal as $key => $value){
			$hasil .= ++$xyz .'. '. $value . '<br>';			
			
			$ket['no'] = $xyz; $ket['value'] = $value;
			$hasil = mdc_logs($page,$ket);
		}
// 		$hasil .= '<script>alert("Ada kimaps yg belum TERDAFTAR");</script>';
		drupal_set_message("Ada KIMAPS yg TDK TERDAFTAR, Proses Simpan GAGAL !","error");
		return $hasil;
	}	
	// ====================== END CEK : jika ada Simpan ; tidak ada, batalkan semua ================================================
}

function mdc_cek_wh($warehouse) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse WHERE description = '$warehouse'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['idWarehouse'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}

function mdc_get_material_code($materialCode) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_material WHERE materialCode = '$materialCode'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['materialCode'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}

// ========================================================== END REVISI ======================================================

function mdc_cek_stok($mattoid,$idWarehouse){// $idMaterial,$idWarehouse
	// 1. cek idMaterial && idWarehouse ada ? 
	// 		jika ya 	=> update
	// 		jika tdk 	=> simpan
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_stock WHERE idMaterial = $mattoid && idWarehouse = $idWarehouse");
	while($row = db_fetch_array($db_data)) {
		$hasil['idStock'] 	= $row['idStock'];
		$hasil['qty'] 		= $row['qty'];
		$hasil['rsvQty'] 	= $row['rsvQty'];
		$hasil['price'] 	= $row['price'];
	}
	db_set_active();
	return $hasil;
}

function mdc_simpan_warehouse($warehouse,$idPlant){
	if($idPlant > 0){
		db_set_active('pep');
		$hasil = db_query("INSERT INTO mdc_warehouse (idPlant,description,isActive) VALUES (%d,'%s',%d)", $idPlant,$warehouse,1);
		db_set_active();
			
// 		drupal_set_message("Add Warehouse, Done !");
		$page = 'Insert mdc_category using Excel';
		$ket['Description'] = $cat;
		$hasil = mdc_logs($page,$ket);
	}
}

function mdc_waretoid($warehouse,$idPlant){ // idPlant	description
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse WHERE idPlant = $idPlant && description = '$warehouse'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['idWarehouse'];
	}
	db_set_active();
	return $hasil;
}

function mdc_fungsitoid($fungsi){ // idPlant	description
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_fungsi WHERE description = '$fungsi'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['idFungsi'];
	}
	db_set_active();
	return $hasil;
}

function mdc_planttoid($plant){
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_plant WHERE description = '$plant'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['idPlant'];
	}
	db_set_active();
	return $hasil;
}

function mdc_get_material_data($description) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_material WHERE description = '$description'");
	while($row = db_fetch_array($db_data)) {
		$hasil['materialCode'] 	= $row['materialCode'];
		$hasil['id'] 			= $row['id'];
		$hasil['category']		= $row['category'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}

function mdc_get_material_kimap($kimap) {
	// ambil data
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_material WHERE materialCode = '$kimap'");
	while($row = db_fetch_array($db_data)) {
		$hasil['description'] 	= $row['description'];
		$hasil['id'] 			= $row['id'];
		$hasil['category']		= $row['category'];
		$hasil['materialCode']		= $row['materialCode'];
	}
	db_set_active();
	// End ambil data
	return $hasil;
}

function mdc_cat_id($cat) {
	$hasil = 0;
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_category WHERE description = '$cat'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['idCategory'];
	}
	db_set_active();	
	return $hasil;
}
// END Upload Initial Stock ========================================================================
// GET GR ============================================================================
function mdc_get_gr(){
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_grdownload_back'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/receive'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	$form['gr'] = array(
			'#type' => 'fieldset',
			'#title' => t('Get Data GR'),
			'#weight' => 1,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$form['gr']['nogr'] = array(
			'#type' => 'textfield',
			'#title' => t('No GR'),
			'#size' => 30,
			'#maxlength' => 10,
			'#weight' => 2,
			'#required' => TRUE,
	);
	$form['gr']['thgr'] = array(
			'#type' => 'textfield',
			'#title' => t('Tahun'),
			'#size' => 30,
			'#maxlength' => 4,
			'#weight' => 3,
			'#required' => TRUE,
	);
	$form['gr']['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Import',
			'#weight' => 5,
	);
	return $form;
}
function mdc_get_gr_submit($form, &$form_state) {
	$nogr 	= $form_state['nogr'];
	$thgr	= $form_state['thgr'];
	
	drupal_goto('mdc/online/master/data/gr/'. $nogr .'/'. $thgr);
}
function mdc_master_data_gr(){
	$back	= "<a href='" .base_path(). "mdc/online/master/get/gr'>[back]</a><br><br>";
	if($_POST){
		db_set_active('pep');
		$no_gr_val = $_POST['gr'][0];
		$th_gr_val = $_POST['tahun_gr'][0];
		$keterangan = "Update stock dari GR No $no_gr_val Tahun $th_gr_val";
		$gr_count = db_num_rows(db_query("select * from mdc_stock where gr='$no_gr_val' and tahun_gr = $th_gr_val"));
		db_set_active();
		if($gr_count>0){
			drupal_set_message("No. GR $no_gr_val Tahun $th_gr_val sudah pernah dilakukan penarikan data sebelumnya",'error');
			return '<br/>'.$back;
		}
		
		$unique_kimap = array_unique($_POST['mat']);
		$kimap_condition = '';
		for($i=0;$i<count($unique_kimap); $i++){
			if($i==(count($unique_kimap)-1)){
				$kimap_condition.="'$unique_kimap[$i]'";
			}else{
				$kimap_condition.="'$unique_kimap[$i]',";
			}
		}
		
		// === blok KIMAP yg belum didaftarkan =========
		db_set_active('pep');
		$count_kimap_query = db_query("select distinct(materialCode) from mdc_material where materialCode in ($kimap_condition)");
		$count_kimap = db_num_rows($count_kimap_query);
		if(count($unique_kimap) != $count_kimap){
			$material_posted = $_POST['mat'];
			while($result = db_fetch_array($count_kimap_query)){
				$material_posted = array_diff($material_posted, array($result['materialCode']));
			}
			foreach($material_posted as $item){
				drupal_set_message("Kimap $item yang belum didaftarkan pada master data material, silahkan dicek kembali",'error');
			}
// 			return '<br/>'.$back;
		}
		db_set_active();
		// === END blok KIMAP yg belum didaftarkan =========
		
		for($i=0;$i<count($_POST['fungsi']);$i++){
			db_set_active('pep');
			$gr_val 	= $_POST['gr'][$i];
			$fungsi_val = $_POST['fungsi'][$i];
			$wh_val 	= $_POST['wh'][$i];			
					
			$hrg 		= $_POST['hrg'][$i]; $hrg = str_replace('.', '', $hrg); // menghapus '.' pada harga			
			// $cur 		= $_POST['cur'][$i];
			
			$mat_val 	= $_POST['mat'][$i];
			$fungsi_val = $_POST['fungsi'][$i];
			$po_val 	= $_POST['po'][$i];
			$tahun_gr_val = $_POST['tahun_gr'][$i];
			$tujuan_val = $_POST['tujuan'][$i];
			$query = db_query("SELECT * FROM `mdc_stock` stock
				LEFT JOIN mdc_material material ON stock.idMaterial = material.id
				WHERE stock.idFungsi =$fungsi_val
				AND stock.idWarehouse = $wh_val
				AND material.materialCode = '$mat_val'"); 
			if(db_num_rows($query) == 0){ //  jika data yg sesuai (idMaterial, idWarehouse, idFungsi), TIDAK ADA
				//insert
				$query1 = db_query("SELECT * FROM `mdc_material` WHERE materialCode ='$mat_val'"); 
				if(db_num_rows($query1)!=0){
					$result1 = db_fetch_array($query1);					
					db_query("INSERT INTO mdc_stock (idMaterial,idWarehouse,idFungsi,po,gr,tahun_gr,qty,tujuan_penggunaan,tanggal_gr,dedicated,price) VALUES (%d,%d,%d,%s,%s,%d,%d,'%s',%d,%d,%d)"
						, $result1['id'],$wh_val,$fungsi_val,$po_val,$gr_val,$tahun_gr_val,$_POST['qty'][$i],$tujuan_val,$_POST['tanggal_gr'][$i],1,$hrg);
					$query2 = db_query("SELECT * FROM `mdc_stock` stock
							LEFT JOIN mdc_material material ON stock.idMaterial = material.id
							WHERE stock.idFungsi =$fungsi_val
							AND stock.idWarehouse = $wh_val
							AND material.materialCode = '$mat_val' "); 
					$result2 = db_fetch_array($query2);
					mdc_hist_stok($result2['idStock'], 3, 0, $_POST['qty'][$i],$keterangan);
				}else{
// 					drupal_set_message("Material Kode $mat_val tidak ada dalam Master Data Material",'error');
				}
			}else{
				$result 	= db_fetch_array($query);
				$quantity 	= $_POST['qty'][$i];
				$idStock 	= $result['idStock'];
				
				// penentuan harga dan average
				// dsini : jika stok sblm ada, maka AVERAGE 
				// 1. cek (idMaterial, idWarehouse, idFungsi) => jika sama dan qty>0 maka lakukan average
				// 2. jika qty=0 gunakan langsung $hrg
				// $prc_now	= $_POST['hrg'][$i]; $prc_old = $result['price'];
				// $price 	= ($prc_now + $prc_old)/2;
				// END penentuan harga dan average
			
				db_query("UPDATE mdc_stock SET qty	= qty+$quantity,po=$po_val,gr=$gr_val,tujuan_penggunaan='$tujuan_val',dedicated=1
						WHERE idStock = $idStock");
				mdc_hist_stok($idStock, 3, $result['qty'], $quantity,$keterangan);
			}
			db_set_active(); 
		}
		$page = 'Get Data GR';
		$ket['No.GR'] = $no_gr_val ; $ket['Th.GR'] = $th_gr_val;
		$hasil = mdc_logs($page,$ket);
		return "$back<br/>Data GR berhasil diproses";
	}
	$nogr 	= arg(5);
	$thgr	= arg(6);
	$form='<form method="POST" action="master/data/gr">';
	$jdl	= "No GR : " . $nogr. "<br>";
	$jdl	.= "Tahun GR : " . $thgr. "<br><br>";
	$jdl	.= "<strong>Get All Data GR</strong>";

	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('No. Material'),),
			array('data'=>t('Description'),),
			array('data'=>t('Quantity'),),
			array('data'=>t('Harga'),),
			array('data'=>t('Po. Number'),),
			array('data'=>t('GR. Number'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Warehouse'),),
			array('data'=>t('Tujuan Penggunaan'),)
	);
	
	db_set_active('pep');
	if(db_num_rows(db_query("select * from mdc_stock where gr='$nogr' and tahun_gr = $thgr"))>0){
		drupal_set_message("No. GR $nogr Tahun $thgr sudah pernah dilakukan penarikan data sebelumnya",'error');
		return '<br/>'.$back;
	}
	db_set_active();
	
	// fungsi penarikan data SAP
	$data 	= get_data_sap($nogr,$thgr);
	$data1 	= $data['Data_GR'];
	// $prc	= $data1[0]->Price;	// harga satuan barang
	// $cur	= $data1[0]->Currency;	// Currency harga
	
	//check plant user with plant gr
	db_set_active('pep');
	$current_username = $GLOBALS['user']->name;
	$plant_user_result = db_fetch_array(db_query("SELECT * FROM mdc_plant plant left join mdc_user user on plant.idPlant=user.lokasi where username='$current_username'"));
	db_set_active();
	
	$data_roles			= mdc_user_roles();
	//$data_roles['admin'];
	if($plant_user_result['plantCode']!=$data1[0]->Plant){
		drupal_set_message("Anda tidak memiliki autorisasi untuk melakukan penarikan GR pada Plant ".$data1[0]->Plant,'error');
		return '<br/>'.$back;
	}
	
	$list_fungsi = mdc_fungsi_data_select();
	$form_fungsi="";
	foreach($list_fungsi as $key=>$value){
		$form_fungsi.="<option value=$key>$value</option>";
	}
	
	$list_wh = mdc_warehouse_data_select();
	$form_wh="";
	foreach($list_wh as $key=>$value){
		$form_wh.="<option value=$key>$value</option>";
	}
	
	foreach ($data1 as $key => $value){
		$nomat 	= $value->Material;
		$desc	= $value->Material_Desc;
		$qty	= $value->Quantity;
		$satuan	= $value->UoM;
		$hrg	= $value->Price;
		$cur	= $value->Currency;
		$po		= $value->RO_Number;
		$gr		= $value->GR_Number;
		$tanggal_gr = $value->GR_Date;
		$tanggal_gr = str_replace(".","-",$tanggal_gr);
		$tanggal_gr_save = strtotime($tanggal_gr);
		$fungsi	= "<select name=fungsi[]>$form_fungsi</select>";
		$fungsi.="<input type='hidden' name='mat[]' value='$nomat'/>";
		$fungsi.="<input type='hidden' name='qty[]' value='$qty'/>";
		$fungsi.="<input type='hidden' name='po[]' value='$po'/>";
		$fungsi.="<input type='hidden' name='hrg[]' value='$hrg'/>";
		$fungsi.="<input type='hidden' name='cur[]' value='$cur'/>";
		$fungsi.="<input type='hidden' name='gr[]' value='$gr'/>";
		$fungsi.="<input type='hidden' name='tahun_gr[]' value='$thgr'/>";
		$fungsi.="<input type='hidden' name='tanggal_gr[]' value='$tanggal_gr_save'/>";
		$warehouse	= "<select name='wh[]'>$form_wh</select>";
		$tujuan = "<textarea name='tujuan[]'></textarea>";
		$isi[] 	= array(++$xyz, $nomat, $desc, $qty .' '. $satuan, $cur .' '. $hrg, $po, $gr,$fungsi,$warehouse,$tujuan);
	}
	
// 	mdc_hist_stok($idStok, $act(1-3), $stok_awal, $nilai, $keterangan='');
	
	$output_data	= theme_table($judul, $isi);
	$end_form="<input type='submit' value='Submit'/></form>";
	return $back.$form.$jdl.$output_data.$end_form;
}
// END GET GR ========================================================================
// CEK GR ========================================================================
function mdc_get_gr_cek(){
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_grdownload_back'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/receive'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	$form['gr'] = array(
			'#type' => 'fieldset',
			'#title' => t('Cek Material GR'),
			'#weight' => 1,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$form['gr']['nogr'] = array(
			'#type' => 'textfield',
			'#title' => t('No GR'),
			'#size' => 30,
			'#maxlength' => 10,
			'#weight' => 2,
			'#required' => TRUE,
	);
	$form['gr']['thgr'] = array(
			'#type' => 'textfield',
			'#title' => t('Tahun'),
			'#size' => 30,
			'#maxlength' => 4,
			'#weight' => 3,
			'#required' => TRUE,
	);
	$form['gr']['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Cek Material',
			'#weight' => 5,
	);
	return $form;
}
function mdc_get_gr_cek_submit($form, &$form_state) {
	$nogr 	= $form_state['nogr'];
	$thgr	= $form_state['thgr'];
	
	drupal_goto('mdc/online/master/data/gr/cek/'. $nogr .'/'. $thgr);
}
function mdc_cek_material_gr(){
	$back	= "<a href='" .base_path(). "mdc/online/master/get/gr/cek'>[back]</a><br><br>";
	$nogr 	= arg(6);
	$thgr	= arg(7);
	$data 	= 'Tahun : <strong>'. $thgr .'</strong><br>';
	$data 	.= 'No. GR : <strong>'. $nogr .'</strong><br><br>';
		
	$hasil	= get_data_sap($nogr,$thgr);
	$hasil	= $hasil['Data_GR']; // $hasil->Material
	foreach($hasil as $key => $isi){
		$noMat = $isi->Material;
		$desc  = $isi->Material_Desc;
		$hasil = mdc_cek_data_gr($noMat);
		
		if($hasil){
			$stat = 'Ada';
		}else{
			$stat = '<strong>TIDAK ADA</strong>';
		}
		$data .= ++$xyz .' : '. $noMat .' : '. $desc .' : '. $stat .'<br>';
	}	
	
	return $back.$data;
}
function mdc_cek_data_gr($material){
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_material WHERE materialCode = '$material'");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['materialCode'];
	}
	db_set_active();
	return $hasil;
}
// END CEK GR ========================================================================