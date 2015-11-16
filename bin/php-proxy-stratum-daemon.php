<?php
class Stratum {
  private $s = array();
  private $p = array();
  private $o = array();

  public function __construct() {
    $this->o = array(NULL);
    $this->p = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->p[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->p[0], 0, 8033) || die('ERROR: Could not bind to address.');
    socket_listen($this->p[0]);
    $this->s = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->s[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->s[0], 0, 3333) || die('ERROR: Could not bind to address.');
    socket_listen($this->s[0]);
    set_time_limit(0);
    for (;;) {
      $r = array_merge($this->s, array_filter($this->p));
      if (socket_select($r, $w = NULL, $e = NULL, 0)) $this->x($r);
    }
  }

  public function __destruct() {
    socket_close($this->p[0]);
    socket_close($this->s[0]);
  }

  private function x($r) {
    if (in_array($this->p[0], $r)) {
      $k = socket_accept($this->p[0]);
      socket_getpeername($k , $a);
      if ($a=='127.0.0.1')
        while ($a = @socket_read($k, 2048, PHP_NORMAL_READ))
          socket_write($k, $this->h($a)."\n");
      unset($r[array_search($this->p[0], $r)]);
    }
    if (in_array($this->s[0], $r)) {
      if (($k = count($this->s))<9999) {
        $this->p[] = NULL;
        $this->o[] = new U();
        $this->s[] = socket_accept($this->s[0]);
        $this->l('connected, total: '.$k.'.');
      } else $this->l('ignored, too many.');
      unset($r[array_search($this->s[0], $r)]);
    }
    foreach($r as $_r) {
      $k = ($_k = array_search($_r, $this->s)) ?: array_search($_r, $this->p);
      $_d = $this->o[$k]->d(@socket_read($_r, 2048, PHP_NORMAL_READ));
      if ($_d === FALSE || !($d = json_decode($_d, TRUE))) $this->k($k, 'lost');
      else if ($_k === FALSE) {
        if ($this->s[$k]) {
          if (isset($d['id']) && $d['id'] && $d['id'] == $this->o[$k]->s[0]) {
            if (isset($d['result']) && isset($d['result'][1]) && $d['result'][1]) {
              $this->l($k.' gets extranonce ["'.$d['result'][1].'", '.$d['result'][2].'].');
              socket_write($this->s[$k], '{"params":["'.$d['result'][1].'",'.$d['result'][2].'],"method":"mining.set_extranonce","id":null}'."\n");
            }
          } else if(!isset($d['method']) || $d['method']!='client.show_message') {
            $this->l($k.' gets: '.$_d);
            socket_write($this->s[$k], $_d);
          }
          if(isset($d['method']) && $d['method']=='mining.set_difficulty' && isset($d['params']) && isset($d['params'][0]))
            $this->o[$k]->F = $d['params'][0];
          $this->t($k);
        } else $this->k($k, 'lost before server');
      } else {
        $this->l($k.' says: '.$_d);
        if (isset($d['method'])) {
          if ($d['method'] == 'mining.subscribe') {
            $this->l($k.' gets subscription '.$d['id'].'.');
            socket_write($this->s[$k], '{"id":'.$d['id'].',"result":[[["mining.set_difficulty","1"],["mining.notify","1"]],"00",4],"error":null}'."\n");
            if (!$this->p[$k]) {
              $this->o[$k]->v = (isset($d['params']) && isset($d['params'][0]) && $d['params'][0]) ? $d['params'][0] : 'unknown';
              $this->o[$k]->s = array($d['id'], $_d);
            }
          } else if ($d['method'] == 'mining.authorize') {
            $this->l($k.' gets authorization '.$d['id'].'.');
            socket_write($this->s[$k], '{"error":null,"id":'.$d['id'].',"result":true}'."\n");
            if (isset($d['params']) && isset($d['params'][0]) && $d['params'][0]) {
              $this->o[$k]->u = $d['params'][0];
              $this->c($k);
            } else $this->k($k, 'unkown.');
          } else if ($this->p[$k]) {
            if(isset($d['method']) && $d['method']=='mining.submit' && isset($d['params']) && isset($d['params'][0]) and $d['params'][0]==$this->o[$k]->P[2])
              $this->t($k, TRUE);
            $this->l('server '.$k.' gets '.$_d);
            socket_write($this->p[$k], $_d);
          } else $this->k($k, 'lost server');
        } else $this->k($k, 'said garbage');
      }
    }
  }

