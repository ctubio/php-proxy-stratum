<?php
class Stratum {
  private $s = array();
  private $p = array();
  private $o = array();

  public function __construct() {
    $this->o = array(NULL);
    $this->p = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->p[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->p[0], 0, 8033) || die('!8033');
    socket_listen($this->p[0]);
    $this->s = array(socket_create(AF_INET, SOCK_STREAM, SOL_TCP));
    socket_set_option($this->s[0], SOL_SOCKET, SO_REUSEADDR, 1);
    socket_bind($this->s[0], 0, 3333) || die('!3333');
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
          if (isset($d['method']) && $d['method']=='mining.set_difficulty' && isset($d['params']) && isset($d['params'][0]))
            $this->o[$k]->F = $d['params'][0];
          if (isset($d['result']) && $d['result']===true && isset($d['id']) && $d['id'])
          $this->o[$k]->t($d['id']);
        } else $this->k($k, 'lost before server.');
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
            if(isset($d['method']) && $d['method']=='mining.submit' && isset($d['params']) && isset($d['params'][0]) and $d['params'][0]==$this->o[$k]->P['user'])
              $this->o[$k]->t(-$d['id']);
            $this->l('server '.$k.' gets '.$_d);
            socket_write($this->p[$k], $_d);
          } else $this->k($k, 'lost server.');
        } else $this->k($k, 'said garbage.');
      }
    }
  }

  private function c($k, $o = 0) {
    if ($this->p[$k]) socket_close($this->p[$k]);
    $this->p[$k] = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
    if (!($p = $this->o[$k]->c($this->p[$k], $o))) $this->k($k, 'lost pools.');
    else if ($this->o[$k]->s) {
      socket_write($this->p[$k], $this->o[$k]->s[1]);
      socket_write($this->p[$k], '{"id": '.($this->o[$k]->s[0]+1).', "method": "mining.authorize", "params": ["'.$p['user'].'", "'.$p['pass'].'"]}'."\n");
      $this->o[$k]->I = array();
      $this->l($k.' connected to '.$p['url'].':'.$p['port'].' as '.$p['user'].'.');
    } else $this->k($k, 'miss subscribe.');
  }

  private function k($k, $m) {
    unset($this->s[$k], $this->p[$k], $this->o[$k]);
    $this->s = array_values($this->s);
    $this->p = array_values($this->p);
    $this->o = array_values($this->o);
    $this->l($k.' '.$m.', killed.');
  }

  private function h($h) {
    $this->l('HTTP request '.$h);
    $d = array('result'=>NULL);
    if (($h = @json_decode($h, TRUE)) && isset($h['method']))
      switch($h['method']) {
        case 'wtfisconnected':
          foreach($this->o as $k => $o) {
            if (!$o) continue;
            if (!is_null($o->u))
              $d['result'][] = array(
                'user'=>$o->u,
                'version'=>$o->v,
                'since'=>date(DATE_ISO8601, $o->T),
                'last'=>date(DATE_ISO8601, $o->L),
                'pool'=>$o->P,
                'pending'=>$o->I,
                'diff'=>$o->F,
                '5min avg'=>$o->h()
              );
            else $d['result'][] = $k.' is zombie.';
          }
          break;
        case 'switchpool':
          foreach($this->o as $k => $o) {
            if (!$o || is_null($o->u) || $o->u!=$h['params'][0]) continue;
            $this->c($k, $h['params'][1]);
          }
          break;
      }
    return json_encode($d);
  }

  private function l($m) { # return;
    print date('H:i:s') .': Client '.$m.(strpos($m, "\n")===FALSE ? PHP_EOL : NULL);
  }
}

class U {
  public $u = NULL;
  public $v = NULL;
  public $s = NULL;
  public $T = NULL;
  public $L = NULL;
  public $I = array();
  public $S = array();
  public $Ht = array(0);
  public $F = 0;
  public $P = array(
    'url'=>0,
    'id'=>'solo.ckpool.org',
    'port'=>3333,
    'user'=>'1CArLeSkmBT1BkkcADtNrHoLSgHVhBcesk',
    'pass'=>'x'
  );
  private $p = NULL;

  public function c($p, $o = 0) {
    if (is_null($this->u)) return FALSE;
    $m = new M();
    $this->p = $m->q('SELECT pools.id, pools.url, pools.port, pools.user, pools.pass FROM pools JOIN workers ON workers.worker = "%s" WHERE pools.worker_id = workers.id;', $this->u);
    if (!$this->p) return FALSE;
    foreach($this->p as $_p)
      if ($o && $_p['id']!=$o) continue;
      else if (@socket_connect($p, $_p['url'], $_p['port'])) return $this->P = $_p;
    if (@socket_connect($p, $this->P['url'], $this->P['port'])) return $this->P;
    return FALSE;
  }

  public function t($I) {
    if ($I<0) $this->I[abs($I)] = $this->L = time();
    else if (isset($this->I[$I])) {
      if (!$this->T) $this->T = $this->I[$I];
      if ($c = count($k = array_keys($this->I)))
        for($i=0;$i<$c;$i++) if ($this->I[$k[$i]]<$this->I[$I]-21) unset($this->I[$k[$i]]); else break;
      if (!isset($this->S[$this->I[$I]])) $this->S[$this->I[$I]] = 0;
      $this->h($this->S[$this->I[$I]] += $this->F);
      unset($this->I[$I]);
    }
    if ($c = count($k = array_keys($this->S)))
      for($i=0;$i<$c;$i++) if ($k[$i]<time()-120) unset($this->S[$k[$i]]); else break;
  }

  public function h($h = FALSE) {
    if ($c = count($k = array_keys($this->S)))
      for($i=0;$i<$c;$i++) if ($k[$i]<time()-120) unset($this->S[$k[$i]]); else break;
    if ($h) return; # pow(2,48)/65535/300/1e6 # 300 14.31677611
    $_H = 0;
    $H = bcmul(35.791940275, array_sum($this->S?:array(0)), 2);
    while($H>1000 && $_H<3) { $_H++; $H = bcdiv($H, 1000, 2); }
    return number_format($H, 2, ',', '.').' '.strtr($_H, array('M','G','T','P')).'H/s';
  }

  public function d($d) {
    return ($d && !is_null($this->u)) ? strtr($d, array($this->u => $this->P['user'])) : $d;
  }
}

class M extends mysqli {
  public function __construct() {
    parent::init();
    if (!parent::real_connect('10.10.10.17', 'phpstratumproxy', 'proxystratumphp', 'php-stratum-proxy')) return FALSE;
  }
  public function q() {
    if ($a = func_get_args()) {
      $q = array_shift($a);
      $a = array_map(array($this, 'real_escape_string'), $a);
      array_unshift($a, $q);
      if (!parent::real_query(call_user_func_array('sprintf', $a))) return FALSE;
      $r = array();
      $__r = parent::use_result();
      while($_r = $__r->fetch_assoc()) $r[] = $_r;
      return $r;
    }
  }
}

new Stratum();
