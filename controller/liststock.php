<?php 
// LISTSTOCK ============================================================================
function mdc_liststock_view_data() {
	$name 		= $GLOBALS['user']->name; 
	$usr_data	= mdc_user_data($name); 
	$lokasi_usr = $usr_data['lokasi']; // lokasi user in numerik
	$lok_user	= mdc_plant_data($lokasi_usr);
	$lok_teks	= $lok_user['description'];
	
	$cat = array(1=>'Active', 2=>'Disabled');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('Material'),),
			array('data'=>t('Qty'),),
			array('data'=>t(''),)
	);
	db_set_active('pep');
// 	$db_data = db_query("SELECT * FROM mdc_stock WHERE idWarehouse=212 ORDER BY idWarehouse,idMaterial ASC"); // WH = 212 (sementara)
	$db_data = db_query("SELECT a.idMaterial, a.qty, a.idWarehouse FROM pep.mdc_stock AS a
					LEFT JOIN pep.mdc_warehouse AS b ON b.idWarehouse = a.idWarehouse WHERE b.idPlant=$lokasi_usr 
					ORDER BY b.idPlant ASC,a.idWarehouse,a.qty ASC");
	while($row = db_fetch_array($db_data)) {
		$sat 	= mdc_material_data($row['idMaterial']);
		$isi[] 	= array(++$xyz, mdc_material_data_select($row['idMaterial']),$row['qty'], $sat['satuan']);
	}
	db_set_active();

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	$hasil['lokasi']= $lok_teks;
	return $hasil;
}
function mdc_liststock_view() {
	$data 		= mdc_liststock_view_data();
	$lokasi		= 'Lokasi : ' . $data['lokasi'];
	$toexcel	= '<a href="' .base_path(). 'mdc/online/liststock/toexcel">Export to Excel</a> | ';
	$hasil 		= "<a href='" .base_path(). "mdc/online/issue/view?status=3'>[back]</a><br><br>";
	$output 	= theme_table($data['judul'], $data['isi']);
	return $toexcel.$hasil.$lokasi.$output;
}
function mdc_liststock_toexcel(){
	$tampilkan 	= mdc_liststock_view_data();
	$judul		= $tampilkan['judul'];
	$isi		= $tampilkan['isi'];

	$workbook = new Spreadsheet_Excel_Writer();
	$workbook->send('liststock_toexcel.xls');
	$worksheet =& $workbook->addWorksheet('Liststock toExcel');
	$worksheet->freezePanes(array(1, 0));
	$format =& $workbook->addFormat(array('Size' => 10,
			'Align' => 'center',
			'Bold' => 1,
			'Color' => 'white',
			'Pattern' => 1,
			'BgColor' => 'white',
			'FgColor' => 'grey'));
	$worksheet->setColumn(0,0,5);
	$worksheet->setColumn(1,1,70);
	$worksheet->setColumn(2,2,10);
	$worksheet->setColumn(3,3,10);

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
// END LISTSTOCK ========================================================================