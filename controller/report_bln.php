<?php 
function mdc_report_form(){
	$periode_awal 	= date("Y-m-d",mktime(0,0,0,date('m'),date('d')-7,date('Y')));
	$periode_akhir 	= date("Y-m-d",mktime(0,0,0,date('m'),date('d')+1,date('Y')));
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_report_reservation_back'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/report'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	
	$form['reservasi'] = array(
			'#type' => 'fieldset',
			'#title' => t('Reservation Periode'),
			'#weight' => 1,
			'#attributes' => array('class' => 'reservasi'),
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
	);
	$form['reservasi']['awal_reservasi'] = array(
			'#type' => 'textfield',
			'#title' => t('Tanggal Awal Reservasi'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode_awal,
			'#required' => TRUE,
			'#weight' => 2
	);
	$form['reservasi']['akhir_reservasi'] = array(
			'#type' => 'textfield',
			'#title' => t('Tanggal Akhir Reservasi'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode_akhir,
			'#required' => TRUE,
			'#weight' => 3
	);
	$form['submit'] = array(
				'#type' => 'submit',
				'#value' => 'View',
				'#weight' => 98,
	);
	return $form;
}
function mdc_report_form_submit($form, &$form_state) {
	$x		= $form_state['awal_reservasi'];
	$y		= $form_state['akhir_reservasi'];
	$tgl1	= substr($x,8,2); $bln1	= substr($x,5,2); $thn1	= substr($x,0,4);
	$tgl2	= substr($y,8,2); $bln2	= substr($y,5,2); $thn2	= substr($y,0,4);
	$awal_reservasi		= mktime(0,0,0, $bln1, $tgl1, $thn1);
	$akhir_reservasi	= mktime(0,0,0, $bln2, $tgl2, $thn2);
	drupal_goto('mdc/online/report/bulanan/' . $awal_reservasi. '/' . $akhir_reservasi);	
}
function mdc_report_bulan() {				// ============================= 1
	if(arg(4) && arg(5)){	
		$awal 	= arg(4);
		$akhir 	= arg(5);
	}else{
		drupal_set_message('Please select Start/End Date !', 'error');
		drupal_goto('mdc/online/report/form');
	}
	
	$data = mdc_report_bulan_data($awal,$akhir);
	$back = "<a href='" .base_path(). "mdc/online/report/form'>[back]</a> | <a href='" .base_path(). "mdc/online/report/bulanan/toexcel/" .$awal. "/" .$akhir. "'>[to Excel]</a><br><br>";
	$output = theme('table', $data['judul'], $data['isi']);
	$output .= theme('pager', NULL, 10);
	return $back.$output;
}

function mdc_report_bulan_data($awal,$akhir) {			// tampilan final DI SINI <<=	// ============================= 2
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Kategori'),),
			array('data'=>t('Jml. Barang'),),
			array('data'=>t('Tot. Harga'),)
	);

	$dataTot	= mdc_report_bulan_sort($awal,$akhir);
	foreach ($dataTot as $fungsi => $data){
		$jmlAllHrg = 0; $jmlAllBrg = 0;
		$isi[] 	= array(++$xyz . '.', $fungsi);
		foreach ($data as $cat => $data2){
			$isi[] 	= array('','',mdc_category_name($cat),$data2['totBrg'],$data2['cur'] .' '. number_format($data2['totHrg']));
			$jmlAllHrg	+= $data2['totHrg'];
			$jmlAllBrg	+= $data2['totBrg'];
		}
		$isi[] 	= array('','','Total',$jmlAllBrg,$data2['cur'] .' '. number_format($jmlAllHrg));
	}

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}

function mdc_report_bulan_sort($awal,$akhir) {			// kelompokkan item sesuai fungsi yg sama, dan jumlahkan tot.harga	// ============================= 3
	$dataDetil	= mdc_report_main($awal,$akhir);
	foreach ($dataDetil as $idRevDtl => $dataIsi){
		$fungsi = $dataIsi['fungsi'];
		$cat 	= $dataIsi['cat'];
		$prc 	= $dataIsi['prc'];
		$accept = $dataIsi['accept'];
		$totHrg	= $accept * $prc; // diterima * harga
		//===========================
		$request 	= $dataIsi['request'];
		$cur 		= $dataIsi['cur'];
		$hslAkh[$fungsi][$cat]['totHrg']	+= $totHrg;
		$hslAkh[$fungsi][$cat]['totBrg']	+= $accept;
		$hslAkh[$fungsi][$cat]['cur']		= $cur;
	}

	return $hslAkh;
}

function mdc_report_main($awal,$akhir){					// ============================= 4
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation WHERE nopekScmApproval != '' && createTime >= $awal && createTime <= $akhir ORDER BY createTime DESC"); // tambahkan filter, nopekScmApproval != '' (#3)
	while($row = db_fetch_array($db_data)) {
// 		$waktu 		= date("d-m-Y H:i:s", $row['createTime']);
		$fungsi			= get_fungsi_user($row['requestBy']); // tambahkan filter disini, jika fungsi '' kosong, tdk perlu diambil datanya. (#1)
		if($fungsi){
			$resNo					= $row['reservasiNo'];
			$hasil[$resNo]['user'] 	= $row['requestBy'];
			$hasil[$resNo]['fungsi']= $fungsi['fungsi'];
			
			$data						= get_data_reservasi_mdc($row['reservasiNo']);	
			foreach ($data as $idRevDtl => $dataIsi){
				foreach ($dataIsi as $ket => $dataDetil){ // string 'mdcR-160114-000003:accept:35' (length=28)
					$hasil[$resNo][$ket]	= $dataDetil;
				}
			}
		}
	}
	db_set_active();
	
	return $hasil;
}

function get_data_reservasi_mdc($resNo){		// requestQty	acceptQty // ============================= 5
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_reservation_detil WHERE reservasiNo = '$resNo'");
	while($row = db_fetch_array($db_data)) {		
		$hasil[$row['id']]['accept']	= $row['acceptQty'];
		$hasil[$row['id']]['request']	= $row['requestQty'];
		
		$data_res		= get_cat_matcod_mdc($row['materialCode']); // tambahkan filter, jika category '' kosong, data selanjutnya tdk perlu disimpan. (#2)
		$hasil[$row['id']]['cat']	= $data_res['cat'];
		$hasil[$row['id']]['prc']	= $data_res['prc'];
		$hasil[$row['id']]['cur']	= $data_res['cur'];
	}
	db_set_active();

	return $hasil;
}

function get_cat_matcod_mdc($matcode) {			// ============================= 6
	db_set_active('pep');
	$db_data = db_query("SELECT * FROM mdc_material WHERE materialCode = '$matcode'");
	while($row = db_fetch_array($db_data)) {
		$hasil['cat'] 	= $row['category'];
		$hasil['prc'] 	= $row['price'];
		$hasil['cur'] 	= $row['currency'];
	}
	db_set_active();
	return $hasil;
}

function mdc_report_bulan_toexcel(){		// ============================= 7
	if(arg(5) && arg(6)){
		$awal 	= arg(5);
		$akhir 	= arg(6);
	}else{
		drupal_set_message('Please select Start/End Date !', 'error');
		drupal_goto('mdc/online/report/form');
	}
	$data 		= mdc_report_bulan_data($awal,$akhir);
	$judul		= $data['judul'];
	$isi		= $data['isi'];

	$workbook = new Spreadsheet_Excel_Writer();
	$workbook->send('report_bulanan.xls');
	$worksheet =& $workbook->addWorksheet('report_bulanan');
	$worksheet->freezePanes(array(1, 0));
	$format =& $workbook->addFormat(array('Size' => 10,
			'Align' => 'center',
			'Bold' => 1,
			'Color' => 'white',
			'Pattern' => 1,
			'BgColor' => 'white',
			'FgColor' => 'grey'));
	$worksheet->setColumn(0,0,5);
	$worksheet->setColumn(1,1,30);
	$worksheet->setColumn(2,2,30);
	$worksheet->setColumn(3,3,10);
	$worksheet->setColumn(4,4,20);

	foreach ($judul as $key => $value){
		foreach ($value as $key1 => $hasil){
			$worksheet->write(0, $key, $hasil, $format);
		}
	}
	$r = 1;
	foreach ($isi as $key => $value){
		foreach ($value as $key1 => $hasil){
			$worksheet->write($r, $key1, $hasil);
		}
		$r++;
	}
	$workbook->close();
}

function mdc_report_historicalcard($material='',$wh='',$awal='',$akhir=''){
	if($awal && $akhir){
		$periode_awal 	= date("Y-m-d",$awal);
		$periode_akhir 	= date("Y-m-d",$akhir);
	}else{
		$periode_awal 	= date("Y-m-d",mktime(0,0,0,date('m'),date('d')-7,date('Y')));
		$periode_akhir 	= date("Y-m-d",mktime(0,0,0,date('m'),date('d')+1,date('Y')));
	}
	$user_lokasi		= mdc_cek_user_lokasi(); // hasil plant (int)
	$data_roles			= mdc_user_roles();
	$user_roles			= $data_roles['admin'];
	if(arg(4)){
		$mat = mdc_material_data($material); $matrial = $mat['description']; $materialname = $material .'-'. $matrial;
	}
	
	//if($user_roles){ // jika user ADMIN
	//	$wh_option			= mdc_warehouse_same_plant($user_lokasi);
	//}else{
		$wh_option			= mdc_warehouse_data_select();
	//}
	
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_stockcard_back'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/report'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	
	$form['mdc_stockcard_back_title'] = array(
			'#value' => t("<h2>Historical Card</h2>"),
			'#weight' => 1,
	);
	$form['mdc_historicalcard'] = array(
			'#type' => 'fieldset',
			'#weight' => 2,
			'#attributes' => array('class' => 'reservasi'),
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
	);
	
	/*$form['mdc_historicalcard']['no_material'] = array(
			'#type' => 'select',
			'#options' => mdc_material_data_select(),
			'#title' => t('No. Material'),
			'#required' => TRUE,
			'#weight' => 3
	);*/
	
	$form['mdc_historicalcard']['no_material_ac'] = array(
			'#type' => 'textfield',
			'#title' => t('Material'),
			'#required' => TRUE,
			'#weight' => 3,
			'#autocomplete_path' => 'mdc/autocomplete/listmaterial',
			'#default_value'=>$materialname
	);
	
	$form['mdc_historicalcard']['warehouse'] = array(
			'#type' => 'select',
			'#options' => $wh_option,
			'#title' => t('Warehouse'),
			'#required' => TRUE,
			'#weight' => 4,
			'#default_value'=>$wh
	);
	
	$form['mdc_historicalcard']['awal'] = array(
			'#type' => 'textfield',
			'#title' => t('Awal'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode_awal,
			'#required' => TRUE,
			'#weight' => 5
	);
	$form['mdc_historicalcard']['akhir'] = array(
			'#type' => 'textfield',
			'#title' => t('Akhir'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode_akhir,
			'#required' => TRUE,
			'#weight' => 6
	);
	$form['submit1'] = array(
			'#type' => 'submit',
			'#value' => 'Tampilkan',
			'#weight' => 97,
	);
	
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Download Excel',
			'#weight' => 98,
	);
	
	if($material!='' && $wh!=''){
		$judul = array(
				array('data'=>t('Tanggal'),),
				array('data'=>t('Satuan'),), 
				array('data'=>t('Terima'),), // mdc_reservation
				array('data'=>t('Keluar'),), // mdc_reservation_detil
				array('data'=>t('Sisa'),),
				array('data'=>t('No Kontrak/OAS/PO'),),
				array('data'=>t('Keterangan'),),
			);
		
		db_set_active('pep');
		$db_data = db_query("SELECT stock.*, history.*, material.satuan, material.description, material.materialCode FROM mdc_stock stock
						LEFT JOIN mdc_hist history on stock.idStock=history.idStok
						LEFT JOIN mdc_material material ON stock.idMaterial = material.id
						WHERE stock.idMaterial = ".$material." AND stock.idWarehouse =".$wh
						." AND waktu>=$awal and waktu<=$akhir "
						." order by history.waktu asc");
							
		while($result = db_fetch_array($db_data)){
			$tanggal = date("d-m-Y",strval($result['waktu']));
			if($result['stok_awal'] > $result['stok_akhir']){
				$terima = 0;
				$keluar = $result['nilai'];
			}else if($result['stok_awal'] < $result['stok_akhir']){
				$terima = $result['nilai'];
				$keluar =0;
			}
			$isi[] 	= array($tanggal,$result['satuan'],$terima,$keluar,$result['stok_akhir'],$result['po'],$result['keterangan']);
		}
		$form['table'] = array(
			'#value'=>"<br/>"."<br/>".theme_table($judul,$isi),
			'#weight'=>99,
		);
	}
	
	return $form;
}

function mdc_report_historicalcard_submit($form, &$form_state) {
	$awal = strtotime($form_state['awal']);
	$akhir = strtotime($form_state['akhir']);
	$no_mat	= explode("-",$form_state['no_material_ac']); $no_materi = $no_mat[0];
	if($form_state['op']==$form_state['submit']){
		$objReader 		= PHPExcel_IOFactory::createReader('Excel2007');
		$objPHPExcel 	= $objReader->load(drupal_get_path('module', 'mdc')."/template_report/historicalcard.xlsx");
		
		db_set_active('pep');
		$db_data = db_query("SELECT stock.*, history.*, material.satuan, material.description, material.materialCode FROM mdc_stock stock
						LEFT JOIN mdc_hist history on stock.idStock=history.idStok
						LEFT JOIN mdc_material material ON stock.idMaterial = material.id
						WHERE stock.idMaterial = ".$no_materi." AND stock.idWarehouse =".$form_state['warehouse']
						." AND waktu>=$awal and waktu<=$akhir "
						." order by history.waktu asc");
				
		$nomor =1;
		$row=9;
		while($result = db_fetch_array($db_data)) {
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, 5, $result['materialCode']." - ".$result['description']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $row, date("d-m-Y",strval($result['waktu'])));
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $row, $result['satuan']);
			if($result['stok_awal'] > $result['stok_akhir']){
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $row, "0");
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $row, $result['nilai']);
			}else if($result['stok_awal'] < $result['stok_akhir']){
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $row, $result['nilai']);
				$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $row, "0");
			}
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $row, $result['stok_akhir']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $row, $result['po']);
			$objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $row, $result['keterangan']);
			$row++;
			$nomor++;
		}
		db_set_active();

		header('Content-Type: application/vnd.openXMLformats-officedocument.spreadsheetml.sheet'); 
		header('Content-Disposition: attachment;filename="historicalcard_report.xlsx"'); 
		header('Cache-Control: max-age=0'); 
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		$objWriter->save("php://output");
	}else{
		drupal_goto("mdc/online/report/historicalcard/".$no_materi."/".$form_state['warehouse']."/".$awal."/".$akhir);
	}
}