  private function c($k, $o = 0) {
    if ($this->p[$k]) socket_close($this->p[$k]);
    $this->p[$k] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!($p = $this->o[$k]->c($this->p[$k], $o))) $this->k($k, 'lost pools');
    else if ($this->o[$k]->s) {
      socket_write($this->p[$k], $this->o[$k]->s[1]);
      socket_write($this->p[$k], '{"id": '.($this->o[$k]->s[0]+1).', "method": "mining.authorize", "params": ["'.$p[2].'", "x"]}'."\n");
      $this->l($k.' connected to '.$p[0].':'.$p[1].' as '.$p[2].'.');
    } else $this->k($k, 'miss subscribe.');
  }

  private function k($k, $m) {
    unset($this->s[$k], $this->p[$k], $this->o[$k]);
    $this->s = array_values($this->s);
    $this->p = array_values($this->p);
    $this->o = array_values($this->o);
    $this->l($k.' '.$m.', killed.');
  }

  private function t($k, $F = FALSE) {
    if ($F) {
      $this->o[$k]->S300 += $this->o[$k]->F;
      $this->o[$k]->S3600 += $this->o[$k]->F;
      $this->o[$k]->S86400 += $this->o[$k]->F;
    }
    $this->o[$k]->H300 = number_format(14316776.11*$this->o[$k]->S300/exp(21), 2, ',', '.');
    $this->o[$k]->H3600 = number_format(1193064.6758333*$this->o[$k]->S3600/exp(21), 2, ',', '.');
    $this->o[$k]->H86400 = number_format(49711.028159722*$this->o[$k]->S86400/exp(21), 2, ',', '.');
    if ($this->o[$k]->St300<time()) {
      $this->o[$k]->S300 = 0;
      $this->o[$k]->St300 = time() + 300;
    }
    if ($this->o[$k]->St3600<time()) {
      $this->o[$k]->S3600 = 0;
      $this->o[$k]->St3600 = time() + 3600;
    }
    if ($this->o[$k]->St86400<time()) {
      $this->o[$k]->S86400 = 0;
      $this->o[$k]->St86400 = time() + 86400;
    }
  }

  private function h($h) {
    $this->l('HTTP says '.$h);
    $d = array('result'=>NULL);
    if (($h = @json_decode($h, TRUE)) && isset($h['method']))
      switch($h['method']) {
        case 'wtfisconnected':
          foreach($this->o as $k => $o) {
            if (!$o) continue;
            if ($o->u)
              $d['result'][] = $o->u.' is fuckin connected with '.$o->v.' to '.$o->P[0].' at '.$o->F.' diff and '.$o->H300.'GH/s 5min avg and '.$o->H3600.'GH/s 1hour avg and '.$o->H86400.'GH/s 1day avg hashrate as '.$o->P[2].'.';
            else $d['result'][] = $k.' is zombie.';
          }
          break;
        case 'switchpool':
          foreach($this->o as $k => $o) {
            if (!$o || $o->u!=$h['params'][0]) continue;
            $this->c($k, $h['params'][1]);
            break;
          }
          break;
      }
    return json_encode($d);
  }

  private function l($m) {# return;
    print date('H:i:s') .': Client '.$m.(strpos($m, "\n")===FALSE ? PHP_EOL : NULL);
  }
}

class U {
  public $v = NULL;
  public $s = NULL;
  public $H300 = 0;
  public $S300 = 0;
  public $St300 = 0;
  public $H3600 = 0;
  public $S3600 = 0;
  public $St3600 = 0;
  public $H86400 = 0;
  public $S86400 = 0;
  public $St86400 = 0;
  public $F = 0;
  public $P = NULL;
  private $p = NULL;

  public function __get($k) { return NULL; }

  public function __set($k, $v) {
    $this->$k = $v;
    if ($k=='u') {
      $this->P = array('solo.ckpool.org', 3333, '1CArLeSkmBT1BkkcADtNrHoLSgHVhBcesk');
      $this->p = array(
        'analpaper.3' => array(
          'p' => array(
            array('eu.stratum.bitcoin.cz', 3333, 'analpaper.0'),
            array('stratum.f2pool.com', 3333, 'analpaper.0')
          )
        ),
        'analpaper.2' => array(
          'p' => array(
            array('eu.stratum.bitcoin.cz', 3333, 'analpaper.0'),
            array('stratum.f2pool.com', 3333, 'analpaper.0')
          )
        )
      );
    }
  }

  public function c($p, $o = 0) {
    if (!isset($this->u)) return FALSE;
    if (isset($this->p[$this->u]) && isset($this->p[$this->u]['p']))
      foreach($this->p[$this->u]['p'] as $_o => $_p)
        if ($_o<$o) continue;
        else if (socket_connect($p, $_p[0], $_p[1])) return $this->P = $_p;
    if (socket_connect($p, $this->P[0], $this->P[1])) return $this->P;
    return FALSE;
  }

  public function d($d) {
    return ($d && isset($this->u)) ? strtr($d, array($this->u => $this->P[2])) : $d;
  }
}

new Stratum();
