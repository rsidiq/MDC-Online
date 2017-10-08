<?php 
// Transfer Item ============================================================================
function mdc_transfer() {
	drupal_add_css(drupal_get_path('module', 'mdc').'/css/nyroModal.css');
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.min.js');
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.nyroModal-1.6.2.pack.js');
	$lokasi = base_path();

	$data 		= mdc_transfer_data();						// Ambil Data Semua Item Stok Barang
	$output 	= theme_table($data['judul'], $data['isi']); 	// Tampilkan Semua Item Stok Barang
	$data		= $_SESSION['transferbarang'];
	if($data){
		$submit_pesan = '<a href="' .base_path(). 'mdc/online/transfer/sabmit"><input type="button" value="Transfer" /></a><br><br>';
	}

	$data_ses 	= mdc_data_transfer_session();
	$output_ses	= theme_table($data_ses['judul'], $data_ses['isi']);

	$back 		= "<br><a href='" .base_path(). "mdc/online/issue'>[back]</a><br><br>";
	$pesan_txt	= "<strong>Daftar Pilihan (Transfer)</strong>";
	// 	$autoSele	= "<br><select name='category' id='category' onchange='getval(this);'>";
	// 	$autoSele	.= mdc_kategori_pil(arg(4));
	// 	$autoSele	.= "</select><br>";
	// 	$list_txt	= "<strong>Daftar Semua Barang</strong>";

	return $back.$pesan_txt.$output_ses.$submit_pesan.$output; // $xx.$list_txt.$autoSele
}

