<?php 
// LIST ALL RESERVASI ============================================================================
function mdc_listall_view_data() {
	// ===== paging =====
	$aw = arg(4); 	// $aw = mulai record yg akan ditampilkan
	$ak = 15;		// $ak = banyaknya baris yg akan ditampilkan
	if(!$aw){
		$aw = 0;
	}
	
	$js = '<script type="text/javascript">
			    function getval(sel) {
					window.location = "' .base_path(). 'mdc/online/master/listall/" + sel.value;
			    }
			</script>';
	// ===== paging =====
	
	$name 		= $GLOBALS['user']->name; 
	$usr_data	= mdc_user_data($name); 
	$lokasi_usr = $usr_data['lokasi']; // lokasi user in numerik
	$lok_user	= mdc_plant_data($lokasi_usr);
	$lok_teks	= $lok_user['description'];
	
	$stat = array(1=>'New Reservation', 2=>'Approved by Atasan', 3=>'Approved by SCM', 5=>'Rejected by Atasan', 6=>'Rejected by SCM', 7=>'Good Issued');
	$judul = array(array('data'=>t('No.'),),
			array('data'=>t('No. Reservasi'),),
			array('data'=>t('Status'),),
			array('data'=>t('Fungsi'),),
			array('data'=>t('Input'),),
			array('data'=>t('Penanggung Jawab'),),
			array('data'=>t('Atasan Approver'),),
			array('data'=>t('SCM Approver'),),
			array('data'=>t('Good Issued - Taken'),)
	);
	db_set_active('pep');
// 	$db_data = db_query("SELECT * FROM mdc_reservation ORDER BY statusApproval ASC");
	$db_data = db_query("SELECT DISTINCT a.reservasiNo, b.idPlant, a.input, a.issuer, a.statusApproval, a.requestBy, a.mgrApproval, a.scmApproval, a.nameClose FROM pep.mdc_reservation AS a
							LEFT JOIN pep.mdc_reservation_detil AS b ON b.reservasiNo = a.reservasiNo WHERE b.idPlant=$lokasi_usr ORDER BY a.statusApproval ASC,a.reservasiNo DESC LIMIT $aw,$ak");
	
	// ===== paging =====
	$tes		= db_query("SELECT COUNT(DISTINCT reservasiNo) FROM mdc_reservation_detil WHERE idPlant=$lokasi_usr");
	$hitbrs		= db_fetch_array($tes);$hitbrs	= $hitbrs['COUNT(DISTINCT reservasiNo)']; 
	$sisa 		= $hitbrs % $ak;
	
	if($sisa > 0){ // $totpg = ganjil (ada sisa)
		$totpg 		= (($hitbrs - $sisa) / $ak);
	}
	
// 	$ling = '';
	$ling	= "<select name='category' id='category' onchange='getval(this);'>";
	for($lup=0;$lup<=$totpg;$lup++){
		$hit = $lup * $ak;
		$pg = $lup + 1;
		if($hit == $aw){
			$pil = 'selected';
		}else{
			$pil = '';
		}
// 		$ling .= '<a href="' .base_path(). 'mdc/online/master/listall/' .$hit. '"> ' .$pg. ' </a> ';
		$ling .= "<option " .$pil. " value='" .$hit. "'>" .$pg. "</option>";
	}
	$ling	.= "</select><br>";
	// ===== paging =====
	
	while($row = db_fetch_array($db_data)) {
		$inputusr	= mdc_user_data($row['input']);
		$req		= mdc_user_data($row['requestBy']);
		$atasan		= mdc_user_data($row['mgrApproval']);
		$scm		= mdc_user_data($row['scmApproval']);
		$gdgusr		= mdc_user_data($row['issuer']);
		$fung		= get_fungsi_user($row['requestBy']);
		$isi[] 		= array(++$xyz, $row['reservasiNo'], $stat[$row['statusApproval']], $fung['fungsi'], $inputusr['fullname'], $req['fullname'], $atasan['fullname'], $scm['fullname'], $gdgusr['fullname'] .' - '. $row['nameClose']);
	}
	db_set_active();
	
	// view paging : 
	$pagess 	= 'Pages : ' . $ling;

	$hasil['judul'] = $judul;
	$hasil['isi'] 	= $isi;
	$hasil['lokasi']= $lok_teks;
	$hasil['pagess']= $pagess;
	$hasil['js']= $js;
	return $hasil;
}
function mdc_listall_view() {
	$data 	= mdc_listall_view_data();
	$output = theme_table($data['judul'], $data['isi']);
	$lokasi = 'Lokasi : ' . $data['lokasi'];
	$pagess = '<br>' . $data['pagess'];
	$js		= $data['js'];
	$back = "<a href='" .base_path(). "mdc/online/master'>[back]</a><br><br>";
	return $js.$back.$lokasi.$pagess.$output;
}