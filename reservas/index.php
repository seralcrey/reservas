<?php session_start(); ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Reserva de pistas</title>
  </head>
  <body><?php
    
    if (isset($_POST['salir']))
    {
      session_destroy();
      setcookie(session_name(), '', 1, '/');
      header("Location: /reservas/usuarios/login.php");
    }

    if (isset($_SESSION['usuario']))
    {
      $usuario = (int) trim($_SESSION['usuario']);
    }
    else
    {
      header("Location: /reservas/usuarios/login.php");
    }
    
    $dows = array('Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves',
                  'Viernes', 'Sábado');
    
    define("UN_DIA", 3600 * 24);
    
    require '../comunes/auxiliar.php';

    $con = conectar();
    
    function boton_anular_reserva($id, $lunes)
    { ?>
      <td align="center">
        <form action="index.php" method="post">
          <input type="hidden" name="id" value="<?= $id ?>" />
          <input type="hidden" name="lunes" value="<?= $lunes ?>" />
          <input type="submit" value="Anular" />
        </form>
      </td><?php
    }

    function anular_reserva($id)
    {
      global $con;
      
      $res = pg_query($con, "delete from reservas
                             where id::text = '$id'");
    }
    
    function boton_hacer_reserva($dia, $hora, $lunes)
    { ?>
      <td align="center">
        <form action="index.php" method="post">
          <input type="hidden" name="dia" value="<?= $dia ?>" />
          <input type="hidden" name="hora" value="<?= $hora ?>" />
          <input type="hidden" name="lunes" value="<?= $lunes ?>" />
          <input type="submit" value="Reservar" />
        </form>
      </td><?php
    }

    function hacer_reserva($pistas_id, $dia, $hora)
    {
      global $usuario;
      global $con;

      $res = pg_query($con, "insert into reservas (pistas_id, fecha, hora,
                                                   usuarios_id)
                             values ($pistas_id, '$dia'::date, $hora,
                                     $usuario)");
    }

    $res = pg_query($con, "select id, nombre
                           from pistas");

    if (isset($_POST['pistas_id']))
    {
      $pistas_id = trim($_POST['pistas_id']);
    }
    elseif (isset($_SESSION['pistas_id']))
    {
      $pistas_id = $_SESSION['pistas_id'];
    } 
    else
    {
      $fila = pg_fetch_assoc($res, 0);
      $pistas_id = $fila['id'];
    }

    $_SESSION['pistas_id'] = $pistas_id;

    if (isset($_POST['id']))
    {
      anular_reserva($_POST['id']);
    }
    
    if (isset($_POST['dia'], $_POST['hora']))
    {
      hacer_reserva($pistas_id, $_POST['dia'], $_POST['hora']);
    }


    $res = pg_query($con, "select nick
                           from usuarios
                           where id = $usuario");
    $fila = pg_fetch_assoc($res, 0);
    $nick = $fila['nick']; ?>

    <form action="index.php" method="post">
      <p align="right">Usuario: <strong><?= $nick ?></strong>
        <input type="submit" name="salir" value="Salir" />
      </p>
    </form>

    <hr/><?php

    if (isset($_POST['lunes']))
    {
      $lunes = trim($_POST['lunes']);
    }
    else
    {
      $dow = (int) date('w');
      $dif = ($dow == 0) ? 6 : $dow - 1;
      $lunes = time() - $dif * UN_DIA;
    }

    $lunes_sql = date('Y-m-d', $lunes);
    
    $res = pg_query($con, "select id, nombre
                           from pistas"); ?>

    <form action="index.php" method="post">
      <select name="pistas_id"><?php
        for ($i = 0; $i < pg_num_rows($res); $i++):
          $fila = pg_fetch_assoc($res, $i); ?>
          <option value="<?= $fila['id'] ?>"
            <?= ($pistas_id == $fila['id']) ? 'selected': '' ?> >
            <?= $fila['nombre'] ?>
          </option><?php
        endfor; ?>
      </select>
      <input type="hidden" name="lunes" value="<?= $lunes ?>" />
      <input type="submit" value="Mostrar reservas" />
    </form>
    
    <hr/><?php
    
    $res = pg_query($con, "select id, to_char(fecha, 'YYYY-MM-DD') as fecha,
                                  hora, usuarios_id
                             from reservas
                            where pistas_id = $pistas_id and
                                  fecha between '$lunes_sql'::date and
                                                '$lunes_sql'::date + 4
                         order by hora, fecha");
      
    $tabla = array();
    
    for ($i = 0; $i < 5; $i++) {
      $tabla[] = array();
    }
                          
    for ($i = 0; $i < pg_num_rows($res); $i++)
    {
      $fila = pg_fetch_assoc($res, $i);
      extract($fila);
      $tabla[$fecha][$hora] = array('id' => $id,
                                    'usuarios_id' => $usuarios_id);
    } ?>

    <table style="margin:auto">
      <tr>
        <td>
          <form action="index.php" method="post">
            <input type="hidden" name="lunes" value="<?= $lunes - UN_DIA * 7 ?>" />
            <input type="submit" value="Patrás" />  
          </form>
        </td>
        <td>
          <form action="index.php" method="post">
            <input type="hidden" name="lunes" value="<?= $lunes + UN_DIA * 7 ?>" />
            <input type="submit" value="Palante" />  
          </form>
        </td>
      </tr>
    </table>


    <table border="1" style="margin: auto">
      <thead>
        <th>Hora</th><?php
        for ($d = $lunes, $i = 1; $d < $lunes + 5 * UN_DIA; $d += UN_DIA, $i++):
          $dia = date('d-m-Y', $d); ?>
          <th><?= $dia ?><br/>(<?= $dows[$i] ?>)</th><?php
        endfor; ?>
      </thead>
      <tbody><?php
        for ($h = 10; $h < 20; $h++): ?>
          <tr>
            <td><?= $h ?>:00</td><?php
            for ($d = $lunes; $d < $lunes + 5 * UN_DIA; $d += UN_DIA):
              $dia = date('Y-m-d', $d);
              if (isset($tabla[$dia][$h])):
                if ($tabla[$dia][$h]['usuarios_id'] == $usuario):
                  boton_anular_reserva($tabla[$dia][$h]['id'], $lunes);
                else: ?>
                  <td align="center">Reservado</td><?php 
                endif;
              else:
                boton_hacer_reserva($dia, $h, $lunes);
              endif;
            endfor; ?>
          </tr><?php
        endfor; ?>
      </tbody>
    </table><?php
    pg_close($con); ?>
  </body>
</html>
