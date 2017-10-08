<?php 
// CATEGORY ========================================================================
function mdc_category_view_data() {
	$stat = array(1=>'Active', 2=>'Disabled');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Category'),),
			array('data'=>t('Status'),),
			array('data'=>t('Edit'),),
			array('data'=>t('Delete'),)
	);	
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_category ORDER BY description ASC");	
	while($row = db_fetch_array($db_data)) {
		$cek_mat = mdc_mat_data($row['idCategory']);
		if($cek_mat == TRUE){
			$delete	= "<a href='#' title='masih ada stok' onclick='alert(\"Tidak dapat dihapus, Kategori masih digunakan!\")'>hapus</a>";
		}else{
			$delete	= "<a href='" .base_path(). "mdc/online/category/delete/?id=" .$row['idCategory']. "' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>hapus</a>";
		}
		$edit 	= "<a href='" .base_path(). "mdc/online/category/set/?id=" .$row['idCategory']. "'>edit</a>";		
		$isi[] 	= array(++$xyz, $row['description'], $stat[$row['isActive']], $edit, $delete);
	}
	db_set_active();
	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_mat_data($idCat) {
	// cek data Material
	$hasil		= FALSE;
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_material WHERE category = $idCat");
	while($row = db_fetch_array($db_data)) {
		$hasil		= TRUE;
	}
	db_set_active();
	// End cek data Material
	return $hasil;
}
function mdc_category_view() {
	$data = mdc_category_view_data();
	$output = theme_table($data['judul'], $data['isi']);
  	$hasil = "<a href='" .base_path(). "mdc/online/category/set'>Add Category</a> | <a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $hasil.$output;
}
function mdc_category_name($idCategory) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_category WHERE idCategory = $idCategory");
	while($row = db_fetch_array($db_data)) {
		$hasil 	= $row['description'];
	}
	db_set_active();
	return $hasil;
}
function mdc_category_set() {
	$status_pil = array(1=>'Active', 2=>'Disabled');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	if(isset($_GET['id'])){
		$id 		= $_GET['id'];		
		// ambil data
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_category WHERE idCategory = $id ORDER BY description ASC");
		while($row = db_fetch_array($db_data)) {
			$category 	= $row['description'];
			$status 	= $row['isActive'];			
		}
		db_set_active();
		// End ambil data
		
		$form['edit_id'] = array(
				'#type' => 'hidden',
				'#default_value' => $id
		);		
	}
	$form['category'] = array(
		'#type' => 'textfield',
		'#title' => t('Kategory'),
		'#size' => 20,	
		'#maxlength' => 240, 
		'#weight' => 1,
		'#default_value' =>  $category,
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
	$form['mdc_category_markup'] = array(
		'#value' => t('<a href="' .base_path(). 'mdc/online/category/view"><input type="button" value="Cancel" /></a>'),
		'#weight' => 99,
	);
	return $form;
}
function mdc_category_set_submit($form, &$form_state) {
	$edit_id 	= $form_state['edit_id'];
	$category 	= $form_state['category'];
	$status 	= $form_state['status'];	
	db_set_active('pep');
	if($edit_id){
		$hasil_update = db_query("UPDATE mdc_category SET
				description 		= '$category',
				isActive			= $status
			WHERE idCategory = $edit_id");
		if($hasil_update){
			drupal_set_message('Category UPDATE Success ...');
		}else{
			drupal_set_message('SAVE EDIT Category FAILED !', 'error');
		}
		$ket['idCategory'] = $edit_id;
		$page = 'Edit Category';
	}else{
		$hasil = db_query("INSERT INTO mdc_category (description,isActive) VALUES ('%s',%d)", $category,$status);
		if($hasil){
			drupal_set_message('Category SAVE Success ...');
		}else{
			drupal_set_message('SAVE Category FAILED !', 'error');
		}		
		$page = 'Insert Category';
	}
	db_set_active();
	$ket['Description'] = $category; $ket['Status'] = $status;
	$hasil = mdc_logs($page,$ket);
	drupal_goto('mdc/online/category/view');
}
function mdc_category_delete() {
	$id 		= $_GET['id'];
	db_set_active('pep');
	$db_query 	= "DELETE FROM mdc_category WHERE idCategory = $id";
	$result 	= mysql_query($db_query);
	if($result){
		drupal_set_message('Data Category DELETE Success ...','error');
	}
	db_set_active();
	drupal_goto('mdc/online/category/view');
}
// END CATEGORY ========================================================================