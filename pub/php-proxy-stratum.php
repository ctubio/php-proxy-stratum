<?php
print date('Y-m-d H:i:s').'<br />';
$x  = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
socket_set_option($x, SOL_SOCKET, SO_REUSEADDR, 1);
socket_set_option($x, SOL_SOCKET, SO_RCVTIMEO, array('sec' => 5, 'usec' => 500000));
socket_connect($x, 0, 8033);
socket_write($x, '{"method":"wtfisconnected"}'."\n");
$wtf = trim(@socket_read($x, 2048, PHP_NORMAL_READ));
#socket_write($x, '{"method":"switchpool","params":["analpaper.3", 0]}'."\n");
#var_dump(@socket_read($x, 2048, PHP_NORMAL_READ));
socket_close($x);
print '<h1>wtfisconnected</h1>';
print '<pre>';
var_dump(json_decode($wtf));
print '</pre>';
