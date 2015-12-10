<?php
print date('Y-m-d H:i:s').'<br />';
$x  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($x, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($x, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 500000));
socket_connect($x, 0, 8033);
if (isset($_GET['switchpool'])) {
  socket_write($x, '{"method":"switchpool","params":["analpaper.3", '.$_GET['switchpool'].']}'."\n");
  var_dump(@socket_read($x, 4096, PHP_NORMAL_READ));
}
socket_write($x, '{"method":"wtfisconnected","params":["'.$_SERVER['REMOTE_ADDR'].'"]}'."\n");
$wtf = trim(@socket_read($x, 4096, PHP_NORMAL_READ));
socket_close($x);
print '<h1>wtfisconnected</h1>';
print '<script type="text/javascript">setTimeout(function(){location.href = "/";},7000);</script>';
print '<pre>';
print json_encode(json_decode($wtf), JSON_PRETTY_PRINT);
print '</pre>';