<?php

    require_once 'conexion.php'; // base de datos
    require_once 'Oauth256.php'; // autenticacion con netsuite
    require_once 'funtions.php'; // funciones

    //alidar si existe pedido
    $sql1_validar = "SELECT * FROM inventario WHERE NOT EXISTS 
    (SELECT * FROM inventario_netsuite WHERE 
     inventario_netsuite.item_id = inventario.item_id AND inventario_netsuite.ubicacion_id = inventario.ubicacion_id)";
    $result1 = pg_query( $dbconn, $sql1_validar );
    
    if (pg_num_rows($result1) > 0){
        while ($row = pg_fetch_array($result1)) {

            $id_inventario = $row["id"];
            $disponible = 0;

            $sql1_actualizar = "UPDATE inventario SET 
                        disponible = $disponible, 
                        updated_at = CURRENT_TIMESTAMP 
                        WHERE id = '".$id_inventario."'";

            $result1_act = pg_query( $dbconn, $sql1_actualizar );
            print($result1_act);

        }
        
    }

    print("\n\ntermino");

?> 