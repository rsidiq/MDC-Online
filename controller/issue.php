<?php 
// ISSUE ============================================================================
function mdc_issue() {
// 	$tampilkan 	= mdc_stock_kritis();
	$status = array(0=>'All',1=>'New',3=>'Issued',7=>'Closed');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['status'] = array(
		'#title' => t('Status'),
		'#type' => 'select',	
		'#options' => $status,
		'#default_value' => variable_get('status', 3),
		'#weight' => 2,
		'#required' => TRUE, 	
	);	
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Show',
			'#weight' => 99,
	);
	return $form;
}
function mdc_issue_submit($form, &$form_state) {
	$filter 	= $form_state['filter'];
	$status		= $form_state['status'];

	drupal_goto('mdc/online/issue/view', 'filter=' .$filter. '&status=' .$status);
}
function mdc_issue_view() {
	$filter 	= $_GET['filter'];
	$status		= $_GET['status'];
	$status_pil = array(0=>'All',1=>'Waiting for Manager approval',2=>'Approved by Manager, waiting for SCM Approval',3=>'Approved by Atasan, ready goods Taken',4=>'Good Issue',5=>'Rejected by Manager',6=>'Rejected by SCM',7=>'Goods Taken');
	$cek_login	= mdc_is_atasan(); // <<<==== dsini OK, dptkan atasan yg sdg login
	$periode_awal 	= mdc_konversi_tgl($_GET['periode_awal']);
	$periode_akhir 	= mdc_konversi_tgl($_GET['periode_akhir']);
	$status		 	= $_GET['status'];
	$hasil 			= "<a href='online'>[back]</a><br><br>";
	$liststock 		= "<a href='" .base_path(). "mdc/online/liststock/view'>List Stock</a> | ";
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('Date Created'),),
			array('data'=>t('No. Reservasi'),),
			array('data'=>t('Plant'),),
			array('data'=>t('Status'),),
			array('data'=>t('Request By'),),
			array('data'=>t('Info to User'),),
			array('data'=>t('Action'),),
	);
	$sa			= $GLOBALS['user']->roles[182];
	$gudang		= $GLOBALS['user']->roles[180];
	$admin		= $GLOBALS['user']->roles[178];
	
	db_set_active('pep');
	if($status > 0){
		$statusv = 'statusApproval = '.$status;
	}else{
		$statusv = 'statusApproval = 3 || statusApproval = 4 || statusApproval = 7'; // <<= jika tampilkan semua [0]
	}
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE $statusv ORDER BY statusApproval ASC");
	while($row = db_fetch_array($db_data)) {
		$data_detil	= mdc_reservation_data_select($row['reservasiNo']);
		$idPlant	= $data_detil['idPlant'];
		$plant_name	= mdc_plant_data($idPlant);
		$fullname	= cek_fullname($row['requestBy']);
		
		switch ($row['statusApproval']) { // 2:manager ; 3: SCM ; 4:Good Issue
			case 3:
				$approved		= "<a href='" .base_path(). "mdc/online/issue/close/?id=" .$row['reservasiNo']. "&key=7'>Issue</a>";
				break;
			case 4:
				$approved 		= "Ready goods Taken"; // Barang Siap Diambil
				break;
			case 7:
				$approved 		= "Goods Taken"; // Closed
				break;
			case 1:
				$approved 		= "Waiting for Manager approval";
				break;
		}
		if($status==3 || $status==4 || $status==7 || $status==1 || $status==0){
// 			$view_image	= "<a class='fancybox-effects-d' href='" .base_path(). "files/portal/mdc/" .$row['fileName']. "' title='" .$row['description']. "'>view</a>";
			$reservasi	= "<a href='" .base_path(). "mdc/online/issue/view/data/?id=" .$row['reservasiNo']. "&stat=" .$status. "'>" .$row['reservasiNo']. "</a>";
			if($row['statusApproval']==3){
				$info	= '<a href="' .base_path(). 'mdc/online/reservation/info/' .$row['reservasiNo']. '/3"><input type="button" value="Send" /></a>';
			}else{
				$info	= '-';
			}
// 			if(!$sa){							// jika bukan SA
				$cek_plant = cek_data_plant_mdc($row['reservasiNo']);
// 				if($cek_plant == $row['reservasiNo']){	// cek lokasi sama dengan user login jika buka SA
// 					if($admin || $gudang){
// 						$isi[] = array(++$xyz, mdc_konversi_tgl($row['createTime'],1), $reservasi, $plant_name['description'], $status_pil[$row['statusApproval']], $fullname, $info, $approved);
// 					}
// 				}
// 			}else{ // jika SA
			if($sa || ($admin && ($cek_plant == $row['reservasiNo']))){
				$isi[] = array(++$xyz, mdc_konversi_tgl($row['createTime'],1), $reservasi, $plant_name['description'], $status_pil[$row['statusApproval']], $fullname, $info, $approved);
			}
		}
	}
	db_set_active();
	$output = theme_table($judul, $isi);
	return $liststock.$hasil.$output;
}
function mdc_cari_scm($ceklok){
	db_set_active('pep');
	$db_data 	= db_query("SELECT * FROM mdc_user WHERE lokasi = $ceklok && akses2 = 171");
	while($row 	= db_fetch_array($db_data)) {
		$user_name	= $row['username'];
	}
	db_set_active();
	
	return $user_name;
}
function mdc_user_reservasi_toemail($reservasiNo) {
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE reservasiNo = '$reservasiNo'");
	while($row = db_fetch_array($db_data)) {
		$un[]	= $row['requestBy'];
		$un[]	= $row['input'];
		$ceklok	= mdc_lokasi_user($row['requestBy']); // hasil kode lokasi user
		$un[]	= mdc_cari_scm($ceklok); // hasil un scm dari kode lokasi
	}
	db_set_active();

	foreach ($un as $us){
		$hasil[]		= mdc_email_user($us);
	}
	array_push($hasil, "pep-webdev01.mdgti@pep.pertamina.com"); // , "christian.arlin@pep.pertamina.com"
	$hasil	= implode($hasil, ',');
	return $hasil;
}
function mdc_info_ambil_barang(){
	$reservasiNo 	= arg(4);
	$statusApproval = arg(5);
	
	// ===========================================================
	$data_detil = mdc_reservation_data_select($reservasiNo); // data detil
	$lokasi_id	= $data_detil['idPlant'];
	
	$toemail	= mdc_user_reservasi_toemail($reservasiNo); // dapetin email dari no.reservasi -> requestBy -> username -> email
	$subjek 	= 'mdc Online - Barang siap diambil';
	
	$from 		= "pep-noreply@pep.pertamina.com";
	$isiemail	= "Info mdc Online<br><br>";
	
	$status = "Barang siap diambil";
	
	// ========== LOOP DATA ====================================
	$data	=	data_detil($reservasiNo);
	foreach ($data as $idStock => $value){
		$cek_stok		= mdc_stock_data($idStock);
		$cek_material 	= $cek_stok['idMaterial'];
		$material_id	= mdc_material_data($cek_material);
		$material 		= $material_id['description'];
			
		$status .= "<br><br>";
		$status .= "Item : " .$material. "<br>";
		$status .= "Request Qty : " .$value['request']. "<br>";
		$status .= "Accept Qty : " .$value['accept'];
	}
	// ========== END LOOP DATA ====================================
	
	$dataRes	= mdc_reservation_data_header($reservasiNo);
	if(!isset($dataRes)){
		$dataRes['createTime']= mktime();
	}
	$awl		= date("Y-m-d", $dataRes['createTime']);
	$akh 		= date('Y-m-d', strtotime($awl . ' +1 day'));
// 	$links		= 'http://portal.pertamina-ep.com/mdc/online/reservation/view?periode_awal=' .$awl. '&periode_akhir=' .$akh. '&status=3';
	if($statusApproval == 3){ // mdc/online/issue/view/data/?id=mdcR-160222-000002&stat=3
		$links		= 'http://portal.pertamina-ep.com/mdc/online/issue/view/data/?id=' .$reservasiNo. '&stat=3';
	}else{
		$links		= 'http://portal.pertamina-ep.com/mdc/online/reservation/view?periode_awal=' .$awl. '&periode_akhir=' .$akh. '&status=' .$stt;
	}
	
	$isiemail	.= "No. Reservasi : <a href='" .$links. "'>" .$reservasiNo. "</a><br><br>";
	$isiemail	.= "Status : " .$status. "<br><br><br>";
	$isiemail	.= "***<br>Thanks regards,<br>";
	$isiemail	.= "mdc Online Team<br>";
	
	$headers	= array(
			'MIME-Version' => '1.0',
			'Content-Type' => 'text/html',
			'Content-Transfer-Encoding' => '8Bit',
			'X-Mailer' => 'Drupal',
	);
	
	$kirim = drupal_mail('mdc', $toemail, $subjek, $isiemail, $from, $headers);	
	// log
	$page = 'send notifikasi - Barang Siap Diambil';
	$ket['toEmail'] = $toemail ; $ket['Status'] = $status;
	$hasil = mdc_logs($page,$ket);
	
	// info sukses : info ke user sukses terkirim, anda dpt klik button send utk notifikasi kembali kepada user
	drupal_set_message('Notifikasi Email ke User, Success');
	
	// ===========================================================
	
	drupal_goto('mdc/online/issue/view');
}
function mdc_issue_view_data(){
	$reservasiNo	= $_GET['id'];
	$status			= $_GET['stat'];
	$hasil = "<a href='" .base_path(). "mdc/online/issue/view?status=" .$status. "'><< back </a><br><br>";
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('No. Reservasi'),),
			array('data'=>t('Item'),),
			array('data'=>t('Plant'),),
			array('data'=>t('Qty'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo' && isActive = 1 ORDER BY id ASC");
	while($row = db_fetch_array($db_data)) {
		$cek_stok		= mdc_stock_data($row['idStock']);
		$cek_material 	= $cek_stok['idMaterial'];
		$material_id	= mdc_material_data($cek_material);
		$material 		= $material_id['description'];
		$isi[] = array(++$xyz,$row['reservasiNo'],$material,cek_lokasi_mdc($row['idPlant']),$row['acceptQty']);
	}
	db_set_active();
	$output = theme_table($judul, $isi);
	return $hasil.$output;
}
function mdc_issue_close(){
	$reservasiNo	= $_GET['id'];
	$statusApproval	= $_GET['key'];
	
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_back_markup'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/issue/view'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	$form['nama'] = array(
			'#type' => 'textfield',
			'#title' => t('Nama'),
			'#size' => 30,
			'#maxlength' => 100,
			'#weight' => 1,
			'#required' => TRUE,
	);
	$form['keterangan'] = array(
			'#type' => 'textarea',
			'#title' => t('Keterangan'),
			'#weight' => 2,
			'#required' => TRUE,
	);
	$form['reservasiNo'] = array(
			'#type' => 'hidden',
			'#default_value' => $reservasiNo
	);
	$form['statusApproval'] = array(
			'#type' => 'hidden',
			'#default_value' => $statusApproval
	);
	$form['submit'] = array (
			'#type' => 'submit',
			'#value' => t('Submit'),
			'#weight' => 3,
	);
	$view_detil_data = mdc_issue_view_data_detil();
	$form['mdc_back_markup2'] = array(
			'#value' => t($view_detil_data),
			'#weight' => 0,
	);
	return $form;
}
function mdc_issue_view_data_detil(){
	$reservasiNo	= $_GET['id'];
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('No. Reservasi'),),
			array('data'=>t('Item'),),
			array('data'=>t('Plant'),),
			array('data'=>t('Qty'),)
	);
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo' && isActive = 1 ORDER BY id ASC");
	while($row = db_fetch_array($db_data)) {
		$cek_stok		= mdc_stock_data($row['idStock']);
		$cek_material 	= $cek_stok['idMaterial'];
		$material_id	= mdc_material_data($cek_material);
		$material 		= $material_id['description'];
		$isi[] = array(++$xyz,$row['reservasiNo'],$material,cek_lokasi_mdc($row['idPlant']),$row['acceptQty']);
	}
	db_set_active();
	$output = theme_table($judul, $isi);
	return $output;
}
function mdc_issue_close_submit($form, &$form_state) {
	$nama 			= $form_state['nama'];
	$keterangan 	= $form_state['keterangan'];
	$reservasiNo	= $form_state['reservasiNo'];
	$statusApproval	= $form_state['statusApproval'];
	$waktu			= mktime();	
	$issuer			= $GLOBALS['user']->name;
	db_set_active('pep');	
	$hasil_update = db_query("UPDATE mdc_reservation SET 
			statusApproval 	= $statusApproval,
			issuer			= '$issuer',
			nameClose		= '$nama',
			noteClose		= '$keterangan',
			timeClose		= $waktu			
		WHERE reservasiNo 	= '$reservasiNo'");
	if($hasil_update){
		drupal_set_message('Approval Success ...');
	}
	db_set_active();	
	if($pesan>$stok){
		drupal_set_message('Submit Item Success');
	}		
	
	// barang diambil
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$reservasiNo' && isActive = 1");
	while($row = db_fetch_array($db_data)) {
		$dataStok	= mdc_stock_data($row['idStock']);
		$sisa		= $dataStok['qty'] - $row['acceptQty']; // dikurang acceptQty (qty yg disetujui SCM ; requestQty => nilai yg diminta)
		$hasil 		= mdc_update_stok($row['idStock'],$sisa,1); // 1:stok utama dikuranga (qty)
	}
	db_set_active();
	// END barang diambil
	
	// kirim email ke user reservasi
		$statusApproval = 9;
		$kirim_email 	= mdc_pesanemail($statusApproval,$reservasiNo); // $statusApproval => 9 -> barang telah diambil oleh, tgl, item
	// END kirim email ke user reservasi
	
	drupal_goto('mdc/online/issue/view');
}
// END ISSUE ============================================================================