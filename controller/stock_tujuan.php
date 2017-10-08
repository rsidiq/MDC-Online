<?php 
// Stock Tujuan ============================================================================
function mdc_stock_tujuan() {
	drupal_add_css(drupal_get_path('module', 'mdc').'/css/nyroModal.css');
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.min.js');
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.nyroModal-1.6.2.pack.js');
	$lokasi = base_path();

	$data1 		= mdc_stock_tujuan_data();						// Ambil Data Semua Item Stok Barang
	$output1 	= theme_table($data1['judul'], $data1['isi']); 	// Tampilkan Semua Item Stok Barang
	$list1		= "<strong>Daftar Stok</strong>";
	
	$data2 		= mdc_stock_transfer_data();						// Ambil Data Semua Item Stok Barang
	$output2 	= theme_table($data2['judul'], $data2['isi']); 	// Tampilkan Semua Item Stok Barang
	$list2		= "<strong>Daftar Transfer</strong>";

	return $back.$list2.$output2.$list1.$output1;
}

function mdc_stock_transfer_data() {
	// cek kesamaan lokasi dan fungsi
	$name			= $GLOBALS['user']->name;
	$data_user		= mdc_user_data($name);
	$user_lokasi	= $data_user['lokasi'];
	$user_fungsi	= $data_user['fungsi'];
	// END cek kesamaan lokasi dan fungsi
	
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('No. Reservasi'),),
			array('data'=>t('dari Lokasi'),),
			array('data'=>t('Material'),),
			array('data'=>t('Qty'),),
			array('data'=>t('Approve'),),
			array('data'=>t('Reject'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT id,noreservasi,stat,idstok,qtyreservasi,idmaterial,idlokasal,idlokasi,idfungsi,ketsumber,kettujuan FROM mdc_transfer_tmp WHERE stat=0");
	while($row = db_fetch_array($db_data)) { 
		$idpl = mdc_warehouse_data($row['idlokasi']); $idplant = $idpl['idPlant'];
		$idpl2 = mdc_warehouse_data($row['idlokasal']); $idplant2 = $idpl2['idPlant'];
		$lok = mdc_plant_data($idplant2); $lokasi = $lok['description'];
		$mat = mdc_material_data($row['idmaterial']); $material = $mat['description'];	
		$appr= "<a href='" .base_path(). "mdc/online/stock/approve/?id=" .$row['id']. "' onclick='if(confirm(\"Are you sure, Approve " .$row['noreservasi']. " ?\") != true){ return false }'>Approve</a>";
		$rejc= "<a href='" .base_path(). "mdc/online/stock/reject/?id=" .$row['id']. "' onclick='if(confirm(\"Are you sure, Reject " .$row['noreservasi']. " ?\") != true){ return false }'>Reject</a>";
		if($idplant == $user_lokasi){	// $row['idlokasi'] == idWarehouse != $user_lokasi
			$isi[] 	= array(++$xyz, $row['noreservasi'], $lokasi, $material, $row['qtyreservasi'], $appr, $rejc);
		}
	}
	db_set_active();
	if(isEmptyArray($isi)){
		$isi[] = array('','Tak ada data Transfer');
	}

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}

function mdc_transfer_idtonores($id){
	db_set_active('pep');
	$hasil_update = db_query("SELECT * FROM mdc_transfer_tmp WHERE id = $id");
	while($row = db_fetch_array($db_data)) {
		$hasil	= $row['noreservasi'];
	}
	db_set_active();
	
	return $hasil;
}

function mdc_stock_transfer_approve(){
	$id = $_GET['id'];

	db_set_active('pep');
	$hasil_update = db_query("UPDATE mdc_transfer_tmp SET stat = 8 WHERE id = $id");
	db_set_active();
	
	$nores = mdc_transfer_idtonores($id);
	$hasil = mdc_transfer_tmp($nores,8,'','','','','','','');

	drupal_set_message('Approve Transfer Success');
	drupal_goto('mdc/online/stock');
}

function mdc_stock_transfer_reject(){
	$id = $_GET['id'];
	
	db_set_active('pep');
	$hasil_update = db_query("UPDATE mdc_transfer_tmp SET stat = 9 WHERE id = $id");
	db_set_active();
	
	drupal_set_message('Reject Transfer !', 'error');
	drupal_goto('mdc/online/stock');
}

function mdc_stock_tujuan_data() {
	drupal_add_js(drupal_get_path('module','mdc').'/js/script.js');

	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Lokasi'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('KIMAP'),),
			array('data'=>t('Material'),),
			array('data'=>t('Stock'),),
			array('data'=>t('Status'),),
			array('data'=>t('Tujuan Penggunaan'),),
			array('data'=>t('Tanggal Penggunaan'),),
			array('data'=>t('Dedicated / Berlebih'),),
			array('data'=>t('Edit Data'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT a.idStock, a.idMaterial, a.idWarehouse, a.idFungsi, a.qty, a.rsvQty, a.price, a.tujuan_penggunaan
							,a.tanggal_penggunaan, a.dedicated
							FROM pep.mdc_stock as a
							left join pep.mdc_material as b on b.id = a.idMaterial
							ORDER BY a.idWarehouse DESC");
	while($row = db_fetch_array($db_data)) {
		$idStock		= $row['idStock'];
		$idMaterial		= $row['idMaterial'];
		$materialData	= mdc_material_data($idMaterial);
		$namafile		= $materialData['fileName'];
		$idWarehouse	= $row['idWarehouse'];

		//===============
		// 1. ambil lokasi user
// 		$data_user_lokasi	= mdc_lokasi_user(); // <<= id lokasi user login
		$name				= $GLOBALS['user']->name;
		$data_user_fungsi	= mdc_user_data($name);
		$user_fungsi		= $data_user_fungsi['fungsi'];
		// 2. ambil lokasi stock
		$idW			= mdc_warehouse_data($idWarehouse);
		$idPlant		= $idW['idPlant']; // <<= id lokasi barang
		$lksbrg			= mdc_plant_data($idPlant);
		$lokasibrg		= $lksbrg['description'] .' - '. $idW['description'];
		$idFungsi		= $row['idFungsi'];
		// 3. tampilkan stok yg sama dgn lokasi user login
		// 			var_dump($data_user_lokasi .' - '. $idPlant);
// 		if($data_user_lokasi == $idPlant){
// 			$lokasi_sama	= 1;
// 		}

		// cek kesamaan fungsi
		if($user_fungsi == $idFungsi){
				$fungsi_sama	= 1;
		}
		//===============

		$stock			= $row['qty'] - $row['rsvQty']; // nil QTY (dikurang) total nil QTY reservasi
		$pesan			= "<a href='" .base_path(). "mdc/online/stok/tujuan/detil/?idStok=" .$idStock. "'>Edit</a>";

// 		$pesan_ses		= $_SESSION['transferbarang'][$idStock];
// 		if($pesan_ses){
// 			$stock 		= $stock - $pesan_ses;
// 			$pesan		= $pesan_ses;
// 		}

		$user_data		= mdc_user_roles();
		$rolesSprAdmin	= $user_data['sa'];
		$Admin			= $user_data['admin'];
		$sat	= mdc_material_data($idMaterial);
		$satuan	= $sat['satuan'];
		$cek_transfer_stok = mdc_transfer_tmp_cek($idStock);
		if($cek_transfer_stok == 0){
			$stat = 'Waiting approval';
		}
		
		if($row['tanggal_penggunaan']){
			$tanggal_penggunaan_show = date("d-m-Y",$row['tanggal_penggunaan']);
		}else{
			$tanggal_penggunaan_show = "-";
		}
		
		if($row['dedicated']){
			$dedicated = "Dedicated";
		}else{
			$dedicated = "Berlebih";
		}
		if($cek_transfer_stok != 1){
			if($fungsi_sama == 1){ // , mdc_category_name($categ)
				$isi[] 	= array(++$xyz, $lokasibrg, mdc_fungsi_name($row['idFungsi']),$sat['materialCode'], $sat['description'], $row['qty'] .' '. $satuan, $stat, $row['tujuan_penggunaan'],$tanggal_penggunaan_show,$dedicated,$pesan); // $stock
			}elseif(isset($rolesSprAdmin) || isset($Admin)){
				$isi[] 	= array(++$xyz, $lokasibrg, mdc_fungsi_name($row['idFungsi']),$sat['materialCode'], $sat['description'], $row['qty'] .' '. $satuan, $stat, $row['tujuan_penggunaan'],$tanggal_penggunaan_show,$dedicated,$pesan);
			}
		}
		$fungsi_sama	= 0;
		$stat = '';
	}
	db_set_active();

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_transfer_tmp_cek($idStock) { // mdc_stock idStock, qty
	$hasil = 9;
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_transfer_tmp WHERE idstok = $idStock");
	while($row = db_fetch_array($db_data)) {
		$hasil = $row['stat'];
	}
	db_set_active();
	return $hasil;
}
function mdc_stock_tujuan_detil() {
	$idStok 		= $_GET['idStok'];
	$stokData 		= mdc_stock_data($idStok);
	$tujuan			= $stokData['tujuan'];
	$stok			= $stokData['qty'];
	$idWarehouse	= $stokData['idWarehouse'];
	$idMaterial		= $stokData['idMaterial'];
	$whData			= mdc_warehouse_data($idWarehouse);
	$whName			= $whData['description'];
	$idPlant		= $whData['idPlant'];
	$plData			= mdc_plant_data($idPlant);
	$plName			= $plData['description'];

	$materialData	= mdc_material_data($idMaterial);
	$description	= $materialData['description'];
	$namafile		= $materialData['fileName'];
	$satuan			= $materialData['satuan'];
	$dedicated_options= array(1=>'Dedicated',2=>'Berlebih');
	if($stokData['tgl']){
		$periode		= date("Y-m-d",$stokData['tgl']);
	}else{
		$periode 		= date("Y-m-d",mktime(0,0,0,date('m'),date('d')+1,date('Y')));	
	}

	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_produk_markup'] = array(
			'#value' => t("Product : <strong>" .$description. "</strong><br>"),
			'#weight' => 2,
	);
	$form['mdc_stok_markup'] = array(
			'#value' => t("Stock : <strong>" .$stok. " " .$satuan. "</strong><br>"),
			'#weight' => 3,
	);
	$form['mdc_lokasi_markup'] = array(
			'#value' => t("Lokasi : <strong>" .$plName. " - " .$whName. "</strong><br><br>"),
			'#weight' => 4,
	);
	$form['tgl_penggunaan'] = array(
			'#type' => 'textfield',
			'#title' => t('Tanggal Penggunaan'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode,
			'#required' => TRUE,
			'#weight' => 5
	);
	$form['tujuan'] = array(
			'#type' => 'textfield',
			'#title' => t('Tujuan Penggunaan'),
			'#size' => 100,
			'#maxlength' => 150,
			'#default_value' => $tujuan,
			'#weight' => 6,
			'#required' => TRUE,
	);
	/*$form['dedicated'] = array(
			'#title' => t('Dedicated'),
			'#type' => 'select',
			'#options' => $dedicated_options,
			'#default_value' =>  variable_get('dedicated', $dedicated),
			'#weight' => 7,
			'#required' => TRUE,
	);*/
	$form['idStok'] = array(
			'#type' => 'hidden',
			'#default_value' => $idStok
	);
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Save',
			'#weight' => 98,
	);
	$form['mdc_stock_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/stock"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}
function mdc_stock_tujuan_detil_submit($form, &$form_state) {
	$idStock		= $form_state['idStok'];	
	$tujuan		= $form_state['tujuan'];	
	$tgl		= $form_state['tgl_penggunaan'];	// 2016-06-10
	$bln1 = substr($tgl,5,2); $tgl1 = substr($tgl,8,2); $thn1 = substr($tgl,0,4);
	$tgl_penggunaan= mktime(0,0,0, $bln1, $tgl1, $thn1);
			
	$next_3_month = strtotime("+3 month",$tgl_penggunaan); // 3*30*24*60*60 => old			
	if($next_3_month >= mktime()){
		$status = 1;
	}else{
		$status = 0;
	}
	//$dedicated	= $form_state['dedicated'];	
	
	db_set_active('pep'); // tujuan_penggunaan	tanggal_penggunaan	dedicated
	$hasil_update = db_query("UPDATE mdc_stock SET tujuan_penggunaan = '$tujuan', tanggal_penggunaan = $tgl_penggunaan, dedicated = $status WHERE idStock = $idStock");	
	db_set_active();
	
	$page = 'Update Penggunaan';
	$ket['Tujuan Penggunaan'] = $tujuan ; $ket['Tanggal Penggunaan'] = $tgl;
	$hasil = mdc_logs($page,$ket);
	//$hist = mdc_hist_stok($idStock, 0, 0, 0,'Update Tujuan Penggunaan & Tanggal Penggunaan <br>' . $tujuan .'<br>'. $tgl); // $act, $stok_awal, $nilai
	
	if($hasil_update){
		drupal_set_message('Submit Stock Tujuan Success');
	}else{
		drupal_set_message('SAVE Stock Tujuan Submit FAILED !', 'error');
	}	
	
	drupal_goto('mdc/online/stock');
}
// END Stock Tujuan ========================================================================