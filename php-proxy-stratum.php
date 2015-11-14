<?php
class Stratum {
  private $s = array();
  private $p = array();
  private $o = array();

  public function __construct() {
    set_time_limit(0);
    $this->p = $this->o = array(NULL);
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
      $this->o[] = array();
      $this->s[] = socket_accept($this->s[0]);
      $this->l('connected, total: '.(count($this->s) - 1).'.');
      unset($r[array_search($this->s[0], $r)]);
    }
    $ic = count($ik = array_keys($r));
    for($i=0;$i<$ic;$i++) {
      $_d = @socket_read($r[$ik[$i]], 2048, PHP_NORMAL_READ);
      $k = ($_k = array_search($r[$ik[$i]], $this->s)) ?: array_search($r[$ik[$i]], $this->p);
      if ($_d === FALSE) $this->k($k, 'lost');
      else if (!$_k) {
        if ($this->s[$k]) {
          if (isset($this->o[$k][1]) && strpos($_d,'mining.notify')!==FALSE) {
            socket_write($this->p[$k], $this->o[$k][1]);
            $this->l('server '.$k.' gets '.$this->o[$k][1]);
            unset($this->o[$k][1]);
          }
          if (strpos($_d,'mining.set_difficulty')!==FALSE && strpos($_d,'mining.notify')!==FALSE) {
            $d = json_decode($_d);
            if (is_object($d) && isset($d->result) && isset($d->result[1]) && $d->result[1]) {
              $this->l($k.' gets extranonce ["'.$d->result[1].'", '.$d->result[2].'].');
              socket_write($this->s[$k], '{"params":["'.$d->result[1].'",'.$d->result[2].'],"method":"mining.set_extranonce","id":null}'."\n");
            }
          } else {
            $this->l($k.' gets: '.$_d);
            socket_write($this->s[$k], $_d);
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
              $this->o[$k][0] = $_d;
              $this->l($k.' gets subscription '.$d->id.'.');
            }
          } else if ($d->method == 'mining.authorize' and !$this->p[$k]) {
            $this->p[$k] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
            if (!socket_connect($this->p[$k], 'eu.stratum.bitcoin.cz', 3333)
              && !socket_connect($this->p[$k], 'stratum.f2pool.com', 3333))
              $this->k($k, 'lost pools');
            else if (isset($this->o[$k][0])) {
              $this->l('server '.$k.' gets '.$this->o[$k][0]);
              socket_write($this->p[$k], $this->o[$k][0]);
              $this->l($k.' gets authorization '.$d->id.'.');
              socket_write($this->s[$k], '{"error":null,"id":'.$d->id.',"result":true}'."\n");
              unset($this->o[$k][0]);
              $this->o[$k][1] = $_d;
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
    unset($this->s[$k], $this->p[$k], $this->o[$k]);
    $this->s = array_values($this->s);
    $this->p = array_values($this->p);
    $this->o = array_values($this->o);
    $this->l($k.' '.$m.', killed.');
  }

  private function l($m) {
    print date('H:i:s') .': Client '.$m.(
      strpos($m, "\n")===FALSE ? PHP_EOL : NULL
    );
  }
}

new Stratum();
