<?php

	function generateRandomString() {
		$length = 20;
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);
		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	function microtime_float() {
        list($useg, $seg) = explode(" ", microtime());
        return ((float)$useg + (float)$seg);
    }
    
    function savefecha($vfecha) {
        $fch=explode("/",$vfecha);$tfecha=$fch[2]."/".$fch[1]."/".$fch[0];
        return $tfecha;
    }

	function slugify($string) {
        $string = utf8_encode($string);
        $string = iconv('UTF-8', 'ASCII//TRANSLIT', $string);   
        $string = preg_replace('/[^a-z0-9- ]/i', '', $string);
        $string = str_replace(' ', '-', $string);
        $string = trim($string, '-');
        $string = strtolower($string);
    
        if (empty($string)) {
            return 'n-a';
        }
    
        return $string;
    }

    function copiarTabla($dbconn, $tabla_origen, $tabla_destino)
    {
        $tabla_copiada = pg_copy_to($dbconn, $tabla_origen);
        pg_query($dbconn, "TRUNCATE ONLY $tabla_destino RESTART IDENTITY");
        pg_copy_from($dbconn, $tabla_destino, $tabla_copiada);
        return true;
    }

    function generarLog($dbconn, $tabla, $cantidad_registros, $valores_creados, $valores_actualizados, $fecha_LOG, $fecha_inicio_log, $fecha_fin_log, $tiempo) {
        $sql_log = "INSERT INTO log (proceso, tabla, cant_registro, cant_insertados, 
            cant_actualizados, fecha, fecha_inicio, fecha_fin, tiempo, created_at) VALUES 
            ('REGISTRO', '$tabla', $cantidad_registros, $valores_creados, $valores_actualizados, 
            '$fecha_LOG', '$fecha_inicio_log', '$fecha_fin_log', '$tiempo', CURRENT_TIMESTAMP)";
        pg_query( $dbconn, $sql_log );
        $errorinsrt1 =  pg_last_error($dbconn);
        if (!isset($errorinsrt1)){
            var_dump($errorinsrt1);
            exit;
        }
    }

?>