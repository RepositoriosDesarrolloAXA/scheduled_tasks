<?php 

require_once(realpath('/var/www/scheduled_tasks/toma_pedido/conexion.php')); // base de datos
require_once(realpath('/var/www/scheduled_tasks/toma_pedido/funtions.php')); // funciones
require_once(realpath('/var/www/scheduled_tasks/toma_pedido/netsuite.php')); // funciones

//tiempo
date_default_timezone_set('America/Bogota');
$tiempo_inicio = microtime_float();
$fecha_LOG = date('Y-m-d');
$fecha_inicio_log = Date('Y-m-d\TH:i:s');

$resultado_cantidad = conexion_netsuite(2097, 'customsearch_ks_item_loaction_on_hand', 0, 1);
if($resultado_cantidad > 0){

    //eliminar datos de la tabla
    pg_query($dbconn, "TRUNCATE ONLY inventario_netsuite RESTART IDENTITY");

    print("Cantidad obtenida: " . $resultado_cantidad . "<br>");

    $start = 0;
    $end = 0;
    $cantidad_busqueda = 0;

    $cantidad_for = ceil($resultado_cantidad/1000)*1000;
    $cantidad_dividir = $cantidad_for / 1000;
    for ($i=0; $i < $cantidad_dividir ; $i++) { 
        
        $start = ($i == 0) ? 0 : $start + 1000;
        $end = $end + 1000;
        $start= str_pad($start, mb_strlen($end), "0", STR_PAD_LEFT); 

        print("Start: " . $start . " - End: " . $end . "<br>\n");

        $resultado = conexion_netsuite(1309, null, $start, $end);
        $data = json_decode($resultado, true);

        //print($start . " / " . $end . " // cantidad netsuite: " . count($data)."<br>");
        $cantidad_busqueda = $cantidad_busqueda + count($data);

        $informacion = array();

        if(!empty($data)){
            for($a=0; $a<count($data); $a++){

                $y=0;

                $item_id                 = $data[$a]["values"]["internalid"][$y]["text"];
                $nombre                  = empty($data[$a]["values"]["itemid"]) ? 'null' : str_replace("'", "", $data[$a]["values"]["itemid"]);
                $ubicacion_id            = $data[$a]["values"]["inventoryLocation.internalid"][$y]["text"];
                $ean                     = $data[$a]["values"]["upccode"];
                $disponible              = empty($data[$a]["values"]["locationquantityavailable"]) ? 0 : $data[$a]["values"]["locationquantityavailable"];

                $informacion[] = "($item_id, '$nombre', $ubicacion_id, '$ean', $disponible, CURRENT_TIMESTAMP)";

            }

            $sql1 = "INSERT INTO inventario_netsuite (item_id, nombre, ubicacion_id, ean, disponible, created_at) VALUES ";
            $sql1 .= implode(',', $informacion);

            $result1_creacion = pg_query( $dbconn, utf8_encode($sql1) );
            $errorinsrt1 =  pg_last_error($dbconn);
            if (!isset($errorinsrt1)){
                var_dump($errorinsrt1);
                exit;
            } else {
                print($errorinsrt1."\n");
            }

        }

    }

    //print("<br>Cantidad ingresada: ".$cantidad_busqueda."<br>");

    //validar cantidad insertada en la tabla "inventario_netsuite"
    $query_2 = pg_query($dbconn, "SELECT count(id) FROM inventario_netsuite");
    $validar_cantidad = pg_fetch_row($query_2);

    $nueva_validacion_cantidad = $resultado_cantidad - intval($validar_cantidad[0]);
    if($nueva_validacion_cantidad < 100){

        //ejecutamos proceso de copiar tabla
        $result_copy = copiarTabla($dbconn, "inventario_netsuite", "inventario");
        if($result_copy) {

            $fecha_fin_log = Date('Y-m-d\TH:i:s');
            $tiempo_fin = microtime_float();
            $tiempo_a = $tiempo_fin - $tiempo_inicio;
            $tiempo = $tiempo_a / 60;

            generarLog($dbconn, "inventario", $validar_cantidad[0], $validar_cantidad[0], 0, $fecha_LOG, $fecha_inicio_log, 
            $fecha_fin_log, $tiempo, "CURRENT_TIMESTAMP");

            print("Actualización 'inventario' finalizada");
        }

    } else {
        print("<br>Cantidad ingresada: ".$cantidad_busqueda."<br>");
        print("Se presento un error, la cantidad de 'inventario_netsuite', no es la correcta!");
    }    

} else {
    print('Esta busqueda guardada no tiene registros!');
}

