<?php session_start(); ?>
<!DOCTYPE html>
<html>
  <head>
    <meta charset="utf-8" />
    <title>Login</title>
  </head>
  <body><?php
    
    require '../comunes/auxiliar.php';
    
    if (isset($_POST['nick'], $_POST['password'])):
      $nick = trim($_POST['nick']);
      $password = trim($_POST['password']);
      $con = conectar();
      $res = pg_query($con, "select id
                               from usuarios
                              where nick = '$nick' and
                                    password = md5('$password')");
      if (pg_num_rows($res) > 0):
        $fila = pg_fetch_assoc($res, 0);
        $_SESSION['usuario'] = $fila['id'];
        header("Location: /reservas/reservas/");
      else: ?>
        <h3>Error: contraseña incorrecta</h3><?php
      endif;
    endif; ?>

    <form action="login.php" method="post">
      <label for="nick">Nombre:</label>
      <input type="text" name="nick" /><br/>
      <label for="password">Contraseña:</label>
      <input type="password" name="password" /><br/>
      <input type="submit" value="Entrar" />
    </form>
  </body>
</html>
  
    