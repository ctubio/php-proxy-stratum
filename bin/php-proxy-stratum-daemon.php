#!/usr/bin/php
<?php
class Stratum {
  private $s = array();
  private $p = array();
  private $o = array();

  public function __construct() {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $l = React\EventLoop\Factory::create();
    $s = new React\Socket\Server($l);
    $s->on('connection', function ($c) use ($l) {
      if (($k = count($this->s))<9999) {
        $this->p[] = NULL;
        $this->o[] = new U();
        $this->s[] = $c;
        $this->l('connected, total: '.($k+1).'.');
      } else $this->l('ignored, too many.');
      $c->on('close', function ($c) {
        $this->k(array_search($c, $this->s), 'gone');
      });
      $c->on('data', function ($d, $c) use ($l) {
        $this->x($d, $c, $l);
      });
    });
    $s->listen(3333, 0);
    $w = new React\Socket\Server($l);
    $w->on('connection', function ($c) use ($l) {
      $c->on('data', function ($d, $c) use ($l) {
        if ($c->getRemoteAddress()=='127.0.0.1')
          $this->h($d, $c, $l);
      });
    });
    $w->listen(8033, 0);
    set_time_limit(0);
    $l->run();
  }

  private function x($_d, $_r, $l) {
    $k = array_search($_r, $this->s);
    $_d = $this->o[$k]->d($_d);
    if ($_d === FALSE || !($d = json_decode($_d, TRUE))) $this->k($k, 'lost');
    else {
      $this->l(((int)$k).' says: '.$_d);
      if (isset($d['method'])) {
        if ($d['method'] == 'mining.subscribe') {
          $this->l(((int)$k).' gets subscription '.$d['id'].'.');
          $this->s[$k]->write('{"id":'.$d['id'].',"result":[[["mining.set_difficulty","1"],["mining.notify","1"]],"00",4],"error":null}'."\n");
          if (!$this->p[$k]) {
            $this->o[$k]->v = (isset($d['params']) && isset($d['params'][0]) && $d['params'][0]) ? $d['params'][0] : 'unknown';
            $this->o[$k]->s = array($d['id'], $_d);
          }
        } else if ($d['method'] == 'mining.authorize') {
          $this->l(((int)$k).' gets authorization '.$d['id'].'.');
          $this->s[$k]->write('{"error":null,"id":'.$d['id'].',"result":true}'."\n");
          if (isset($d['params']) && isset($d['params'][0]) && $d['params'][0]) {
            $this->o[$k]->u = $d['params'][0];
            $this->c($l, $k);
          } else $this->k($k, 'unkown');
        } else if ($this->p[$k]) {
          if(isset($d['method']) && $d['method']=='mining.submit' && isset($d['params']) && isset($d['params'][0]) and $d['params'][0]==$this->o[$k]->P['user'])
            $this->o[$k]->t(-$d['id']);
          $this->l('server '.$k.' gets '.$_d);
          $this->p[$k]->write($_d);
        } else $this->k($k, 'lost server');
      } else $this->k($k, 'said garbage');
    }
  }

  private function c($l, $k, $o = 0) {
    if ($this->p[$k]) $this->p[$k]->end();
    $this->p[$k] = NULL;
    $a = $this->o[$k]->c();
    $this->o[$k]->P = $a[$o];
    $n = isset($a[$o+1]) ? $o+1 : 0;
    $x = new React\Dns\Resolver\Factory();
    $c = new React\SocketClient\Connector($l, $x->createCached('8.8.8.8', $l));
    $c->create($this->o[$k]->P['url'], $this->o[$k]->P['port'])->then(function ($s) use ($k) {
      $this->p[$k] = $s;
      if (isset($this->s[$k]) && $this->o[$k]->s) {
        $this->p[$k]->write($this->o[$k]->s[1]);
        $this->p[$k]->write('{"id": '.($this->o[$k]->s[0]+1).', "method": "mining.authorize", "params": ["'.$this->o[$k]->P['user'].'", "'.$this->o[$k]->P['pass'].'"]}'."\n");
        $this->o[$k]->I = array();
        $this->l(((int)$k).' connected to '.$this->o[$k]->P['url'].':'.$this->o[$k]->P['port'].' as '.$this->o[$k]->P['user'].'.');
        $s->on('close', function ($s) {
          $this->k(array_search($s, $this->p), 'server gone');
        });
        $s->on('data', function ($__d, $s) {
          $k = array_search($s, $this->p);
          if (isset($this->s[$k])) {
            foreach(array_filter(explode(PHP_EOL, $__d)) as $_d) {
              if ($_d === FALSE || !($d = json_decode($_d, TRUE))) $this->k($k, 'server lost');
              if (isset($d['id']) && $d['id'] && $d['id'] == $this->o[$k]->s[0]) {
                if (isset($d['result']) && isset($d['result'][1]) && $d['result'][1]) {
                  $this->l(((int)$k).' gets extranonce ["'.$d['result'][1].'", '.$d['result'][2].'].');
                  $this->s[$k]->write('{"params":["'.$d['result'][1].'",'.$d['result'][2].'],"method":"mining.set_extranonce","id":null}'."\n");
                }
              } else if(!isset($d['method']) || $d['method']!='client.show_message') {
                $this->l(((int)$k).' gets: '.$_d);
                $this->s[$k]->write($_d."\n");
              }
              if (isset($d['method']) && $d['method']=='mining.set_difficulty' && isset($d['params']) && isset($d['params'][0]))
                $this->o[$k]->F = $d['params'][0];
              if (isset($d['result']) && $d['result']===TRUE && isset($d['id']) && $d['id'])
                $this->o[$k]->t($d['id']);
            }
          } else $this->k($k, 'lost before server');
        });
      } else $this->k($k, 'miss subscribe');
    }, function() use ($k, $n) {
      if ($n) $this->c($l, $k, $n);
      else $this->k($k, 'lost pools');
    });
  }

  private function k($k, $m) {
    unset($this->s[$k], $this->p[$k], $this->o[$k]);
    $this->s = array_values($this->s);
    $this->p = array_values($this->p);
    $this->o = array_values($this->o);
    $this->l(((int)$k).' '.$m.', killed.');
  }

  private function h($h, $c, $l) {
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
                '2min avg'=>$o->h()
              );
            else $d['result'][] = $k.' is zombie.';
          }
          break;
        case 'switchpool':
          foreach($this->o as $k => $o) {
            if (!$o || is_null($o->u) || $o->u!=$h['params'][0]) continue;
            $this->c($l, $k, $h['params'][1]);
          }
          break;
      }
    $c->write(json_encode($d)."\n");
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
    'id' => 0,
    'url'  => 'sha256.eu.nicehash.com',
    'port'=> 3334,
    'user'=> '1DiS2bVRR35jwxmbSMmtqkobRmTiD9Tevv.0',
    'pass'=> 'x'
  );

  public function c() {
    if (is_null($this->u)) return array();
    $m = new M();
    $q = $m->q('SELECT pools.id, pools.url, pools.port, pools.user, pools.pass FROM pools JOIN workers ON workers.worker = "%s" WHERE pools.worker_id = workers.id;', $this->u);
    array_push($q, $this->P);
    return $q;
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
      return parent::use_result()->fetch_all(MYSQLI_ASSOC);
    }
  }
}

new Stratum();
