<?php session_start(); ?>
<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8" />
        <title>Reserva de pistas</title>
    </head>
    <body><?php
        require '../comunes/auxiliar.php';

        define("UN_DIA", 3600 * 24);
        $dows = array('Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes');

        function dibujar_celda($tabla, $dia, $h, $lunes, $pistas_id, $usuario_id)
        {
            if (isset($tabla[$dia][$h])):
                $id = $tabla[$dia][$h]['id'];
                $usuarios_id = $tabla[$dia][$h]['usuarios_id'];
                if ($usuario_id == $usuarios_id): ?>
                    <form action="reindex.php" method="post">
                        <input type="hidden" name="lunes"
                               value="<?= $lunes ?>" />
                        <input type="hidden" name="id"
                               value="<?= $id ?>" />
                        <input type="submit" value="Anular" />
                    </form><?php
                else: ?>
                    Reservado<?php
                endif;
            else: ?>
                <form action="reindex.php" method="post">
                    <input type="hidden" name="lunes"
                           value="<?= $lunes ?>" />
                    <input type="hidden" name="pistas_id"
                           value="<?= $pistas_id ?>" />
                    <input type="hidden" name="hora"
                           value="<?= $h ?>" />
                    <input type="hidden" name="fecha"
                           value="<?= $dia ?>" />
                    <input type="submit" value="Reservar" />
                </form><?php
            endif;
        }

        function selected($a, $b)
        {
            return $a == $b ? 'selected="on"' : '';
        }

        conectar();

        if (!isset($_SESSION['usuario'])) {
            header("Location: ../usuarios/login.php");
            return;
        } else {
            $usuario_id = $_SESSION['usuario'];
        }

        $res = pg_query_params("select *
                                 from usuarios
                                where id = $1", array($usuario_id));

        $fila = pg_fetch_assoc($res, 0);
        $nick = $fila['nick']; ?>

        <form action="../usuarios/logout.php" method="post">
            <p align="right">
                Usuario: <strong><?= $nick ?></strong>
                <input type="submit" value="Salir" />
            </p>
        </form>
        <hr/><?php

        $pistas_id = isset($_POST['pistas_id']) ? trim($_POST['pistas_id']) :
                     "";

        if (isset($_POST['id'])) {
            $id = trim($_POST['id']);
            $res = pg_query_params("delete from reservas
                                     where id = $1", array($id));
        } elseif (isset($_POST['fecha'], $_POST['hora'], $_POST['pistas_id'])) {
            $fecha = trim($_POST['fecha']);
            $hora = trim($_POST['hora']);
            $pistas_id = trim($_POST['pistas_id']);
            $res = pg_query_params("insert into reservas (pistas_id, usuarios_id,
                                                    fecha, hora)
                                    values ($1, $2, $3, $4)",
                                    array($pistas_id, $usuario_id, $fecha, $hora));
        }

        if (isset($_POST['lunes'])) {
            $lunes = trim($_POST['lunes']);
        } else {
            $dow = (int) date("w");
            $dif = $dow == 0 ? 6 : $dow - 1;
            $lunes = time() - $dif * UN_DIA;
        } ?>

        <form action="reindex.php" method="post"><?php
            $res = pg_query("select * from pistas order by id");
            if ($pistas_id == "") {
                $fila = pg_fetch_assoc($res, 0);
                $pistas_id = $fila['id'];
            } ?>
            <select name="pistas_id"><?php
                for ($i = 0; $i < pg_num_rows($res); $i++):
                    $fila = pg_fetch_assoc($res, $i);
                    extract($fila); ?>
                    <option value="<?= $id ?>" <?= selected($pistas_id, $id) ?> >
                        <?= $nombre ?>
                    </option><?php
                endfor; ?>
            </select>
            <input type="submit" value="Mostrar reservas" />
        </form>

        <hr/>

        <table style="margin:auto">
            <td>
                <form action="reindex.php" method="post">
                    <input type="hidden" name="pistas_id"
                           value="<?= $pistas_id ?>" />
                    <input type="hidden" name="lunes"
                           value="<?= $lunes - 7 * UN_DIA ?>" />
                    <input type="submit" value="Patrás">
                </form>
            </td>
            <td>
                <form action="reindex.php" method="post">
                    <input type="hidden" name="pistas_id"
                           value="<?= $pistas_id ?>" />
                    <input type="hidden" name="lunes"
                           value="<?= $lunes + 7 * UN_DIA ?>" />
                    <input type="submit" value="Palante">
                </form>
            </td>
        </table><?php
        $lunes_sql = date('Y-m-d');

        $res = pg_query_params("select id,
                                       to_char(fecha, 'YYYY-MM-DD') as fecha,
                                       hora, usuarios_id
                                  from reservas
                                 where pistas_id = $1 and
                                       fecha between $2 and $2 + 4
                              order by hora, fecha",
                              array($pistas_id, $lunes_sql));

        $tabla = array();

        for ($i = 0; $i < 5; $i++) {
            $tabla[] = array();
        }

        for ($i = 0; $i < pg_num_rows($res); $i++) {
            $fila = pg_fetch_assoc($res, $i);
            extract($fila);
            $tabla[$fecha][$hora] = array('id' => $id,
                                          'usuarios_id' => $usuarios_id);
        } ?>

        <table border="1" style="margin:auto">
            <thead>
                <th>Hora</th><?php
                for ($d = 0; $d < 5; $d++):
                    $dia = $lunes + $d * UN_DIA;
                    $dia = date("d-m-Y", $dia);
                    $dow = $dows[$d]; ?>
                    <th><?= $dia ?><br/>(<?= $dow ?>)</th><?php
                endfor; ?>
            </thead>
            <tbody><?php
                for ($h = 10; $h < 20; $h++): ?>
                    <tr>
                        <td><?= $h ?>:00</td><?php
                        for ($d = 0; $d < 5; $d++):
                            $dia = $lunes + $d * UN_DIA;
                            $dia = date("Y-m-d", $dia); ?>
                            <td align="center"><?php
                                dibujar_celda($tabla, $dia, $h, $lunes,
                                              $pistas_id, $usuario_id); ?>
                            </td><?php
                        endfor; ?>
                    </tr><?php
                endfor; ?>
            </tbody>
        </table>
    </body>
</html>
