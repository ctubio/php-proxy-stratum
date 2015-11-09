<?php
class Stratum {
  private $s = array();
  private $p = array();
  private $m = array();

  public function __construct() {
    set_time_limit(0);
    $this->p = $this->m = array(NULL);
    $this->s = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->s[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->s[0], 0, 3333) or die('ERROR: Could not bind to address.');
    socket_listen($this->s[0]);
    for (;;) {
      $r = array_merge($this->s, array_filter($this->p));
      if (socket_select($r, $w = NULL, $e = NULL, 0))
        $this->x($r);
    }
  }

  public function __destruct() { socket_close($this->s[0]); }

  private function x($r) {
    if (in_array($this->s[0], $r)) {
      $this->p[] = NULL;
      $this->m[] = array();
      $this->s[] = socket_accept($this->s[0]);
      $this->l('connected, total: '.(count($this->s) - 1).'.');
      unset($r[array_search($this->s[0], $r)]);
    }
    foreach ($r as $_r) {
      $_d = @socket_read($_r, 2048, PHP_NORMAL_READ);
      $k = ($_k = array_search($_r, $this->s)) ?: array_search($_r, $this->p);
      if ($_d === FALSE) $this->k($k, 'lost');
      else if (!$_k) {
        if ($this->s[$k]) {
          $this->l($k.' gets: '.$_d);
          socket_write($this->s[$k], $_d);
          if (isset($this->m[$k][1]) && strpos($_d,'mining.notify')!==FALSE) {
            socket_write($this->p[$k], $this->m[$k][1]);
            $this->l('server '.$k.' gets '.$this->m[$k][1]);
            unset($this->m[$k][1]);
          }
          if (strpos($_d,'mining.notify')!==FALSE && strpos($_d,'mining.set_difficulty')!==FALSE) {
            $d = json_decode($_d);
            if (is_object($d) && isset($d->result) && isset($d->result[1]) && $d->result[1])
              socket_write($this->s[$k], json_encode(array(
                'params'=>array($d->result[1],$d->result[2]),
                'method'=>'mining.set_extranonce',
                'id'=>NULL
              ))."\n");
          }
        } else $this->k($k, 'lost before server');
      } else {
        $this->l($k.' says: '.$_d);
        if (($d = json_decode($_d)) && isset($d->method)) {
          if ($d->method == 'mining.subscribe') {
            if ($this->p[$k]) {
              $this->l('server '.$k.' gets '.$_d);
              socket_write($this->p[$k], $_d);
            } else {
              socket_write($this->s[$k], '{"id":'.$d->id.',"result":[[["mining.set_difficulty","1"],["mining.notify","1"]],"00",4],"error":null}'."\n");
              $this->m[$k][0] = $_d;
              $this->l($k.' gets subscription.');
            }
          } else if ($d->method == 'mining.authorize' and !$this->p[$k]) {
            $this->p[$k] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!socket_connect($this->p[$k], 'eu.stratum.bitcoin.cz', 3333)
              && !socket_connect($this->p[$k], 'stratum.f2pool.com', 3333))
              $this->k($k, 'lost pools');
            else if (isset($this->m[$k][0])) {
              $this->l('server '.$k.' gets '.$this->m[$k][0]);
              socket_write($this->p[$k], $this->m[$k][0]);
              unset($this->m[$k][0]);
              $this->m[$k][1] = $_d;
            }
          } else if ($this->p[$k]) {
            $this->l('server '.$k.' gets '.$_d);
            socket_write($this->p[$k], $_d);
          } else $this->k($k, 'lost server');
        } else $this->k($k, 'said garbage');
      }
    }
  }

  private function k($k, $m) {
    unset($this->s[$k], $this->p[$k], $this->m[$k]);
    $this->s = array_values($this->s);
    $this->p = array_values($this->p);
    $this->m = array_values($this->m);
    $this->l($k.' '.$m.', killed.');
  }

  private function l($m) {
    echo date('H:i:s'), ': Client ', $m, (
      (strpos($m, PHP_EOL)===FALSE && strpos($m, "\n")===FALSE)
        ? PHP_EOL : NULL
    );
  }
}

new Stratum();
