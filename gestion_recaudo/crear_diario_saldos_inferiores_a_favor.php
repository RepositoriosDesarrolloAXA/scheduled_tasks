<?php

/*
	Cron Job que Crea un diario con todas las notas créditos y Pagos que dejaron un monto menor a 1000 sin aplicar.
*/

require_once 'conexion.php'; // base de datos
require_once 'Oauth256.php'; // autenticacion con netsuite
require_once 'funtions.php'; // funciones

require_once 'saldos_inferiores_a_favor/getAllRowsWithBalanceFavor.php';
require_once 'saldos_inferiores_a_favor/createJournal.php';


error_reporting(E_ALL ^ E_WARNING);
header('Content-type: text/plain; charset=UTF-8');

// Obtener los registros con saldo a favor - 1000
$allRows = getAllRowsWithBalanceFavor($dbconn);

if(count($allRows) > 0){

	// Crear el diario
	$responseJournalCreated = createJournal($allRows);

	//Registrar los documentos (Pagos y Nc) con el diario al cual se relacionó en la tabla saldos_inf_a_favor.
	if($responseJournalCreated){
    	if($responseJournalCreated['message'] === 'success'){
        	print("El id del diario es {$responseJournalCreated['response']['id']} \n");
        
        	$dateCurrent = Date('Y-m-d\TH:i:s');
        	foreach($allRows as $row){
            	$sqlAddRowInSaldosAFavor = "
            		INSERT INTO saldos_inf_a_favor(
						id_interno, numero_documento, type, id_journal, monto_aplicado, pago_id, created_at, updated_at
             		)VALUES (
             			$row->id_interno, '$row->numero_documento', '$row->tipo_transaccion', {$responseJournalCreated['response']['id']}, $row->total_cartera, null, '$dateCurrent', null
             		)
             	";
        		$resultAdd = pg_query($dbconn, $sqlAddRowInSaldosAFavor);
        		if($resultAdd){
            		print("El registro $row->id_interno se añadió con exito a saldos_inf_a_favor \n");
            	}else{
            		print("Ocurrió un error al añadir el registro $row->id_interno a saldos_inf_a_favor \n");
            	}
            }
        }else{
        	print('Error en NetSuite al intentar crear el Diario: ' . $responseJournalCreated['response'] . "\n");
        }
    }	
}
?>