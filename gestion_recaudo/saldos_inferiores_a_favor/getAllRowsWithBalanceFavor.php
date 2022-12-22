<?php

/*
	Función que se trae todos los registros con saldo a favor (Pagos y Notas Crédito) que estén disponibles para cruzarse.
*/

function getAllRowsWithBalanceFavor($dbconn){
	try {
		$allData = [];

		// Traer los pagos y Notas crédito con montos unapplied - 1000
	/*	$sqlGetRegisters = "
    		SELECT DISTINCT ON (cartera.id_interno, cartera.numero_documento) * FROM cartera 
            INNER JOIN cliente ON cartera.cliente_id = cliente.id
            WHERE cliente.inactive = 'No' AND cartera.ubicacion_id != 0
            
            
        	AND cartera.tipo_transaccion IN ('Pago', 'Nota de crédito')
            
        	AND cartera.total_cartera > 0 AND cartera.total_cartera < 1000 
            
            and cartera.numero_documento not in (SELECT numero_documento FROM saldos_inf_a_favor )
            and cartera.id_interno in ('11730727', '11731228', '11731120', '11731221','11731220','11631906') 
            
        	ORDER BY cartera.numero_documento DESC
            
            LIMIT 6
    	";*/
    
    $sqlGetRegisters = "
    		SELECT DISTINCT ON (cartera.id_interno, cartera.numero_documento) * FROM cartera 
            INNER JOIN cliente ON cartera.cliente_id = cliente.id
            WHERE cliente.inactive = 'No' AND cartera.ubicacion_id != 0
            
            
        	AND cartera.tipo_transaccion IN ('Pago', 'Nota de crédito')
            
        	AND cartera.total_cartera > 0 AND cartera.total_cartera < 1000 
                        
        	ORDER BY cartera.numero_documento DESC
    	";
		$resultGetRegisters = pg_query( $dbconn, $sqlGetRegisters );
	
		if(!$resultGetRegisters){
    		print("¡Ocurrió un error al consultar los Pagos y Notas Crédito a Favor! \n");
    	}else{
    		if(pg_num_rows($resultGetRegisters) === 0){
        		print("¡No hay Pagos o NC a Favor de los clientes! \n");
        	}else{
        
        		$allRows = pg_fetch_all($resultGetRegisters);
        		foreach($allRows as $row){
            		$register = (object) $row;
                	
                	// Validar que no supere los 500 registros
                	if(count($allData) < 500){
                    	// Buscar en la tabla saldos_inf_a_favor si ya está el registro, si está, que no lo añada
                    	$sqlValidationDuplicate = "SELECT * FROM saldos_inf_a_favor WHERE id_interno=$register->id_interno AND numero_documento='$register->numero_documento' LIMIT 1";
                    	$resultValidationDuplicate = pg_query( $dbconn, $sqlValidationDuplicate );

                    	if(!$resultValidationDuplicate){
                        	print("¡Ocurrió un error al consultar validación de duplicado! \n");
                    	}else{
                        	if(pg_num_rows($resultValidationDuplicate) !== 0){
                            	while ($registerDuplicate = pg_fetch_object($resultValidationDuplicate)) {
                                	print("¡No añadir $registerDuplicate->numero_documento por validación de duplicado! \n");
                            	}
                        	}else{
                            	array_push($allData, $register);
                        	}
                    	}	
                    }
            	}
        	} 
    	}

		return $allData;
	} catch (\Exception $err) {
		print($err);
		return [];
	};
}
?>