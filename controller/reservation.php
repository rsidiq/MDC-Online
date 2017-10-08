<?php 
// VIEW RESERVATION ============================================================================
function mdc_reservation() {
	return '';
}
function mdc_reservation_closed() {	
// 	$tampilkan 	= mdc_stock_kritis();
	$periode_awal = date("Y-m-d",mktime(0,0,0,date('m'),date('d')-30,date('Y')));
	$periode_akhir = date("Y-m-d",mktime(0,0,0,date('m'),date('d')+1,date('Y'))); // ,4=>'Good Issued' <<= dihapus
	$status_pil = array(0=>'All',1=>'Waiting for Atasan Approval',3=>'Approved by Atasan, ready goods Taken',5=>'Rejected by Atasan',6=>'Rejected by SCM',7=>'Item Received');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');	
	$form['tampilkan'] = array(
			'#type' => 'fieldset',
			'#title' => t('Reservation View'),
			'#weight' => 0,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$form['tampilkan']['periode_awal'] = array(
	  	'#type' => 'textfield',
	  	'#title' => t('Periode'),
	  	'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
	  	'#jscalendar_ifFormat' => '%Y-%m-%d',
	  	'#jscalendar_showsTime' => 'false',
	  	'#default_value' => $periode_awal,
		'#required' => TRUE,
	  	'#weight' => 1
	);	
	$form['tampilkan']['periode_akhir'] = array(
	  	'#type' => 'textfield',
	  	'#title' => t('s.d'),
	  	'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
	  	'#jscalendar_ifFormat' => '%Y-%m-%d',
	  	'#jscalendar_showsTime' => 'false',
	  	'#default_value' => $periode_akhir,
		'#required' => TRUE,
	  	'#weight' => 2
	); 
	$form['tampilkan']['status'] = array(
		'#title' => t('Status'),
		'#type' => 'select',	
		'#options' => $status_pil,
		'#default_value' =>  variable_get('status', $status),
		'#weight' => 3,
		'#required' => TRUE, 	
	);	
	$form['tampilkan']['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Show',
			'#weight' => 99,
	);
	$form['tampilkan2'] = array(
			'#type' => 'fieldset',
			'#title' => t('New Reservation'),
			'#weight' => 11,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$nama 		= $GLOBALS['user']->name;
	$nopek 		= get_nopek_org($nama);
	if($nopek){
		$hasil 		= cek_nopek_mdc($nopek);
		$data_nama 	= '<br><br>' .$hasil['nama'];
	}else{
		$data_nama 	= '<br><br>' .$nama;
	}

	$form['tampilkan2']['mdc_reservation_markup1'] = array(
		'#value' => t('<br><strong>"Rekomendasi Browser Mozilla Firefox dan Google Chrome"</strong><br><br>')
	);
	
	$form['tampilkan2']['mdc_reservation_markup'] = array(
		'#value' => t('<a href="' .base_path(). 'mdc/online/reservation/new"><input type="button" value="New Reservation" onclick="window.location.href=\'' .base_path(). 'mdc/online/reservation/new\';" /></a>')
	);
	
	if($nopek){
		$data_nama 					= '<br><br>' .$nama. ' (' .$nopek. ')';
		$_SESSION['reservation']	= $nopek;
	}else{
		$data_nama 					= '<br><br>' .$nama;
	}
	
	$form['tampilkan2']['mdc_reservation_markup_alert'] = array(
		'#value' => t($data_nama)
	);
	return $form;
}
function mdc_reservation_submit($form, &$form_state) {
	$periode_awal 	= $form_state['periode_awal'];
	$periode_akhir 	= $form_state['periode_akhir'];
	$status		 	= $form_state['status'];
	drupal_goto('mdc/online/reservation/view', 'periode_awal=' .$periode_awal. '&periode_akhir=' .$periode_akhir. '&status=' .$status);
}
function mdc_konversi_tgl($data,$key=NULL){
	if($key){
		$hasil	= date("d-m-Y H:i:s", $data);				// 14-06-2015 00:00:00
	}else{
		$thn 	= substr($data,0,4);
		$bln 	= substr($data,5,2);
		$tgl 	= substr($data,8,2);
		$hasil	= mktime($hrs,$mnt,$scd,$bln,$tgl,$thn);	// 1434232800
	}
	return $hasil;
}
function mdc_reservation_data_select($reservasiNo) { // <<======================================= GAK BENER nih, krn reservasiNo TDK Unik !
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo'");
	while($row = db_fetch_array($db_data)) {
		$hasil['reservasiNo'] 	= $row['reservasiNo'];
		$hasil['idStock']		= $row['idStock'];
		$hasil['idPlant']		= $row['idPlant'];
		$hasil['materialCode']	= $row['materialCode'];
		$hasil['requestQty']	= $row['requestQty'];
		$hasil['acceptQty']		= $row['acceptQty'];
	}//$nameClose $timeClose $noteClose ; $idStock $requestQty $acceptQty
	db_set_active();
	return $hasil;
}
function mdc_is_atasan(){
	$user_data	= mdc_user_roles();
	$nama		= $user_data['nama'];	// <<= tambahan yg login ditampilkan
	$hasil		= array();
	$data_user_lokasi	= mdc_lokasi_user(); // sesuaikan lokasi login dan bawahan yg diambil
	
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_user WHERE (atasan1 = '$nama' ||  atasan2 = '$nama' ||  atasan3 = '$nama') && lokasi = $data_user_lokasi");
	while($row = db_fetch_array($db_data)) {
		$hasil[]	= $row['username'];
	}
	db_set_active();
	
	array_push($hasil,$nama);
	return $hasil;
}
function mdc_reservation_view() { // mdc/online/reservation/view
	$status_pil = array(0=>'All',1=>'Waiting for Atasan Approval',2=>'Approved by Atasan, waiting for SCM Approval',3=>'Approved by Atasan, ready for Goods Issue',4=>'Good Issued',5=>'Rejected by Atasan',6=>'Rejected by SCM',7=>'Item Received');
	
	// cek is ADMIN ?
	// $idRoles 	= array('182,183,181,180');
	$cekAnggota		= mdc_is_atasan(); // <<<==== dsini OK, dptkan atasan yg sdg login
	$user_data		= mdc_user_roles();
	$rolesSprAdmin	= $user_data['sa'];
	$rolesUser		= $user_data['issuer'];	
	$un				= $user_data['nama'];
	$nopek 			= get_nopek_org($un);
	
	$rolesAdmin		= $user_data['admin'];
	$rolesSCM		= $user_data['scmApp'];
	$mgrApp			= $user_data['mgrApp'];
	
	$periode_awal 	= mdc_konversi_tgl($_GET['periode_awal']);
	$periode_akhir 	= mdc_konversi_tgl($_GET['periode_akhir']);		
	$status		 	= $_GET['status'];
	$hasil = "<a href='online'>[back]</a><br><br>";
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('Date Created'),),
			array('data'=>t('No. Reservasi'),),
			array('data'=>t('Plant'),),			
			array('data'=>t('Status'),),
			array('data'=>t('Request By'),),
			array('data'=>t('Action'),),
	);	
	
	db_set_active('pep');	
	// =============================================================================================================
	if($nopek && $rolesUser){					// jika pekerja dan bukan Atasan, tampilkan hanya sesuai login = requestBy
		$reques		= ' && requestBy = "' .$un. '"';
	}
	if($mgrApp){								// jika atasan tampilkan semua user dibawahnya saja tampil	
		$list_user	= implode($cekAnggota,'","');
		$reques		= ' && requestBy IN ("' .$list_user. '")';
	}
	if($rolesSprAdmin || $rolesAdmin || $rolesSCM){							// jika SA, tampilkan SEMUA
		$reques		= '';
	}
	if($status>0){ //  && requestBy = $requestBy <<= tampilkan hanya berdasarkan login
		$statusv	= ' && statusApproval = ' .$status;
	}
	if($rolesSCM){							// jika SCM, tampilkan hanya yg harus di approve SCM
		$statusv	= ' && statusApproval = 2';
	}
	if(!$nopek){							// jika pekarya, tampilkan hanya sesuai login = input || <<== pindahkan setelah $nopek && $rolesUser
		$reques		= " && input = '" .$un. "'";
	}
	
	// =============================================================================================================
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE createTime >= $periode_awal && createTime <= $periode_akhir $statusv $reques ORDER BY requestBy,createTime DESC");
	// =============================================================================================================
	
	while($row = db_fetch_array($db_data)) {
		$data_detil	= mdc_reservation_data_select($row['reservasiNo']);
		$idPlant	= $data_detil['idPlant'];
		$plant_name	= mdc_plant_data($idPlant);
		$fullname	= cek_fullname($row['requestBy']);
		
		if($mgrApp){ // $rolesSCM || 
			$txtApp = 'Approve';
		}else{
			$txtApp = 'View';
		}
		
		switch ($row['statusApproval']) { // 2:manager ; 3: SCM ; 4:GI 
			case 1:
				$approved 		= "<a href='" .base_path(). "mdc/online/reservation/approved/?id=" .$row['reservasiNo']. "&key=3&awal=" .$periode_awal. "&akhir=" .$periode_akhir. "'>" .$txtApp. "</a>";
				if($mgrApp){ 	// <<= yg boleh me-reject Atasan
					$approved		.= " | <a href='" .base_path(). "mdc/online/reservation/reject/?id=" .$row['reservasiNo']. "&key=2' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>Reject</a>";
				}
				break;
			case 2:
				$approved 		= "<a href='" .base_path(). "mdc/online/reservation/approved/?id=" .$row['reservasiNo']. "&key=3&awal=" .$periode_awal. "&akhir=" .$periode_akhir. "'>" .$txtApp. "</a>";
				if($rolesSCM){ // <<= yg boleh me-reject SCM
					$approved		.= " | <a href='" .base_path(). "mdc/online/reservation/reject/?id=" .$row['reservasiNo']. "&key=3' onclick='if(confirm(\"are you sure ?\") != true){ return false }'>Reject</a>";
				}
				break;
			case 3:
				$approved 		= "Approved by Atasan, ready for Goods Issue";
				break;
			case 4:
				$approved 		= "Good Issued";
				break;
			case 5:
				$approved 		= "Rejected by Atasan";
				break;
			case 6:
				$approved 		= "Rejected by SCM";
				break;
			case 7:
				$approved 		= "Item Received";
				break;
		}
		
		$reservasi	= "<a href='" .base_path(). "mdc/online/reservation/view/data/" .$row['reservasiNo']. "/" .$_GET['periode_awal']. "/" .$_GET['periode_akhir']. "'>" .$row['reservasiNo']. "</a>"; // + #1
		if($rolesSprAdmin){ 	// jika SA -> tampilkan semua tanpa filter ; || $rolesSCM || $rolesAdmin || $mgrApp
			$isi[] = array(++$xyz, mdc_konversi_tgl($row['createTime'],1), $reservasi, $plant_name['description'], $status_pil[$row['statusApproval']], $fullname, $approved);
		}else{ 	
				// =================== Tampilkan hanya yg plant/lokasi yg sama dengan login jika buka SA ================================
				$cek_plant 			= cek_data_plant_mdc($row['reservasiNo']); 	// <<= cek plant yg sama dgn login; feedback -> reservasiNoz
				if($cek_plant == $row['reservasiNo']){ 				// jika lokasi sama => tampilkan
					$isi[] = array(++$xyz, mdc_konversi_tgl($row['createTime'],1), $reservasi, $plant_name['description'], $status_pil[$row['statusApproval']], $fullname, $approved);
				}
				// =================== END ================================
		}
	}
	db_set_active();
	$output = theme_table($judul, $isi);
	return $hasil.$output;
}
function mdc_res_view_data(){ // + #2
	$reservasiNo	= arg(5);
	$aw				= arg(6);
	$ak				= arg(7);
	$hasil = "<a href='" .base_path(). "mdc/online/reservation/view?periode_awal=" .$aw. "&periode_akhir=" .$ak. "'><< back </a><br><br>";
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('Item'),),
			array('data'=>t('Request Qty'),),
			array('data'=>t('Accept Qty'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo' && isActive = 1 ORDER BY id ASC");
	while($row = db_fetch_array($db_data)) {
		$cek_stok		= mdc_stock_data($row['idStock']);
		$cek_material 	= $cek_stok['idMaterial'];
		$material_id	= mdc_material_data($cek_material);
		$material 		= $material_id['description'];
		$isi[] = array(++$xyz,$material,$row['requestQty'],$row['acceptQty']);
	}
	db_set_active();
	$output = theme_table($judul, $isi);
	return $hasil.$output;
}
function cek_data_plant_mdc($reservasiNo){
	$data_user_lokasi	= mdc_lokasi_user();
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo' && idPlant = $data_user_lokasi");
	while($row = db_fetch_array($db_data)) {		
		$hasil 				= $row['reservasiNo'];
	}
	return $hasil;
}
function mdc_reservation_data_header($reservasiNo) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo = '$reservasiNo'");
	while($row = db_fetch_array($db_data)) {
		$hasil['id']				= $row['id'];
		$hasil['statusApproval']	= $row['statusApproval'];
		$hasil['requestBy']			= $row['requestBy'];
		$hasil['input']				= $row['input'];
		$hasil['issuer']			= $row['issuer'];
		$hasil['noteMgr']			= $row['noteMgr'];
		$hasil['noteScm']			= $row['noteScm'];
		$hasil['createTime']		= $row['createTime'];
		$hasil['nameClose']			= $row['nameClose'];
		$hasil['timeClose']			= $row['timeClose'];
		$hasil['noteClose']			= $row['noteClose'];
	}//$nameClose $timeClose $noteClose ; $idStock $requestQty $acceptQty
	db_set_active();
	return $hasil;
}
function theme_columns_checkboxes_mdc($e) {
	$options = $e['#options'];
	// Set the default if no columns are given.
	if (!isset($e['#columns'])) {
		$e['#columns'] = 8;
	}
	// Set the column number if less than the set amount.
	if (count($options) < $e['#columns']) {
		$e['#columns'] = count($options);
	}
	$rows = array();
	foreach ($options as $key=>$value) {
		$row[] = theme_checkbox($e[$key]);
		if (count($row) == $e['#columns']) {
			array_push($rows,$row);
			$row = array();
		}
	}
	// This flushes out the columns when the items don't divide evenly into the columns.
	if (count($row)) {
		array_push($rows,$row);
	}
	return theme_table(NULL, $rows);
}
// END VIEW RESERVATION ============================================================================
function mdc_reservation_editJml(){
	$key	= $_GET['key'];
	$iditem	= $_GET['id'];
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE id = $iditem");
	while($row = db_fetch_array($db_data)) {
		$idres 	= $row['reservasiNo'];
		$qty 	= $row['requestQty'];
		$idStock= $row['idStock'];
		$idPlant= $row['idPlant'];
	}
	db_set_active();
	$cek_stok		= mdc_stock_data($idStock);
	$cek_material 	= $cek_stok['idMaterial'];
	$material_id	= mdc_material_data($cek_material);
	$material 		= $material_id['description'];
	$satuan 		= $material_id['satuan'];
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['idres'] = array(
			'#type' => 'hidden',
			'#default_value' => $idres
	);
	$form['iditem'] = array(
			'#type' => 'hidden',
			'#default_value' => $iditem
	);
	$form['key'] = array(
			'#type' => 'hidden',
			'#default_value' => $key
	);
	$form['idStock'] = array(
			'#type' => 'hidden',
			'#default_value' => $idStock
	);
	$form['item_markup'] = array(
			'#value' => t('<strong>' .$material. '</strong><br>Stock : ' . $cek_stok['qty'] . ' ' . $satuan),
			'#weight' => 1,
	);
	$form['jml'] = array(
			'#title' => t('Nilai Revisi'),
			'#type' => 'textfield',
			'#size' => 5,
			'#maxlength' => 3,
			'#weight' => 2,
			'#default_value' => $qty,
			'#description' => t($satuan)
	);
	$form['submit'] = array (
			'#type' => 'submit',
			'#value' => t('Edit'),
			'#weight' => 99,
	);
	return $form;
}
function mdc_reservation_editJml_submit($form, &$form_state){
	$idres 		= $form_state['idres'];
	$iditem 	= $form_state['iditem'];
	$key 		= $form_state['key'];
	$jml 		= $form_state['jml'];
	$idStock 	= $form_state['idStock'];
	$cek_sedia	= mdc_stock_cek($idStock);
	
	if($jml > $cek_sedia){
		drupal_set_message('Nilai Permintaan Melebihi Stock Yang Ada !','error');
		drupal_goto('mdc/online/reservation/approved/','id=' .$idres. '&key=' .$key);
	}
	
	db_set_active('pep');
	$hasil_update = db_query("UPDATE mdc_reservation_detil SET acceptQty = $jml WHERE id = $iditem");
	if($hasil_update){
		drupal_set_message('UPDATE Success ...');
	}
	db_set_active();	
	
	drupal_goto('mdc/online/reservation/approved/','id=' .$idres. '&key=' .$key);
}
function mdc_id_to_nama($reservasiNo){
	// cek id to nama => smua id dengan username yg sama pada 30 hari terakhir (output mdc_reservation_detil : reservasiNo dengan username => sama)
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo = '$reservasiNo'");
	while($row = db_fetch_array($db_data)) {
		$requestBy 	= $row['requestBy'];
		$akhir	 	= $row['createTime'];
	}
	
	$tgl	= date("d-m-Y", $akhir);
	$pecah 	= explode('-', $tgl);
	$awal	= mktime(0,0,0,$pecah[1],$pecah[0]-30,$pecah[2]); // <<= dikurang 30 hari dari tgl reservasi (utk dptkan awal tgl filter)
	
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE requestBy = '$requestBy' && createTime > $awal && createTime <= $akhir");
	while($row = db_fetch_array($db_data)) {
		$noReservasi[] 	= $row['reservasiNo'];
	}
	db_set_active();
	
	return $noReservasi; // <<= output $noReservasi dgn nama yg sama, pada 30 hari terakhir ! OK !
}
function mdc_reservation_history(){ // mdc/online/reservation/history/?id=
	$reservasiNo	= $_GET['id'];
	$statusApproval	= $_GET['key'];	
	$itemid			= $_GET['itemid'];	
	$cek			= mdc_id_to_nama($reservasiNo);
	$back	= '<a href="' .base_path(). 'mdc/online/reservation/approved/?id=' .$reservasiNo. '&key=' .$statusApproval. '">[back]</a><br><br>';
	
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('Date Reservasi'),), // mdc_reservation
			array('data'=>t('No. Reservasi'),), // mdc_reservation
			array('data'=>t('Qty (permintaan)'),), // mdc_reservation_detil
			array('data'=>t('Qty (diberikan)'),),
			array('data'=>t('Qty (stock)'),),
	);
	
	$data_res 		= mdc_reservation_data_header($reservasiNo);
	$name_req		= $data_res['requestBy'];	
	$cek_stok		= mdc_stock_data($itemid);
	$cek_material 	= $cek_stok['idMaterial'];
	$material_id	= mdc_material_data($cek_material);
	$satuan 		= $material_id['satuan'];
	$material 		= $material_id['description'];
	
	// cek kondisi view stock, user SCM atau Super ADMIN
	$stat_pkrj		= mdc_user_roles();
	$sa				= $stat_pkrj['sa'];
	$scmApp			= $stat_pkrj['scmApp'];
	if($sa || $scmApp){ // jika user SCM atau Super ADMIN
		$view_stock	= $cek_stok['qty'];
	}else{
		$view_stock	= $cek_stok['rsvQty'];
	}
	
	// cek $reservasiNo & $itemid(idStock) == mdc_reservation_detil	
	foreach ($cek as $key => $noReservasi){ // (output mdc_reservation_detil : reservasiNo (sdh difilter nama sama) & idStock => sama)
		$xx = mdc_reservation_data_header($noReservasi); // createTime
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$noReservasi' && idStock = $itemid");		
		while($row = db_fetch_array($db_data)) {		
			$isi[] = array(++$xyz, date('d-m-Y',$xx['createTime']), $row['reservasiNo'], $row['requestQty'] .' '. $satuan, $row['acceptQty'] .' '. $satuan, $view_stock .' '. $satuan);
		}
		db_set_active();
	}
	
	$data_item	= "User Request 	: <strong>$name_req</strong><br>
					Item 			: <strong>$material</strong>
					<br><br>";
	
	$output = theme_table($judul, $isi);
	return $back.$data_item.$output;
}
function mdc_qtyStock($idStock,$idPlant){
	db_set_active('pep');
// 	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE idStock = $idStock && idPlant = $idPlant");
	$db_data = db_query("SELECT * FROM pep.mdc_reservation as a left join pep.mdc_reservation_detil as b on b.reservasiNo = a.reservasiNo WHERE b.idStock = $idStock && b.idPlant = $idPlant && a.statusApproval <= 2");	
	
	while($row = db_fetch_array($db_data)) {
		$hasil += $row['requestQty'];
	}
	return $hasil;
}
function mdc_list_data_approve($reservasiNo){
	drupal_add_css(drupal_get_path('module', 'mdc').'/css/nyroModal.css');
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.min.js');	
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.nyroModal-1.6.2.pack.js');
	
	$user_data		= mdc_user_roles();
	$rolesSprAdmin	= $user_data['sa'];
	$rolesAdmin		= $user_data['admin'];
	$rolesSCM		= $user_data['scmApp'];
	$mgrApp			= $user_data['mgrApp'];
	
	$hasil['utama'] = '<table cellpadding="0"  cellspacing="0">
				  <thead><tr>
					<th>Item</th>
					<th>Qty Permintaan</th>
					<th>Qty Disetujui</th>
					<th>Stock</th>
					<th>Open Reservasi</th>
					<th>History</th>
				  <tr></thead>';
	db_set_active('pep');
	$x = 0;
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo' && isActive = 1 ORDER BY id ASC");
	while($row = db_fetch_array($db_data)) {
		$x++;
		$data_id[]		= $row['id'];
		$data_stok		= mdc_stock_data($row['idStock']);
		$sisaStock		= $data_stok['qty'];
		$openStock		= $sisaStock - mdc_qtyStock($row['idStock'],$row['idPlant']); // mnentukan nilai stok reservasi, pada lokasi yg sama dikurangi dari stok real.
		$item			= mdc_material_data_select($data_stok['idMaterial']);
		$satuan			= mdc_material_data($data_stok['idMaterial']);
		$plant_data		= mdc_plant_data($row['idPlant']);
		$plant_name		= $plant_data['description'];
		if($rolesSprAdmin || $rolesSCM || $rolesAdmin){ //  onkeypress="return event.charCode >= 48 && event.charCode <= 57"
			$edit			= '<input type="text" name="qty_'.$x.'" id="qty_'.$x.'" value="' .$row["acceptQty"]. '" size="5" maxlength="3" /> '. $satuan['satuan'];
		}else{
			$edit			= $row["acceptQty"]. ' '. $satuan['satuan'];
// 			$edit			= '<input type="text" name="qty_accept" id="qty_accept" value="' .$row["acceptQty"]. '" size="5"/> '. $satuan['satuan'];
		}
// 		$approval[$row['id']] = $item .' - <strong>QTY : ' .$row['acceptQty']. ' ' .$satuan['satuan']. '</strong>' .$edit. ' |
// 									<a href="' .base_path(). 'mdc/online/reservation/history/?id=' .$reservasiNo. '&key=' .$statusApproval. '&itemid=' .$row['idStock']. '" class="nyroModal">[history]</a>';
// 		$idSmua[]		= $row['id'];
		
		if($x%2){
			$klas = 'even';
		}else{
			$klas = 'odd';
		}
		$hasil['utama'] .= '<tr class="' .$klas. '">
					<td>' .$item. '</td>
					<td>' .$row["requestQty"]. ' '. $satuan["satuan"]. '</td>
					<td>' .$edit. '</td>
					<td>' .$sisaStock. ' ' .$satuan['satuan']. '</td>
					<td>' .$openStock. ' ' .$satuan['satuan']. '</td>
					<td><a href="' .base_path(). 'mdc/online/reservation/history/?id=' .$reservasiNo. '&key=' .$statusApproval. '&itemid=' .$row['idStock']. '" class="nyroModal">[history]</a></td>
				  </tr>';
	}
	db_set_active();
	$hasil['utama'] 	.= '</table>';
	$hasil['dataid'] 	= $data_id; // $data_id
	$hasil['tot'] 		= $x;
	return $hasil;
}
function mdc_reservation_approved(){ // mdc/online/reservation/approved/?id=
	$reservasiNo	= $_GET['id'];
	$statusApproval	= $_GET['key'];
	$awal			= date('Y-m-d',$_GET['awal']);
	$akhir			= date('Y-m-d',$_GET['akhir']);
	
	$user_data		= mdc_user_roles();
	$rolesSprAdmin	= $user_data['sa'];
	$rolesAdmin		= $user_data['admin'];
	$rolesSCM		= $user_data['scmApp'];
	$mgrApp			= $user_data['mgrApp'];		
	
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_back_markup'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/reservation/view?periode_awal=" .$awal. "&periode_akhir=" .$akhir. "'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	$data_res 	= mdc_reservation_data_header($reservasiNo);
	$name_req	= mdc_user_data($data_res['requestBy']); $name_req = $name_req['fullname'];
	$name_inp	= mdc_user_data($data_res['input']); $name_inp = $name_inp['fullname'];
	$tgl_res	= date('d-m-Y',$data_res['createTime']);
	$form['mdc_detil_markup'] = array(
			'#value' => t("User Input 	: <strong>$name_inp</strong><br>
							User Request 	: <strong>$name_req</strong><br>
							Res. No 		: <strong>$reservasiNo</strong><br>
							Date Request 	: <strong>$tgl_res</strong>
							<br><br>"),
			'#weight' => 1,
	);
	
	// === LIST Data Approve
	$data 		= mdc_list_data_approve($reservasiNo);
	$data_list 	= $data['utama'];
	$dataid 	= implode($data['dataid'],'_'); 
	$tot		= $data['tot'];
	
	$form['tambah'] = array(
			'#title' => t('Approval List'),
			'#value' =>$data_list,
			'#weight' => 2,
	);
	$form['resNo'] = array(
			'#type' => 'hidden',
			'#default_value' => $reservasiNo
	);
	$form['dataid'] = array(
			'#type' => 'hidden',
			'#default_value' => $dataid
	);
	// === End LIST Data Approve
	
	$form['statusApproval'] = array(
			'#type' => 'hidden',
			'#default_value' => $statusApproval
	);
	if(isset($rolesSprAdmin) || isset($rolesSCM) || isset($mgrApp)){ 
		if(($statusApproval==3) && ($mgrApp)){
			$form['notes'] = array(
					'#title' => t('Catatan'),
					'#type' => 'textarea',
					'#weight' => 5,
			);
			$form['submit'] = array (
					'#type' => 'submit',
					'#value' => t('Approve'),
					'#weight' => 98,
			);
			$form['mdc_reject_markup'] = array(
					'#value' => t('<a href="' .base_path(). 'mdc/online/reservation/reject/?id=' .$reservasiNo. '&key=' .$statusApproval. '" onclick="if(confirm(\'are you sure ?\') != true){ return false }"><input type="button" value="Reject" /></a>'),
					'#weight' => 99,
			);
		}
	
		if($statusApproval==7){
			$form['submit'] = array (
					'#type' => 'submit',
					'#value' => t('Submit'),
					'#weight' => 98,
			);		
		}
	}
	return $form;
}
function mdc_user_lokasi_toemail($lokasi,$stat,$uns = null) { // akses3 = 172 ; akses2 = 171 
	if($stat == 2){						// Atasan Approve
		$akses 	= '&& akses2 = 181'; 	// 171 : role SCM
	}elseif($stat == 3){				// SCM Approve => send email to gudang
		$akses 	= '&& akses4 = 180'; 	// 173 : role gudang
	}
	
	if ($stat == 1){				// 1 : info utk user reservasi // tester => $akses 	= '&& akses1 = 170';
		$un[] 	= $uns;
		if($uns != $GLOBALS['user']->name){
			$un[] 	= $GLOBALS['user']->name;
		}
		// dapmdcan atasan user
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_user WHERE username='$uns'");
		while($row = db_fetch_array($db_data)) {
			$at1	= $row['atasan1'];
			$at2	= $row['atasan2'];
			$at3	= $row['atasan3'];
			if($at1){
				$un[] 	= $row['atasan1'];
			}
			if($at1){
				$un[] 	= $row['atasan2'];
			}
			if($at1){
				$un[] 	= $row['atasan3'];
			}
		}
		db_set_active();
		// END dapmdcan atasan user
	}
	if (($stat == 2) || ($stat == 3)){ // khusus 2 & 3 saja !
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_user WHERE lokasi = $lokasi $akses");
		while($row = db_fetch_array($db_data)) {
			$un[] 	= $row['username'];
		}
		db_set_active();
	}
	
	// jika stat = 9
	if (($stat == 9) || ($stat == 5) || ($stat == 6)){ // jika dari "send" gudang -> dapmdcan user (input,requestBy,scmApproval,mgrApproval) ; 5=reject atasan ; 6=reject SCM
		db_set_active('pep');
		$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo = '$uns'"); // jika $stat = 9 => $uns = reservasiNo
		while($row = db_fetch_array($db_data)) {
			$un[] 	= $row['input'];
			$un[] 	= $row['requestBy'];
		}
		db_set_active();
	}
	// END jika stat = 9
	
	foreach ($un as $us){
		$hasil[]		= mdc_email_user($us);
	}
	array_push($hasil, "pep-webdev01.mdgti@pep.pertamina.com"); // , "christian.arlin@pep.pertamina.com"
	$hasil	= implode($hasil, ',');
	return $hasil;
}
function mdc_data_detil($reservasiNo){
	// mdc_reservation_detil
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo'");
	while($row = db_fetch_array($db_data)) {
		$hasil[$row['idStock']]['item'] 	= $row['idStock'];
		$hasil[$row['idStock']]['request'] 	= $row['requestQty'];
		$hasil[$row['idStock']]['accept'] 	= $row['acceptQty'];
	}
	db_set_active();
	return $hasil;
}
function mdc_pesanemail($statusApproval,$reservasiNo,$un = NULL){	
	global $base_url;
	// 1. dptkan id lokasi request,
		$data_detil = mdc_reservation_data_select($reservasiNo); // data detil
		$lokasi_id	= $data_detil['idPlant']; // id lokasi (berdasar $reservasiNo)
		
	// 2. cari SCM dengan lokasi yg sama, -> hasil email SCM
		if($statusApproval == 1){
			$toemailx	= mdc_user_lokasi_toemail($lokasi_id,$statusApproval,$un); // email bisa >1 user, dipisah ','
		}elseif (($statusApproval == 2) || ($statusApproval == 3)){
			$toemailx	= mdc_user_lokasi_toemail($lokasi_id,$statusApproval);
		}elseif ($statusApproval == 9){
			$toemailx	= mdc_user_lokasi_toemail($lokasi_id,$statusApproval,$reservasiNo);
		}elseif (($statusApproval == 5) || ($statusApproval == 6)){
			$toemailx	= mdc_user_lokasi_toemail($lokasi_id,$statusApproval,$reservasiNo);
		}
		
	// tambahan EMAIL, jika Approval by SCM => send mail : input, request, atasan
		if($statusApproval == 3){
			db_set_active('pep');
			$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo = '$reservasiNo'");
			while($row = db_fetch_array($db_data)) {
				$toemailx 	.= ',' . mdc_email_user($row['mgrApproval']);
			}
			db_set_active();
		}
	
	$toemail	= $toemailx; // "pep-webdev01.mdgti@pep.pertamina.com,christian.arlin@pep.pertamina.com"; 				// sample <<= SEMENTARA !
	$subjek 	= 'mdc Online Approval Information';
	
	if($statusApproval == 1){ // 1 : New Reservasi
		$status = "New Reservation. Waiting for Atasan Approval";
	}elseif($statusApproval == 2){ // 2 : Approved by Manager, 3=> Approved by SCM
		$data_header = mdc_reservation_data_header($reservasiNo);
		$status = "Approved by Atasan. Waiting for SCM Approval";
		$status .= "<br><br>";
		$status .= "Catatan : " .$data_header['noteMgr'];
	}elseif($statusApproval == 5){
		$status = "Reject by Atasan";
	}elseif($statusApproval == 6){
		$status = "Reject by SCM";
	}elseif($statusApproval == 3){
		$data_header = mdc_reservation_data_header($reservasiNo);
		$status = "Approved by Atasan. Ready for Goods Issue";
		$status .= "<br><br>";
		$status .= "Catatan : " .$data_header['noteScm'];
	}elseif($statusApproval == 9){
		$subjek 	= 'mdc Online - Barang Telah diambil';
		// ambil semua data $reservasiNo, pengambilan item		
		
		// End ambil semua data $reservasiNo, pengambilan item
		$data_header = mdc_reservation_data_header($reservasiNo); // data header		
		$status = "Barang telah diambil";
		$status .= "<br>Oleh : " .$data_header['nameClose'];
		$status .= "<br>Waktu : " .mdc_konversi_tgl($data_header['timeClose'],1);
		
		// ========== LOOP DATA ====================================
		$data	=	mdc_data_detil($reservasiNo);
		foreach ($data as $idStock => $value){
			$cek_stok		= mdc_stock_data($idStock);
			$cek_material 	= $cek_stok['idMaterial'];
			$material_id	= mdc_material_data($cek_material);
			$material 		= $material_id['description'];
			
			$status .= "<br><br>";
			$status .= "Item: " .$material. "<br>";
			$status .= "Request Qty: " .$value['request']. "<br>";
			$status .= "Accept Qty: " .$value['accept'];
		}
		// ========== END LOOP DATA ====================================
		
		$status .= "<br><br>";
		$status .= "Catatan: " .$data_header['noteClose'];
	}
	
	$from 		= "pep-noreply@pep.pertamina.com"; 
	$isiemail	= "Info mdc Online<br><br>";
	
	$dataRes	= mdc_reservation_data_header($reservasiNo);
	if(!isset($dataRes)){
		$dataRes['createTime']= mktime();
	}
	$awl		= date("Y-m-d", $dataRes['createTime']);
	$akh 		= date('Y-m-d', strtotime($awl . ' +1 day'));
	
	if($statusApproval == 9){
		$stt		= 0;
	}else{
		$stt		= $statusApproval;
	}
	if($statusApproval == 3){ // mdc/online/issue/view/data/?id=mdcR-160222-000002&stat=3
		$links		= 'http://portal.pertamina-ep.com/mdc/online/issue/view/data/?id=' .$reservasiNo. '&stat=3';
	}else{
		$links		= 'http://portal.pertamina-ep.com/mdc/online/reservation/view?periode_awal=' .$awl. '&periode_akhir=' .$akh. '&status=' .$stt;
	}
	if(($statusApproval == 5) || ($statusApproval == 6)){ 
		$links		= 'http://portal.pertamina-ep.com/mdc/online/issue/view/data/?id=' .$reservasiNo. '&stat=' .$statusApproval;
	}
	
	$isiemail	.= "No. Reservasi: <a href='" .$links. "'>" .$reservasiNo. "</a><br><br>";	
	$isiemail	.= "Status: " .$status. "<br><br><br>";
	$isiemail	.= "***<br>Thanks regards,<br>";
	$isiemail	.= "mdc Online Team<br>";
	
	$headers	= array(
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/html',
			'Content-Transfer-Encoding' => '8Bit',
			'X-Mailer' => 'Drupal',
	);
	
// 	$kirim = drupal_mail('mdc', $toemail, $subjek, $isiemail, $from, $headers);		// !!! matikan sementara !!!!
	// =============================================================
}
function mdc_disable_rev_detil($id){
	db_set_active('pep');
	$hasil_update = db_query("UPDATE mdc_reservation_detil SET isActive = 0 WHERE id = $id");
	if($hasil_update){
		drupal_set_message('Item Dihapus !','warning');
	}
	db_set_active();
}
function mdc_reservation_approved_submit($form, &$form_state){
// 	$approved 		= $form_state['approved'];
	$statusApproval = $form_state['statusApproval'];
	$pesan			= $form_state['notes'];	

	$user_data		= mdc_user_roles();
	$isSCM			= $user_data['scmApp'];
	$un				= $user_data['nama'];
	$nopek			= get_nopek_org($un);
// 	$approved 		= $_POST['qty_accept'];
	$resNo 			= $form_state['resNo'];
	$dataid 		= explode('_',$form_state['dataid']);	
	
// 	if($statusApproval==3){  // Approval SCM
		foreach ($dataid as $xy => $idRes) {						// $idRes = id reservasi detil
			$qtyEdit 	= $xy + 1; 									// <<= no urut variable POST
			$jmlEd 		= $_POST['qty_' . $qtyEdit];				// nilai QTY yg disetujui oleh SCM
	// 		var_dump($idRes .'_'. $_POST['qty_' . $qtyEdit]);		// sesuaikan data urutan dgn data yg diEDIT		
	
			//===
			db_set_active('pep');
			$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE id = $idRes ORDER BY id ASC"); // id = $id
			while($row = db_fetch_array($db_data)) {
				$idReservasi[$row['reservasiNo']]	= $row['reservasiNo'];
				$nilReq								= $row['requestQty'];
				$idstk								= $row['idStock']; // idStock utk item yg dikurangi/ditambah
	// 			$kurangiStock[$row['idStock']]		= $row['requestQty'];
			}
			db_set_active();
			
			// rencana HAPUS !
			$user_data	= mdc_user_roles();
			$isSCM		= $user_data['scmApp'];
			$isMan		= $user_data['mgrApp'];
// 			if(!$value && ($isSCM || $isMan)){ // jika approve 0 and user scm/atasan => disable item TRUE
// 				$removed	= mdc_disable_rev_detil($id);
					
// 				$page 		= 'Disable Unselected Item';
// 				$ket['User']= $isSCM?'SCM':'Atasan';
// 				$hasil = mdc_logs($page,$ket);
// 			}
			// END rencana HAPUS !
			//===
			
			if($statusApproval==3){
				// #1 update data mdc_stock : (qty, rsvQty) => qty - $jmlEd ; rsvQty - $jmlEd
				$stok_data 	= mdc_stock_data($idstk);
				$jml_qty 	= $stok_data['qty'] - $jmlEd;
				$jml_rsvQty	= $stok_data['rsvQty'] - $nilReq; // $jmlEd
				
				db_set_active('pep'); // update Quantity
				if($jmlEd == 0){ // jika $jmlEd=0 berarti di tolak/reject
					// $jml_rsvQty = $stok_data['rsvQty'] - $nilReq;
					$hasil_update = db_query("UPDATE mdc_stock SET rsvQty = $jml_rsvQty WHERE idStock = $idstk");
					// catat LOG for maintenance
					$page 				= 'Update mdc_stock by System';
					$idData['jmlEd']	= $jmlEd;
					$idData['rsvQty']	= $jml_rsvQty;
					$idData['idStock']	= $idstk;
					$hasilMaintenance 	= mdc_logs($page,$idData);
					$page = ''; unset($idData);
				}else{
					$hasil_update = db_query("UPDATE mdc_stock SET qty = $jml_qty, rsvQty = $jml_rsvQty WHERE idStock = $idstk"); // sampe sini update QTY yg diterima; $idstk = mdc_stock(id)
					// catat LOG for maintenance
					$page 				= 'Update mdc_stock by System $jmlEd > 0';
					$idData['jmlEd']	= $jmlEd;
					$idData['qty']		= $jml_qty;
					$idData['rsvQty']	= $jml_rsvQty;
					$idData['idStock']	= $idstk;
					$hasilMaintenance 	= mdc_logs($page,$idData);
					$page = ''; unset($idData);
				}
				db_set_active();
				// END update data mdc_stock
				
				// #2 UPDATE acceptQty
				db_set_active('pep'); // update nilai reservasi
				if($jmlEd == 0){ //  jika $jmlEd=0 berarti di tolak/reject ; requestQty=0, acceptQty=0
					$hasil_update = db_query("UPDATE mdc_reservation_detil SET requestQty = 0, acceptQty = 0 WHERE id = $idRes");
					// catat LOG for maintenance
					$page 				= 'Update mdc_reservation_detil by System (di (0) utk stat tolak/ reject)';
					$idData['jmlEd']	= $jmlEd;
					$idData['requestQty']	= 0;
					$idData['acceptQty']	= 0;
					$idData['idRes']	= $idRes;
					$hasilMaintenance 	= mdc_logs($page,$idData);
					$page = ''; unset($idData);
				}else{
					$hasil_update = db_query("UPDATE mdc_reservation_detil SET acceptQty = $jmlEd WHERE id = $idRes"); // sampe sini update QTY yg diterima;  $idRes = mdc_reservation_detil(id)
					// catat LOG for maintenance
					$page 				= 'Update mdc_reservation_detil by System';
					$idData['jmlEd']	= $jmlEd;
					$idData['acceptQty']= $jmlEd;
					$idData['idRes']	= $idRes;
					$hasilMaintenance 	= mdc_logs($page,$idData);
					$page = ''; unset($idData);
				}			
				db_set_active();
				// END UPDATE acceptQty
				$jml_qty 	= 0;
				$jml_rsvQty	= 0;
			}
		}
// 		$kirim_email = mdc_pesanemail($statusApproval,$reservasiNo);
// 	} // end Approval SCM
// 	$x=0;
	// approved reservasiNo yg didapat
	foreach ($idReservasi as $reservasiNo => $value) {
		switch ($statusApproval) { // 2:manager ; 3: SCM ; 4:GI 
			case 2:
				$tambahan 	= ', mgrApproval="' .$un.'", noteMgr="' .$pesan.'", timeMgrApproval="' .mktime().'", nopekMgrApproval="' .$nopek.'"';
				$ket = 'Atasan';
				break;
			case 3:
// 				$tambahan 	= ', scmApproval="' .$un.'", noteScm="' .$pesan.'", timeScmApproval="' .mktime().'", nopekScmApproval="' .$nopek.'"';
				$tambahan 	= ', mgrApproval="' .$un.'", noteMgr="' .$pesan.'", timeMgrApproval="' .mktime().'", nopekMgrApproval="' .$nopek.'"';
				$ket = 'Atasan';
				break;
			case 4:
				$tambahan 	= ', timeIssuerApproval="' .mktime().'"';
				break;
		}
		db_set_active('pep');	
		$hasil_update = db_query("UPDATE mdc_reservation SET statusApproval = $statusApproval $tambahan WHERE reservasiNo = '$reservasiNo'");
		if($hasil_update){
// 			drupal_set_message('Approval Success ...');
			//============ Kirim Status Pesan DISINI ! =================================
// 			if($statusApproval == 2){ // (($statusApproval == 2) || ($statusApproval == 3))
// 				$kirim_email = mdc_pesanemail($statusApproval,$reservasiNo);						*** MATIKAN KIRIM EMAIL
// 			}
			//============ END Kirim Status Pesan DISINI ! =============================
		}
		db_set_active();
	}	
	
// 	if($statusApproval==3){ // jika ScmApproval => stok (sementara - rsvQty) gudang dikurangi (data real -qty- berkurang jika sdh diambil)
		// ====================================================================================================
		// mdc_reservation_detil => reservasiNo, idStock, requestQty
		// kurangkan mdc_stock => qty - requestQty => UPDATE
// 		db_set_active('pep');
// 		$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo'");
// 		while($row = db_fetch_array($db_data)) {
// 			$dataStok	= mdc_stock_data($row['idStock']);
// 			$sisa		= $dataStok['qty'] - $row['requestQty']; // rsvQty : qty ==============================================================
// 			$hasil 		= mdc_update_stok($row['idStock'],$sisa,1); // 2:stok reservasi
// 		}
// 		db_set_active();
		// ====================================================================================================
// 	}	
	if($statusApproval==2 || $statusApproval==3){
		drupal_set_message('Approval ' .$ket. ' Success');
	}
	drupal_goto('mdc/online/reservation');
}
function mdc_update_stok($id,$sisa,$key = NULL){ // OK <<= rsvQty ; $key => 1:stok utama ; 2:stok reservasi
	if($key == 1){
		$nil_stok = 'qty 	= ' .$sisa;
	}elseif($key == 2){
		$nil_stok = 'rsvQty = ' .$sisa;
	}else{
		drupal_goto('mdc/online/reservation');
	}
	
	db_set_active('pep');		
	$hasil_update = db_query("UPDATE mdc_stock SET $nil_stok WHERE idStock = $id");
	if($hasil_update){
// 		drupal_set_message('Update Stock Success ...');
	}
	db_set_active();
}
function mdc_reject_all_res($idstk,$idRes,$nilReq){ /// ?????
		// #1 update data mdc_stock
		$stok_data 	= mdc_stock_data($idstk);
		$jml_rsvQty	= $stok_data['rsvQty'] - $nilReq; // $jmlEd
	
		db_set_active('pep'); // update Quantity
		$hasil_update = db_query("UPDATE mdc_stock SET rsvQty = $jml_rsvQty WHERE idStock = $idstk");
		db_set_active();
		// END update data mdc_stock
	
		// #2 UPDATE acceptQty
		db_set_active('pep'); // update nilai reservasi
		$hasil_update = db_query("UPDATE mdc_reservation_detil SET requestQty = 0, acceptQty = 0 WHERE id = $idRes");
		db_set_active();
		// END UPDATE acceptQty
}
function mdc_reservation_reject(){
	$reservasiNo	= $_GET['id'];
	$statusApproval	= $_GET['key'];
	$nama 			= $GLOBALS['user']->name;
	$nopek			= get_nopek_org($nama);
	switch ($statusApproval) { // 2:manager ; 3: SCM ; 4:GI 
		case 2:
			$reject 	= 5; $rjstat = 'Atasan';
			$rjtbl		= ', mgrApproval="' .$nama.'", timeMgrApproval="' .mktime().'", nopekMgrApproval="' .$nopek.'"';
			break;
		case 3:
			$reject 	= 6; $rjstat = 'SCM';
			$rjtbl		= ', scmApproval="' .$nama.'", timeScmApproval="' .mktime().'", nopekScmApproval="' .$nopek.'"';
			break;
	}
	db_set_active('pep');
	$hasil_update = db_query("UPDATE mdc_reservation SET statusApproval = $reject $rjtbl WHERE reservasiNo = '$reservasiNo'");
	if($hasil_update){
			drupal_set_message('Reservation ' .$reservasiNo. ' Reject!');
	}
	db_set_active();
	
	// =========================================================================================================
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo'");
	while($row = db_fetch_array($db_data)) {		
		$idRes					= $row['id'];
		$idstk					= $row['idStock'];
		$nilReq					= $row['requestQty'];
		
		mdc_reject_all_res($idstk,$idRes,$nilReq);
		$ket['idStk ' . $idstk]	= $idstk; $ket['idRes ' . $idRes]	= $idRes; $ket['nilReq ' . $nilReq]	= $nilReq;
	}
	db_set_active();
	// =========================================================================================================
// 	mdc_pesanemail($reject,$reservasiNo,$un = NULL); // $statusApproval => $reject						*** MATIKAN KIRIM EMAIL
	$page			= 'Reject Reservation, by ' . $nama . ' (' .$rjstat. ')';
	mdc_logs($page,$ket);
	
	drupal_goto('mdc/online/reservation');
}
// RESERVATION ===================================================================================== 
function mdc_lokasi_user($un = NULL){	
	if(!$un){
		$un	= $GLOBALS['user']->name;
	}
	
	db_set_active('pep');
	$db_data 	= db_query("SELECT * FROM mdc_user WHERE username = '$un'");
	while($row 	= db_fetch_array($db_data)) {
		$user_lokasi	= $row['lokasi'];
	}
	db_set_active();
	
	return $user_lokasi; 
}
function mdc_email_user($un = NULL){	
	if(!$un){
		$un	= $GLOBALS['user']->name;
	}
	
	db_set_active('pep');
	$db_data 	= db_query("SELECT * FROM top_person WHERE name = '$un'");
	while($row 	= db_fetch_array($db_data)) {
		$user_email	= $row['mail'];
	}
	db_set_active();
	
	$user_email = str_replace("pertamina.com","pep.pertamina.com",$user_email);
	return $user_email;
}
function mdc_kategori_pil($pili = NULL){
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_category ORDER BY description");
	while($row = db_fetch_array($db_data)) {
		if($row['idCategory'] == $pili){
			$pil = 'selected';
		}else{
			$pil = '';
		}
		$hasil 	.= "<option " .$pil. " value='" .$row['idCategory']. "'>" .$row['description']. "</option>";
	}
	db_set_active();	
	
	return $hasil;
}
function mdc_reservation_data() {
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
// 			$data_user_lokasi	= mdc_lokasi_user(); // <<= id lokasi user login
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
// 			if($data_user_lokasi == $idPlant){
// 				$lokasi_sama	= 1;
// 			}
			
			// cek kesamaan fungsi
			if($user_fungsi == $idFungsi){
				$fungsi_sama	= 1;
			}
		//===============
		
		$stock			= $row['qty'] - $row['rsvQty']; // nil QTY (dikurang) total nil QTY reservasi
		$pesan			= "<a href='" .base_path(). "mdc/online/reservation/detil/?idStok=" .$idStock. "' class='nyroModal'>Pilih</a>";	
		
		$pesan_ses		= $_SESSION['pesanbarang'][$idStock];
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
function mdc_reservation_new() {
	drupal_add_css(drupal_get_path('module', 'mdc').'/css/nyroModal.css');
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.min.js');	
	drupal_add_js(drupal_get_path('module', 'mdc') . '/js/jquery.nyroModal-1.6.2.pack.js');
	$lokasi = base_path();
	
	$data 		= mdc_reservation_data();						// Ambil Data Semua Item Stok Barang
	$output 	= theme_table($data['judul'], $data['isi']); 	// Tampilkan Semua Item Stok Barang
	$data		= $_SESSION['pesanbarang'];
	if($data){
		$submit_pesan = '<a href="' .base_path(). 'mdc/online/reservation/sabmit"><input type="button" value="Submit" /></a><br><br>';
	}
	
	$data_ses 	= mdc_data_session();
	$output_ses	= theme_table($data_ses['judul'], $data_ses['isi']);	
	
	$back 		= "<br><a href='" .base_path(). "mdc/online/issue'>[back]</a><br><br>";
	$pesan_txt	= "<strong>Daftar Pilihan (Consumption)</strong>";
// 	$autoSele	= "<br><select name='category' id='category' onchange='getval(this);'>";
// 	$autoSele	.= mdc_kategori_pil(arg(4));
// 	$autoSele	.= "</select><br>";
// 	$list_txt	= "<strong>Daftar Semua Barang</strong>";
	
	return $back.$pesan_txt.$output_ses.$submit_pesan.$output; // $xx.$list_txt.$autoSele
}
function mdc_data_session(){
	$data	= $_SESSION['pesanbarang'];
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
		
			$pesan_ses		= $_SESSION['pesanbarang'][$key];
			if($pesan_ses){
				$stock 		= $stock - $pesan_ses;
				$pesan		= $pesan_ses .' '. $satuan;
			}
			$batal			= "<a href='" .base_path(). "mdc/online/reservation/batal/?idStok=" .$key. "'>batal</a>";
			if($value){
				$isi[] 	= array(++$xyz, mdc_material_data_select($idMaterial), $pesan, $batal);
			}
		}
	}	
	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}
