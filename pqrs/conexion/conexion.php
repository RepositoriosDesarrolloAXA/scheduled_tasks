<?php

    $dbconn = pg_connect("host=172.16.1.6 dbname=api_pqrs user=desarrolloaxa password=Ax4Desarr0ll0")
    or die('No se ha podido conectar: ' . pg_last_error());