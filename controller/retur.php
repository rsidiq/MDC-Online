<?php 
// RETUR ============================================================================
function mdc_retur() {
	$status_pil = array(0=>'All',1=>'Waiting for Manager approval',2=>'Approved by Manager, waiting for SCM Approval',8=>'Approved by SCM, waiting for Return to Stock',9=>'Returned To Stock / Closed',5=>'Rejected by Manager',6=>'Rejected by SCM');
	$form['#attributes'] = array('enctype' => 'multipart/form-data');	
	$form['tampilkan1'] = array(
			'#type' => 'fieldset',
			'#title' => t('Return View'),
			'#weight' => 0,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	$form['tampilkan1']['periode_awal'] = array(
	  	'#type' => 'textfield',
	  	'#title' => t('Periode'),
	  	'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
	  	'#jscalendar_ifFormat' => '%Y-%m-%d',
	  	'#jscalendar_showsTime' => 'false',
	  	'#default_value' => $periode_awal,
	  	'#weight' => 1
	);	
	$form['tampilkan1']['periode_akhir'] = array(
	  	'#type' => 'textfield',
	  	'#title' => t('s.d'),
	  	'#attributes' => array('readonly'=>'readonly','class' => 'jscalendar'),
	  	'#jscalendar_ifFormat' => '%Y-%m-%d',
	  	'#jscalendar_showsTime' => 'false',
	  	'#default_value' => $periode_akhir,
	  	'#weight' => 2
	); 
	$form['tampilkan1']['status'] = array(
		'#title' => t('Status'),
		'#type' => 'select',	
		'#options' => $status_pil,
		'#default_value' =>  variable_get('status', $status),
		'#weight' => 3,
		'#required' => TRUE, 	
	);	
	$form['tampilkan1']['submit'] = array(
			'#type' => 'submit',
			'#value' => 'Show',
			'#weight' => 99,
	);
	$form['tampilkan2'] = array(
			'#type' => 'fieldset',
			'#title' => t('New Return'),
			'#weight' => 11,
			'#collapsible' => FALSE,
			'#collapsed' => TRUE,
	);
	if(!$_SESSION["return"]){
		$form['tampilkan2']['mdc_return_markup'] = array(
				'#value' => t('<a href="' .base_path(). 'mdc/online"><input type="button" value="New Return to Stock" disabled=disabled /></a>')
		);
		$form['tampilkan2']['mdc_return_markup_alert'] = array(
				'#value' => t('<br><br>Lakukan Set Pekerja terlebih dahulu sebelum Return')
		);
	}else{
		$hasil 	= cek_nopek_mdc($_SESSION['return']);
		$nama	= $hasil['nama'];
		$form['tampilkan2']['mdc_return_markup'] = array(
				'#value' => t('<a href="' .base_path(). 'mdc/online"><input type="button" value="New Return to Stock" /></a>')
		);
		$form['tampilkan2']['mdc_return_markup_alert'] = array(
				'#value' => t('<br><br>' .$nama. ' (' .$_SESSION['return']. ')')
		);
	};
	return $form;
}
function mdc_retur_submit($form, &$form_state) {
	$periode_awal 	= $form_state['periode_awal'];
	$periode_akhir 	= $form_state['periode_akhir'];
	$status		 	= $form_state['status'];	
	
	drupal_goto('mdc/online/retur/view', 'periode_awal=' .$periode_awal. '&periode_akhir=' .$periode_akhir. '&status=' .$status);
}
function mdc_retur_view() {
	$periode_awal 	= $_GET['periode_awal'];
	$periode_akhir 	= $_GET['periode_akhir'];
	$status		 	= $_GET['status'];
	$hasil = "<a href='online'><< back to home</a><br><br>";
	$judul = array(
			array('data'=>t('No'),),
			array('data'=>t('MaterialDescription'),),
			array('data'=>t('MaterialCode'),),
			array('data'=>t('PlantCode'),),
			array('data'=>t('Qty'),),
			array('data'=>t('Limit'),)
	);
	
	$isi[] = array(++$xyz, $periode_awal, $periode_akhir, mdc_status_pil($status), $qty, $limit);
	$output = theme_table($judul, $isi);
	return $hasil.$output;
}
// END RETUR ========================================================================