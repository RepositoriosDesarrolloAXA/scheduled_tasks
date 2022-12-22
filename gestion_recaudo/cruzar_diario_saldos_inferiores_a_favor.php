<?php

/*
	Cron que toma registros (Pagos y notas créditos) que están a favor del cliente y relaciona el pequeño monto que no se aplicó, con el diario.
*/

require_once 'conexion.php'; // base de datos
require_once 'Oauth256.php'; // autenticacion con netsuite
require_once 'funtions.php'; // funciones

require_once 'saldos_inferiores_a_favor/joinPayInJournal.php';
require_once 'saldos_inferiores_a_favor/joinCreditMemoInJournal.php';


error_reporting(E_ALL ^ E_WARNING);
header('Content-type: text/plain; charset=UTF-8');

try {
	// Obtener un maximo de 500 registros de la tabla saldos_inf_a_favor que no tengan nada en pago_id

	$sqlRowsWithoutCruceJournal = "SELECT * FROM saldos_inf_a_favor WHERE pago_id IS NULL ORDER BY id_interno DESC LIMIT 500";
	$resultGetRows = pg_query($dbconn, $sqlRowsWithoutCruceJournal);
	if(!$resultGetRows){
		print("Ocurrió un error al buscar los registros saldos_inf_a_favor con pago_id null \n");
	}else{
		if(pg_num_rows($resultGetRows) === 0){
    		print("¡No hay registros en saldos_inf_a_favor con pago_id en null! \n");
    	}else{
    		$allRows = pg_fetch_all($resultGetRows);
        
        	// Cruzar todos los registros con el diario
        	foreach($allRows as $row){
            	$register = (object) $row;

            	// Validar que tipo de documento es
            	if($register->type === 'Pago'){
                	cruzarPayWithJournal($dbconn, $register);
                }else if($register->type === 'Nota de crédito'){
                	cruzarCreditMemoWithJournal($dbconn, $register);
                }
            }
    	}
	}
}catch(\Exception $err){
	print('Error en catch al tratar de cruzar pagos y NC con el diario');
}
?>