function mdc_reservation_detil() {
	$idStok 		= $_GET['idStok'];
	$stokData 		= mdc_stock_data($idStok);
	$stok			= $stokData['qty'];
	$idMaterial		= $stokData['idMaterial'];
	$materialData	= mdc_material_data($idMaterial);
	$description	= $materialData['description'];
	$namafile		= $materialData['fileName'];	
	$satuan			= $materialData['satuan'];
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_back_markup'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/reservation/new'>[back]</a><br><br>"),
			'#weight' => 0,
	);
// 	$form['mdc_image_markup'] = array(
// 			'#value' => t('<img src="' .base_path(). 'files/portal/mdc/' .$namafile. '" alt="' .$description. '"  height="200"><br><br>'),
// 			'#weight' => 1,
// 	);
	$form['mdc_produk_markup'] = array(
			'#value' => t("Product : <strong>" .$description. "</strong><br><br>"),
			'#weight' => 2,
	);
	$form['mdc_stok_markup'] = array(
			'#value' => t("Stock : <strong>" .$stok. " " .$satuan. "</strong><br><br>"),
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
	$form['notes'] = array(
			'#title' => t('Reason'),
			'#type' => 'textarea',
			'#weight' => 5,
			'#required' => TRUE,
	);
	$form['idStok'] = array(
			'#type' => 'hidden',
			'#default_value' => $idStok
	);
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Submit',
			'#weight' => 98,
	);
	$form['mdc_stock_markup'] = array(
			'#value' => t('<a href="' .base_path(). 'mdc/online/reservation/new"><input type="button" value="Cancel" /></a>'),
			'#weight' => 99,
	);
	return $form;
}