function mdc_report_stockcard($fungsi='',$awal='',$akhir='',$plant=''){
	if($awal && $akhir){
		$periode_awal 	= date("Y-m-d",$awal);
		$periode_akhir 	= date("Y-m-d",$akhir);
	}else{
		$periode_awal 	= date("Y-m-d",mktime(0,0,0,date('m'),date('d')-7,date('Y')));
		$periode_akhir 	= date("Y-m-d",mktime(0,0,0,date('m'),date('d')+1,date('Y')));
	}
	$user_lokasi		= mdc_cek_user_lokasi(); // hasil plant (int)
	$data_roles			= mdc_user_roles();
	$user_roles			= $data_roles['admin'];
	
	if($user_roles){ // jika user ADMIN
		$wh_option			= mdc_warehouse_same_plant($user_lokasi);
	}else{
		$wh_option			= mdc_warehouse_data_select();
	}
	
	$form['#attributes'] = array('enctype' => 'multipart/form-data');
	$form['mdc_stockcard_back'] = array(
			'#value' => t("<a href='" .base_path(). "mdc/online/report'>[back]</a><br><br>"),
			'#weight' => 0,
	);
	
	$form['mdc_stockcard_back_title'] = array(
			'#value' => t("<h2>Stock Card</h2>"),
			'#weight' => 1,
	);
	$form['mdc_stockcard'] = array(
			'#type' => 'fieldset',
			'#weight' => 2,
			'#attributes' => array('class' => 'reservasi'),
			'#collapsible' => TRUE,
			'#collapsed' => FALSE,
	);
	
	$form['mdc_stockcard']['awal'] = array(
			'#type' => 'textfield',
			'#title' => t('Awal'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode_awal,
			'#required' => TRUE,
			'#weight' => 2
	);
	$form['mdc_stockcard']['akhir'] = array(
			'#type' => 'textfield',
			'#title' => t('Akhir'),
			'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
			'#jscalendar_ifFormat' => '%Y-%m-%d',
			'#jscalendar_showsTime' => 'false',
			'#default_value' => $periode_akhir,
			'#required' => TRUE,
			'#weight' => 3
	);	
	$form['mdc_stockcard']['plant'] = array(
			'#title' => t('Plant'),
			'#type' => 'select',
			'#options' => mdc_plant_data_select(),
			'#default_value' =>  variable_get('idPlant', $plant),
			'#weight' => 4,
			'#attributes' => array('onchange' => 'this.form.submit();'),
			'#required' => TRUE,
	);
	if($xxx){
		$form['mdc_stockcard']['fungsi'] = array(
				'#title' => t('Fungsi'),
				'#type' => 'select',
				'#options' => mdc_fungsi_data_select(),
				'#default_value' =>  variable_get('idFungsi', $xxx),
				'#weight' => 5,
				'#required' => TRUE,
		);
	}
	$form['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Download Excel',
			'#weight' => 98,
	);
	
	$form['submit1'] = array(
			'#type' => 'submit',
			'#value' => 'Tampilkan',
			'#weight' => 97,
	);
	
	if($fungsi!=''){
		$data 		= mdc_data_stockcard($fungsi,$awal,$akhir,$plant);
		$judul		= $data['judul'];
		$isi		= $data['isi'];
		
		$form['table'] = array(
			'#value'=>"<br/>"."<br/>".theme_table($judul,$isi),
			'#weight'=>99,
		);
	}
	return $form;
}

function mdc_data_stockcard($fungsi,$awal,$akhir,$plant,$ket=''){
	$judul = array(
			array('data'=>t('No.'),),
			array('data'=>t('KIMAP'),),
			array('data'=>t('Warehouse'),),
			array('data'=>t('CostCenter'),),
			array('data'=>t('Deskripsi/Spesifikasi Material'),),
			array('data'=>t('Qty'),),
			array('data'=>t('No Kontrak/OAS/PO'),),
			array('data'=>t('Dedicated/Berlebih'),),
			array('data'=>t('Tujuan Penggunaan'),),
			array('data'=>t('Waktu Penggunaan'),),
			array('data'=>t('Keterangan'),),
			array('data'=>t('Status'),),
	);
	
	db_set_active('pep');
	$db_data = db_query("SELECT stock.*,hist.*,material.description mat_desc, material.satuan mat_satuan ,material.materialCode, 
				fungsi.description fung_desc, fungsi.cost fung_cost, plant.description plant_desc
			FROM `mdc_stock` stock
			join mdc_hist hist on stock.idStock=hist.idStok 
			join mdc_material material on stock.idMaterial=material.id
			join mdc_fungsi fungsi on stock.idFungsi=fungsi.idFungsi
			join mdc_warehouse wh on stock.idWarehouse=wh.idWarehouse
			join mdc_plant plant on wh.idPlant=plant.idPlant
			where stock.idFungsi=$fungsi and wh.idPlant = $plant and waktu<=$akhir and stock.qty>0 order by waktu desc");
	
	$nomor = 1;
	$stock_set = array();
	$hasil = array();
	while($result = db_fetch_array($db_data)) {
		$hasil['fung_desc']=$result['fung_desc'];
		$hasil['plant_desc']=$result['plant_desc'];
		$hasil['awal']=date("d/m/Y",$awal);
		$hasil['akhir']=date("d/m/Y",$akhir);
		if(!in_array($result['idStok'],$stock_set)){
			$idstk = $result['idStok'];
			$idWh1 = mdc_stock_data($idstk);
			$idWh2 = $idWh1['idWarehouse'];
			$idWh  = mdc_warehouse_data($idWh2);
			$idPl1 = $idWh['idPlant'];
			$wh    = $idWh['description'];
			$Pl1   = mdc_plant_data($idPl1);
			$Pl    = $Pl1['description'];			
			
			$stock_set[] = $result['idStok'];
			if($result['tanggal_penggunaan'] == 0){
				$tgl_penggunaan = "-";
			}else{
				$tgl_penggunaan = date("d-m-Y",$result['tanggal_penggunaan']);
			}
			
			// timestamp 3 bulan
			if($result['tanggal_penggunaan'] > 0){
				$tgl_ref = $result['tanggal_penggunaan'];
			}else{
				$tgl_ref = $result['tanggal_gr'];
			}
		
			$next_3_month = strtotime("+3 month",$tgl_ref); // 3*30*24*60*60 => old			
			if($next_3_month >= mktime()){
				$status = "Dedicated";
			}else{
				$status = "Berlebih";
			}
			
			if($result['tanggal_gr'] > 0){
				$tgl_ref = $result['tanggal_gr'];
			}else{
				$tgl_ref = $result['tanggal_penggunaan'];
			}
			// timestamp 3 bulan
			$bef_3_month =  strtotime("+3 month",$tgl_ref); // 3*30*24*60*60 => old
			if($bef_3_month >= mktime()){				
				if($ket=='excel'){
					$status_flag = 'GREEN';
				}else{
					$status_flag = "<img src='".base_path().drupal_get_path('module','mdc')."/img/green.png'/>";
				}
			}else{				
				if($ket=='excel'){
					$status_flag = 'RED';
				}else{
					$status_flag = "<img src='".base_path().drupal_get_path('module','mdc')."/img/red.png'/>";
				}
			}
			$isi[] 	= array($nomor,$result['materialCode'],$wh,$result['fung_cost'],$result['mat_desc'],$result['stok_akhir'] .' '. $result['mat_satuan'],$result['po']
					,$status,$result['tujuan_penggunaan'],$tgl_penggunaan,'',$status_flag);
			$nomor++;
		}
	}
	
	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	return $hasil;
}

function mdc_report_stockcard_toexcel($fungsi,$awal,$akhir,$plant){
	$data 		= mdc_data_stockcard($fungsi,$awal,$akhir,$plant,'excel');
	$judul		= $data['judul'];
	$isi		= $data['isi'];

	$workbook = new Spreadsheet_Excel_Writer();
	$workbook->send('report_stock_card.xls');
	$worksheet =& $workbook->addWorksheet('report_bulanan');
	$worksheet->freezePanes(array(1, 0));
	$format =& $workbook->addFormat(array('Size' => 10,
			'Align' => 'center',
			'Bold' => 1,
			'Color' => 'white',
			'Pattern' => 1,
			'BgColor' => 'white',
			'FgColor' => 'grey'));
	$worksheet->setColumn(0,0,5);
	$worksheet->setColumn(1,1,15);
	$worksheet->setColumn(2,2,20);
	$worksheet->setColumn(3,3,15);
	$worksheet->setColumn(4,4,40);
	$worksheet->setColumn(5,5,5);
	$worksheet->setColumn(6,6,15);
	$worksheet->setColumn(7,7,20);
	$worksheet->setColumn(8,8,20);
	$worksheet->setColumn(9,9,15);
	$worksheet->setColumn(10,10,15);
	$worksheet->setColumn(11,11,10);
	
	$worksheet->write(0, 0, "DAFTAR BARANG DIRECT CHARGE YANG DISIMPAN");
	$worksheet->write(2, 0, "PENANGGUNG JAWAB");
	$worksheet->write(3, 0, "Fungsi:");
	$worksheet->write(3, 1, $data['fung_desc']);
	$worksheet->write(4, 0, "Plant:");
	$worksheet->write(4, 1, $data['plant_desc']);
	$worksheet->write(5, 0, "Posisi Akhir Bulan/Tahun:");
	$worksheet->write(5, 1, $data['awal']." s/d ".$data['akhir']);

	foreach ($judul as $key => $value){
		foreach ($value as $key1 => $hasil){
			$worksheet->write(8, $key, $hasil, $format);
		}
	}
	$r = 9;
	foreach ($isi as $key => $value){
		foreach ($value as $key1 => $hasil){
			if($hasil=='GREEN'){
				$format6 =& $workbook->addFormat();
				$format6->setFgColor('green');
				$worksheet->write($r, $key1, '', $format6);
			}elseif($hasil=='RED'){$format6 =& $workbook->addFormat();
				$format6->setFgColor('red');
				$worksheet->write($r, $key1, '', $format6);
			}else{
				$worksheet->write($r, $key1, $hasil);
			}
		}
		$r++;
	}
	$workbook->close();
}

function mdc_report_stockcard_submit($form, &$form_state) {		
	$awal = strtotime($form_state['awal']);
	$akhir = strtotime($form_state['akhir']);
	$fungsi = $form_state['fungsi'];
	$plant = $form_state['plant'];
	
	if($form_state['op']==$form_state['submit']){
		$proses = mdc_report_stockcard_toexcel($fungsi,$awal,$akhir,$plant);
	}

	drupal_goto("mdc/online/report/stockcard/$fungsi/$awal/$akhir/$plant");
}

function mdc_report_warninglog(){
	$judul = array(
				array('data'=>t('Nomor'),),
				array('data'=>t('Lokasi'),), 
				array('data'=>t('Gudang'),), 
				array('data'=>t('Fungsi'),), 
				array('data'=>t('Kimap'),), 
				array('data'=>t('Deskripsi/Spesifikasi Material'),), 
				array('data'=>t('Qty'),), 
				array('data'=>t('No Kontrak/OAS/PO'),),
				array('data'=>t('Nomor GR'),),
				array('data'=>t('Tanggal GR'),),
			);
		
		$next_3_month =  3*30*24*60*60;
		$current_time = time();
		db_set_active('pep');
		
		$db_data = db_query("SELECT stock.*,hist.*,material.description mat_desc ,material.materialCode, fungsi.description fung_desc ,
					wh.description gudang, plant.description lokasi
					FROM `mdc_stock` stock 
					join mdc_hist hist on stock.idStock=hist.idStok 
					join mdc_material material on stock.idMaterial=material.id 
					join mdc_fungsi fungsi on stock.idFungsi=fungsi.idFungsi
					join mdc_warehouse wh on stock.idWarehouse=wh.idWarehouse
					join mdc_plant plant on plant.idPlant=wh.idPlant
					where stock.tanggal_gr + $next_3_month < $current_time and 
					stock.tanggal_penggunaan + $next_3_month < $current_time");
		
		$nomor = 1;
		$stock_set = array();
		while($result = db_fetch_array($db_data)) {
			if(!in_array($result['idStock'],$stock_set)){
				$stock_set[] = $result['idStock'];
				if($result['tanggal_penggunaan'] == 0){
					$tgl_penggunaan = "-";
				}else{
					$tgl_penggunaan = date("d-m-Y",$result['tanggal_penggunaan']);
				}
				
				if($result['tanggal_gr'] == 0){
					$tgl_gr = "-";
				}else{
					$tgl_gr = date("d-m-Y",$result['tanggal_penggunaan']);
				}
				
				if($result['tanggal_gr']+$next_12_month < time()){
					$status_flag = "<img src='".base_path().drupal_get_path('module','mdc')."/img/red.png'/>";
				}else{
					$status_flag = "<img src='".base_path().drupal_get_path('module','mdc')."/img/green.png'/>";
				}
				$isi[] 	= array($nomor,$result['lokasi'],$result['gudang'],$result['fung_desc'],$result['materialCode'],$result['mat_desc'],$result['stok_akhir'],$result['po']
					,$result['gr'],$tgl_gr);
				$nomor++;
			}
		}
	
	$back = "<br/><a href='" .base_path(). "mdc/online/report'>[back]</a>";
	$title = "<h2>Warning Log</h2>";
	return $back.$title.theme_table($judul,$isi);
}