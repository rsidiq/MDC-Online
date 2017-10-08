<?php 
// GR ============================================================================
function mdc_up_user(){
	$xxx		= $GLOBALS['user']->name;
	if($xxx != 'pep-webdev01.mdgti'){
		drupal_goto('mdc/online/master');
	}
	$txtJudul 	= 'Upload User Data';
	drupal_add_js("$(document).ready(function() { $('div#app-title h2').text('" .$txtJudul."'); })", "inline");
	
	$site_options 	= mdc_plant_list();
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['upUser'] = array(
			'#type' => 'fieldset',
			'#title' => t('Upload User'),
			'#weight' => 1,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$form['upUser']['user_file'] = array(
			'#type' => 'file',
			'#title' => 'Select your file',
			'#size' => 2,
	);	  
	$form['upUser']['lokasi'] = array(
			'#title' => t('Lokasi'),
			'#type' => 'select',
			'#options' => $site_options,
			'#default_value' =>  variable_get('lokasi', 1),
			'#weight' => 3,
			'#required' => TRUE,
	);
	$form['upUser']['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Import',
			'#weight' => 99,
	);
	return $form;
}
function mdc_up_user_submit($form, &$form_state) {
	$check_file = file_check_upload('user_file');
	$lokasi		= $form_state['lokasi'];
	$mime1 = 'application/vnd.ms-excel';
	$mime2 = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	$isimime = $check_file->filemime;
	if($isimime == $mime2) {
		$isi_sblm 		= file_directory_path().DIRECTORY_SEPARATOR."mdc/gr" .DIRECTORY_SEPARATOR;
		if ($isimime == $mime1) {
			$nama_file 	= 'dataUser.xls';			
		}else{
			$nama_file 	= 'dataUser.xlsx';
		}
		if($isi_sblm . $nama_file){
			unlink($isi_sblm . $nama_file);
		}
		
		//Save File
		$path 		= file_directory_path().DIRECTORY_SEPARATOR."mdc/gr" .DIRECTORY_SEPARATOR. $nama_file;
		$file 		= file_save_upload('user_file',$path);
		// END Simpan File
		if ($file === 0) {
			drupal_set_message("Gagal simpan file !",'error');
		}else{
			$hasil = mdc_up_user_from_excel_simpan($nama_file,$lokasi); // <= simpan ke database
		}
		drupal_goto('mdc/online/master/upload/user');
	}
	drupal_set_message("Gagal simpan, Only Type : *.xlsx ",'error');
	return FALSE;
}
function mdc_up_user_from_excel_simpan($nama_file,$lok) {
	$lokasi 		= file_directory_path()."/mdc/gr/" .$nama_file;
	$objReader 		= PHPExcel_IOFactory::createReader('Excel2007');
	$objPHPExcel 	= $objReader->load($lokasi);
	$objWorksheet 	= $objPHPExcel->setActiveSheetIndex(0);
	$totalRow 		= $objWorksheet->getHighestRow();
	$sheetCount 	= $objPHPExcel->getSheetCount();
	$berhasil 		= 0;
	// 	$sheetNames = $objPHPExcel->getSheetNames();

	// Cek AA1
	$cek_template	= trim($objPHPExcel->setActiveSheetIndex(0)->getCell('D1')->getValue());
	// Cek ke-Asli-an Template : AA1 => 'ASLI'
	if($cek_template != 'ASLI'){
		drupal_set_message("Format Data TIDAK SAMA !<br>Gunakan template yg disediakan.",'error');
		drupal_goto('mdc/online/master/upload/user');
	}
	
	for($x=2;$x<=$totalRow;$x++){
		$username		= $objPHPExcel->setActiveSheetIndex(0)->getCell('A'.$x)->getValue();
		$addAtasan		= $objPHPExcel->setActiveSheetIndex(0)->getCell('B'.$x)->getValue();
		// ================================
		mdc_addUserUpload($username,$lok,$addAtasan);
		mdc_addUserUpload($addAtasan,$lok);
		$berhasil = 1;
	}
	
	if($berhasil == 1){
		drupal_set_message("File Save to Database, Complete!");
	}else{
		drupal_set_message("File Save to Database, Failed!",'error');
	}
	drupal_goto('mdc/online/master/upload/user');
}
function mdc_addUserUpload($username,$lokasi,$atasan = NULL){ // 170:akses1(user), 172:akses3(atasan)
	$fullname		= cek_fullname($username); 	// <= cek user login
	$cek_user_ada	= mdc_user_data($username); // <= cek user mdc
	
	// Add User dan atasan
	if($fullname && !isset($cek_user_ada)){
		db_set_active('pep');
		if(!$atasan){
			$rol			= 179;
			$hasil = db_query("INSERT INTO mdc_user (username,fullname,lokasi,akses3) VALUES ('%s','%s',%d,%d)", $username,$fullname,$lokasi,$rol);
		}else{
			$rol			= 183;
			$hasil = db_query("INSERT INTO mdc_user (username,fullname,lokasi,akses1,atasan1) VALUES ('%s','%s',%d,%d,'%s')", $username,$fullname,$lokasi,$rol,$atasan);
		}		
		db_set_active();
			
		$adduserrole = mdc_role_save($username,$rol);
	}
	// END Add User dan atasan
}