function mdc_transfer_data() {
	drupal_add_js(drupal_get_path('module','mdc').'/js/script.js');

	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Lokasi'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Material'),),
			array('data'=>t('Stock'),),
			array('data'=>t('Satuan'),),
			array('data'=>t('Pilih'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT a.idStock, a.idMaterial, a.idWarehouse, a.idFungsi, a.qty, a.rsvQty, a.price
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
		$pesan			= "<a href='" .base_path(). "mdc/online/transfer/detil/?idStok=" .$idStock. "' class='nyroModal'>Transfer</a>";

		$pesan_ses		= $_SESSION['transferbarang'][$idStock];
		if($pesan_ses){
			$stock 		= $stock - $pesan_ses;
			$pesan		= $pesan_ses;
		}

		$user_data		= mdc_user_roles();
		$rolesSprAdmin	= $user_data['sa'];
		$Admin			= $user_data['admin'];
		$sat	= mdc_material_data($idMaterial);
		$satuan	= $sat['satuan'];
		if($fungsi_sama == 1){ // , mdc_category_name($categ)
			$isi[] 	= array(++$xyz, $lokasibrg, mdc_fungsi_name($row['idFungsi']), mdc_material_data_select($idMaterial), $row['qty'], $satuan, $pesan); // $stock
		}elseif(isset($rolesSprAdmin) || isset($Admin)){
			$isi[] 	= array(++$xyz, $lokasibrg, mdc_fungsi_name($row['idFungsi']), mdc_material_data_select($idMaterial), $row['qty'], $satuan, $pesan);
		}
		$fungsi_sama	= 0;
	}
	db_set_active();

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_bataltransfer() {
	$idStok 	= $_GET['idStok'];
	unset($_SESSION['transferbarang'][$idStok]);
	drupal_goto('mdc/online/transfer');
}
function mdc_plant_warehouse(){
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_warehouse WHERE isActive = 1");
	while($row = db_fetch_array($db_data)) {
		$plant	= mdc_plant_data($row['idPlant']);
		$plantnm= $plant['description'];
		$hasil[$row['idWarehouse']]	= $plantnm .' - '. $row['description'];
	}
	db_set_active();
	return $hasil;
}
function mdc_transfer_detil() {
	$idStok 		= $_GET['idStok'];
	$stokData 		= mdc_stock_data($idStok);
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
	$site_options 	= mdc_plant_warehouse();
	$fungsi_options	= mdc_fungsi_all();
	
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_back_markup'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/transfer'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	$form['mdc_produk_markup'] = array(
			'#value' => t("Product : <strong>" .$description. "</strong><br>"),
			'#weight' => 2,
	);
	$form['mdc_stok_markup'] = array(
			'#value' => t("Stock : <strong>" .$stok. " " .$satuan. "</strong><br>"),
			'#weight' => 3,
	);
	$form['mdc_lokasi_markup'] = array(
			'#value' => t("Lokasi Awal : <strong>" .$plName. " - " .$whName. "</strong><br><br>"),
			'#weight' => 3,
	);
	$form['pesan'] = array(
			'#type' => 'textfield',
			'#title' => t('Jumlah'),
			'#size' => 10,
			'#maxlength' => 3,
			'#weight' => 4,
			'#required' => TRUE,
	);
// 	$form['lokasi'] = array(
// 			'#title' => t('Lokasi'),
// 			'#type' => 'textfield',
// 			'#weight' => 5,
// 	);	  
	$form['lokasi'] = array(
			'#title' => t('Lokasi Tujuan'),
			'#type' => 'select',
			'#options' => $site_options,
			'#default_value' =>  variable_get('lokasi', $lokasi),
			'#weight' => 5,
			'#required' => TRUE,
	);
	$form['fungsi'] = array(
			'#title' => t('Fungsi Tujuan'),
			'#type' => 'select',
			'#options' => $fungsi_options,
			'#default_value' =>  variable_get('lokasi', $fungsi),
			'#weight' => 5,
			'#required' => TRUE,
	);
	$form['idStok'] = array(
			'#type' => 'hidden',
			'#default_value' => $idStok
	);
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Transfer',
			'#weight' => 98,
	);
	$form['mdc_stock_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/transfer"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}

function mdc_transfer_detil_flush(){
	die(drupal_get_form('mdc_transfer_detil'));
}
function mdc_transfer_detil_submit($form, &$form_state) {
	$pesan 		= $form_state['pesan'];
	$lokasi		= $form_state['lokasi'];
	$fungsi		= $form_state['fungsi'];
	$idStok 	= $form_state['idStok'];
	$stokData 	= mdc_stock_data($idStok);
	$stok		= $stokData['qty'];
	$idMaterial	= $stokData['idMaterial'];
	$idWarehouse= $stokData['idWarehouse'];	
	$sisa		= $stok - $pesan;	
	if($pesan>$stok){
		drupal_set_message('Stock Barang Tidak Mencukupi !', 'error');
	}else{
		if($pesan > 0){
			$_SESSION['transferbarang'][$idStok] 	= $pesan;
			$_SESSION['lokasi'][$idStok] 			= $lokasi;
			$_SESSION['fungsi'][$idStok] 			= $fungsi;
		}
	}		
	drupal_goto('mdc/online/transfer');
}
function mdc_data_transfer_session(){
	$data	= $_SESSION['transferbarang'];
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Material'),),
			array('data'=>t('Pilih'),),
			array('data'=>t('Batal'),)
	);
	if(!$data){
		$isi[] 	= array("Belum Ada Item yang Dipilih!");
	}else{
		foreach ($data as $key => $value){	// $key => $idStock ; $value => qty
			$idStock 		= mdc_stock_data($key);
			$idMaterial		= $idStock['idMaterial'];
			$idWarehouse	= $idStock['idWarehouse'];
			$stock			= $idStock['qty'];
			$sat			= mdc_material_data($idMaterial);
			$satuan			= $sat['satuan'];

			$pesan_ses		= $_SESSION['transferbarang'][$key];
			if($pesan_ses){
				$stock 		= $stock - $pesan_ses;
				$pesan		= $pesan_ses .' '. $satuan;
			}
			$batal			= "<a href='" .base_path(). "mdc/online/transfer/batal/?idStok=" .$key. "'>batal</a>"; // benerin link nya
			if($value){
				$isi[] 	= array(++$xyz, mdc_material_data_select($idMaterial), $pesan, $batal);
			}
		}
	}
	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_noTransfer() {
	$tglSkrng	= date('ym'); // ambil thn & bln sekarang
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo LIKE '%MDCT-$tglSkrng%' ORDER BY id ASC");
	while($row = db_fetch_array($db_data)) {
		$hasil[]	= $row['id'];
		$hit		= ++$xxx;
	}
	db_set_active();
	if($hit==NULL){
		$no = '000001';
	}else{
		$no = $hit+1;
		$hitung	= 6 - strlen($no);
		for($yy=1;$yy<=$hitung;$yy++){
			$str .= '0';
		}
		$no = $str.$no;
	}
	return $no;
}
function mdc_transfer_sabmit() { // saat pesanan di submit oleh user
	// $reservasiNo = uid_stokNo_qty_date => xxxxx_xxx_xxx_xxxxxx : uid login ; date => dmy_His
	// $reservasiNo = mdcR-date-rnd => mdcR-xxxxxx-xxxxxx : date => ymd ; rnd => 1-999999
	// 1. simpan reservation_detil : qty, item, gudang(plant)
	// 2. simpan reservation : reservasiNo, nopekMgrApproval, statMgrApproval=0, statScmApproval=0,idRequest,requestBy(nama),createTime(time now)
	// 3. if timeIssuerApproval => isClose=1
	$data			= $_SESSION['transferbarang'];	
// 	$requestNo		= $_SESSION['notransfer'];
	$user_data		= mdc_user_roles();
	$uid			= $user_data['uid'];
	$inputData		= $user_data['nama'];		
	
	$requestBy		= $inputData; // get_username_org($requestNo);
	$uidRequest		= mdc_usernametouid($requestBy);
	$noRev 			= mdc_noTransfer(); 				// <<= No terakhir dgn tahun & bln yg sama - Reservasi
	$reservasiNo 	= 'MDCT-'. date('ymd') .'-'. $noRev;
	// $kirim_pesan	= mdc_pesanemail(1,$reservasiNo,$requestBy); // <= reservasi pertama kali 				*** MATIKAN KIRIM EMAIL
	$createTime		= mktime(); // date('Y-m-d H:i:s');
	foreach ($data as $key => $value){ 					// $key => idStock : $value => qty pesan			
		$stokData 		= mdc_stock_data($key);
// 		$notes_isi		= $_SESSION['notes'][$key]; lokasi
		$lokasi			= $_SESSION['lokasi'][$key];
		$idMaterial		= $stokData['idMaterial'];
// 		$idFungsi		= $stokData['idFungsi'];
		$idFungsi		= $_SESSION['fungsi'][$key];
// 		$rsvQty			= $stokData['rsvQty'] + $value;	
		$idWarehouse	= $stokData['idWarehouse'];	
		$materialData	= mdc_material_data($idMaterial);
		$materialCode	= $materialData['materialCode'];
		$wareHouseData	= mdc_warehouse_data($idWarehouse);
		$idPlant		= $wareHouseData['idPlant'];
		$warehouse_tujuan = mdc_warehouse_data($lokasi);
		$idPlant_tujuan 	= $warehouse_tujuan['idPlant'];
		$fungsiData = mdc_fungsi_data($idFungsi);
		$fungsi_asal_data = mdc_fungsi_data($stokData['idFungsi']);
		$plantData = mdc_plant_data($idPlant);
		$plant_tujuan_data = mdc_plant_data($idPlant_tujuan);
		$warehouse_name = $warehouse_tujuan['description'];
		$fungsi_name = $fungsiData['description'];
		$plant_name = $plantData['description'];
		$keterangan = "Transfer ke $warehouse_name Lokasi ".$plant_tujuan_data['description']." Fungsi $fungsi_name";
		$keterangan_asal = "Transfer dari ".$wareHouseData['description']." Lokasi ".$plant_name." Fungsi ".$fungsi_asal_data['description'];
		
		// data TUJUAN
// 		$whDataTujuan	= mdc_warehouse_data($lokasi);
// 		$palantTujuan	= $whDataTujuan['idPlant'];
		
		if($value > 0){
			// add => mdc_reservation_detil
			db_set_active('pep');
			$hasil = db_query("INSERT INTO mdc_reservation_detil (reservasiNo,idStock,idPlant,materialCode,requestQty,acceptQty,notes,isActive) VALUES ('%s',%d,%d,'%s',%d,%d,'%s',%d)", $reservasiNo,$key,$idPlant,$materialCode,$value,$value,$lokasi,1);
			if(!$hasil){
				drupal_set_message('SAVE Reservation Submit FAILED !', 'error');
			}	
			db_set_active();
			
			// ================================================================================================= Update Stok => stlh approve
			// 'noreservasi',stat,idstok,qtyreservasi,idmaterial,idlokasi,idfungsi,'ketsumber','kettujuan'
			$mdc_transfer_tmp = mdc_transfer_tmp($reservasiNo,0,$key,$value,$idMaterial,$lokasi,$idFungsi,$keterangan_asal,$keterangan);
			// ================================================================================================= END Update Stok => stlh approve
		}else{
			unset($_SESSION['transferbarang']);
			unset($_SESSION['lokasi']);
// 			unset($_SESSION['transfer']);
			drupal_set_message('SAVE Reservation Submit FAILED !', 'error');
			drupal_goto('mdc/online/transfer');			
		}
	}
	// add => mdc_reservation =====>> cukup sekali record, letakkan diluar
	db_set_active('pep');
	$hasil = db_query("INSERT INTO mdc_reservation (reservasiNo,statusApproval,statMgrApproval,statScmApproval,idRequest,requestBy,createTime,input)
				VALUES ('%s',%d,%d,%d,%d,'%s','%s','%s')", $reservasiNo,0,0,0,$uidRequest,$requestBy,$createTime,$inputData);
	if($hasil){
		drupal_set_message('Submit Reservation Success');
		unset($_SESSION['transferbarang']);
		unset($_SESSION['lokasi']);
// 		unset($_SESSION['transfer']);
	}else{
		drupal_set_message('SAVE Reservation Submit FAILED !', 'error');
	}
	db_set_active();
	// END add => mdc_reservation
	drupal_goto('mdc/online/issue');
}
function mdc_simpan_transfer_tujuan($idMaterial, $idWarehouse, $idFungsi, $qty,$keterangan = ''){
	// 1. cari idStock, idMaterial, idWarehouse, idFungsi yg sama
	$data = 0;
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_stock WHERE idMaterial = $idMaterial && idWarehouse = $idWarehouse && idFungsi = $idFungsi");
	while($row = db_fetch_array($db_data)) {
		$data	= $row['qty'];
		$idStock = $row['idStock'];
	}
	db_set_active();
	// 2. tambahkan jika ada data tsb;
	if($data > 0){
		$hasil = $data + $qty;
		db_set_active('pep');
		$hasil_update = db_query("UPDATE mdc_stock SET qty = $hasil WHERE idStock = ".$idStock);
		db_set_active();
		mdc_hist_stok(idStock, 3, $data, $qty,$keterangan);
	}else{
		
		db_set_active('pep');
		$hasil = db_query("INSERT INTO mdc_stock (idMaterial, idWarehouse, idFungsi, qty)
				VALUES (%d,%d,%d,%d)", $idMaterial, $idWarehouse, $idFungsi, $qty);
		
		$db_data = db_query("SELECT * FROM mdc_stock WHERE idMaterial = $idMaterial && idWarehouse = $idWarehouse && idFungsi = $idFungsi");
		$stock_result = db_fetch_array($db_data);
		mdc_hist_stok($stock_result['idStock'], 3, 0, $qty,$keterangan);
		db_set_active();
	}
}
function mdc_get_data_transfer($noreservasi){
	db_set_active('pep');
	$hasil_update = db_query("SELECT * FROM mdc_transfer_tmp WHERE noreservasi = '$noreservasi'");
	while($row = db_fetch_array($db_data)) {
		$hasil['qtyreservasi']	= $row['qtyreservasi'];
		$hasil['idstok']		= $row['idstok'];		
		$hasil['idmaterial']	= $row['idmaterial'];
		$hasil['idlokasi']		= $row['idlokasi'];	
		$hasil['idfungsi']		= $row['idfungsi'];
		$hasil['ketsumber']		= $row['ketsumber'];
		$hasil['kettujuan']		= $row['kettujuan'];
	}
	db_set_active();
	
	return $hasil;
}
function mdc_transfer_tmp($noreservasi='',$stat='',$idstok='',$qtyreservasi='',$idmaterial='',$idlokasi='',$idfungsi='',$ketsumber='',$kettujuan=''){
	// ================================================================================================= Update Stok => stlh approve
	// $idlokasi = $idWarehouse => tujuan
	$stok_data 	= mdc_stock_data($idstok);
	$lok_asal 	= $stok_data['idWarehouse']; // $lok_asal = idWarehouse(stok)
	
	// stat = 8 => Approve; stat = 9 => Reject; stat = 0 => New Transfer
	
	if($stat == 0){ // 0 = blm Approve
		db_set_active('pep');
		$hasil = db_query("INSERT INTO mdc_transfer_tmp (noreservasi, stat, idstok, qtyreservasi, idmaterial, idlokasal, idlokasi, idfungsi, ketsumber, kettujuan)
				VALUES ('%s',%d,%d,%d,%d,%d,%d,%d,'%s','%s')", $noreservasi,$stat,$idstok,$qtyreservasi,$idmaterial,$lok_asal,$idlokasi,$idfungsi,$ketsumber,$kettujuan);
		db_set_active();
		
	}elseif ($stat == 8){ // 8 = sdh Approve
		$get_data_transfer = mdc_get_data_transfer($noreservasi);
		$qtyreservasi = $get_data_transfer['qtyreservasi'];
		$idstok 	= $get_data_transfer['idstok'];
		$idmaterial = $get_data_transfer['idmaterial'];
		$idlokasi 	= $get_data_transfer['idlokasi'];
		$idfungsi 	= $get_data_transfer['idfungsi'];
		$ketsumber 	= $get_data_transfer['ketsumber'];
		$kettujuan 	= $get_data_transfer['kettujuan'];
		
		// update QTY Stok
		$stok_data 	= mdc_stock_data($idstok);
		$jml_qty 	= $stok_data['qty'] - $qtyreservasi;

		// update STOCK LOKAL
		db_set_active('pep');
		$hasil_update = db_query("UPDATE mdc_stock SET qty = $jml_qty WHERE idStock = $idstok");
		db_set_active();
			
		// update STOCK TARGET ; idMaterial, idWarehouse, idFungsi, qty
		$simpan_tujuan = mdc_simpan_transfer_tujuan($idmaterial, $idlokasi, $idfungsi, $qtyreservasi,$ketsumber);
			
		$hist = mdc_hist_stok($idstok, 2, $stok_data['qty'], $qtyreservasi,$kettujuan);
		// END add => mdc_reservation_detil		
	}
	// ================================================================================================= END Update Stok => stlh approve
}