function mdc_reservation_detil_flush(){
	die(drupal_get_form('mdc_reservation_detil'));
}
function mdc_reservation_detil_submit($form, &$form_state) {
	$pesan 		= $form_state['pesan'];
	$notes 		= $form_state['notes'];
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
			$_SESSION['pesanbarang'][$idStok] 	= $pesan;
			$_SESSION['notes'][$idStok] 		= $notes;
		}
	}		
	drupal_goto('mdc/online/reservation/new');
}
function mdc_batalpesan() {
	$idStok 	= $_GET['idStok'];	
	drupal_set_message('Batal Pesan, Berhasil !');
	unset($_SESSION['pesanbarang'][$idStok]);
	drupal_goto('mdc/online/reservation/new');
}
function mdc_noReservasi() {
	$tglSkrng	= date('ym'); // ambil thn & bln sekarang
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo LIKE '%$tglSkrng%' ORDER BY id ASC");
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
function mdc_reservation_sabmit() { // saat pesanan di submit oleh user
	// $reservasiNo = uid_stokNo_qty_date => xxxxx_xxx_xxx_xxxxxx : uid login ; date => dmy_His
	// $reservasiNo = mdcR-date-rnd => mdcR-xxxxxx-xxxxxx : date => ymd ; rnd => 1-999999
	// 1. simpan reservation_detil : qty, item, gudang(plant)
	// 2. simpan reservation : reservasiNo, nopekMgrApproval, statMgrApproval=0, statScmApproval=0,idRequest,requestBy(nama),createTime(time now)
	// 3. if timeIssuerApproval => isClose=1
	$data			= $_SESSION['pesanbarang'];	
	$requestNo		= $_SESSION['reservation'];
	$user_data		= mdc_user_roles();
	$uid			= $user_data['uid'];
	$inputData		= $user_data['nama'];		
	
	$requestBy		= $inputData; // get_username_org($requestNo);
	$uidRequest		= mdc_usernametouid($requestBy);
	$noRev 			= mdc_noReservasi(); 				// <<= No terakhir dgn tahun & bln yg sama - Reservasi
	$reservasiNo 	= 'MDCC-'. date('ymd') .'-'. $noRev;
	// $kirim_pesan	= mdc_pesanemail(1,$reservasiNo,$requestBy); // <= reservasi pertama kali 				*** MATIKAN KIRIM EMAIL
	$createTime		= mktime(); // date('Y-m-d H:i:s');
	foreach ($data as $key => $value){ 					// $key => idStock : $value => qty pesan			
		$stokData 		= mdc_stock_data($key);
		$notes_isi		= $_SESSION['notes'][$key];
		$idMaterial		= $stokData['idMaterial'];
		$rsvQty			= $stokData['rsvQty'] + $value;	
		$idWarehouse	= $stokData['idWarehouse'];	
		$materialData	= mdc_material_data($idMaterial);
		$materialCode	= $materialData['materialCode'];
		$wareHouseData	= mdc_warehouse_data($idWarehouse);
		$idPlant		= $wareHouseData['idPlant'];
		
		if($value > 0){
			// add => mdc_reservation_detil
			db_set_active('pep');
			$hasil = db_query("INSERT INTO mdc_reservation_detil (reservasiNo,idStock,idPlant,materialCode,requestQty,acceptQty,notes,isActive) VALUES ('%s',%d,%d,'%s',%d,%d,'%s',%d)", $reservasiNo,$key,$idPlant,$materialCode,$value,$value,$notes_isi,1);
			if(!$hasil){
				drupal_set_message('SAVE Reservation Submit FAILED !', 'error');
			}	
			db_set_active();
			
			// update QTY Stok
			$stok_data 	= mdc_stock_data($key); // $key = idStock
			$jml_qty 	= $stok_data['qty'] - $value;
			
			db_set_active('pep');		
			$hasil_update = db_query("UPDATE mdc_stock SET qty = $jml_qty WHERE idStock = $key");
			db_set_active();
			
			$hist = mdc_hist_stok($key, 1, $stok_data['qty'], $value,$notes_isi); // $act, $stok_awal, $nilai
			// END add => mdc_reservation_detil	
		}else{
			unset($_SESSION['pesanbarang']);
			unset($_SESSION['notes']);
			unset($_SESSION['reservation']);
			drupal_set_message('SAVE Reservation Submit FAILED !', 'error');
			drupal_goto('mdc/online/reservation/new');			
		}
	}
	// add => mdc_reservation =====>> cukup sekali record, letakkan diluar
	db_set_active('pep');
	$hasil = db_query("INSERT INTO mdc_reservation (reservasiNo,statusApproval,statMgrApproval,statScmApproval,idRequest,requestBy,createTime,input)
				VALUES ('%s',%d,%d,%d,%d,'%s','%s','%s')", $reservasiNo,1,0,0,$uidRequest,$requestBy,$createTime,$inputData);
	if($hasil){
		drupal_set_message('Submit Reservation Success');
		unset($_SESSION['pesanbarang']);
		unset($_SESSION['notes']);
		unset($_SESSION['reservation']);
	}else{
		drupal_set_message('SAVE Reservation Submit FAILED !', 'error');
	}
	db_set_active();
	// END add => mdc_reservation
	drupal_goto('mdc/online/reservation');
}
// END RESERVATION =================================================================================
function mdc_hist_stok($idStok, $act, $stok_awal, $nilai, $keterangan=''){
	$timeStamp	= mktime();
	$name		= $GLOBALS['user']->name;
	
	if($act == 3){ // 1 consumption; 2 transfer; 3 tambah data
		$nil		= $stok_awal + $nilai;
	}else{
		$nil		= $stok_awal - $nilai;
	}
	
	db_set_active('pep');
	$result = db_fetch_array(db_query("select * from mdc_stock where idStock=$idStok"));
	$result_po = $result['po'];
	$result_gr = $result['gr'];
	$result_thn_gr = $result['tahun_gr'];
	/*$hasil = db_query("INSERT INTO mdc_hist 
		(user,waktu,act,idStok,stok_awal,stok_akhir,po,gr,tahun_gr,nilai,keterangan) VALUES ('%s',%d,%d,%d,%d,%d,%s,%s,%d,%d,'%s')"
				, $name,$timeStamp,$act,$idStok,$stok_awal,$nil,$result['po'],$result['gr'],$result['tahun_gr'],$nilai,$keterangan);
	*/	
	$hasil = db_query("INSERT INTO mdc_hist 
		(user,waktu,act,idStok,stok_awal,stok_akhir,po,gr,tahun_gr,nilai,keterangan) 
		VALUES ('$name',$timeStamp,$act,$idStok,$stok_awal,$nil,'$result_po','$result_gr',$result_thn_gr,$nilai,'$keterangan')");
	
	db_set_active();
	return $hasil;
}