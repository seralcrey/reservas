<?php

require '../comunes/auxiliar.php';

$con = conectar();

$res = pg_query($con, "delete from reservas
                        where fecha < current_date");

pg_close($con);

