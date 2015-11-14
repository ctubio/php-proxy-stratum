<?php
print date('Y-m-d H:i:s').'<br />';

$socket  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 500000));
socket_connect($socket, 0, 8033);
socket_write($socket, '{"method":"wtfisconnected"}'."\n");
$result = trim(@socket_read($socket, 2048, PHP_NORMAL_READ));
socket_close($socket);
print '<h1>wtfisconnected</h1>';
print '<pre>';
var_dump($result);
print '</pre>';