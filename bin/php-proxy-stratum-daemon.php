#!/usr/bin/php
<?php
class Stratum {
  public function __construct() {
    require dirname(__DIR__) . '/vendor/autoload.php';
    $l = React\EventLoop\Factory::create();
    $l->o = array();
    $s = new React\Socket\Server($l);
    $s->on('connection', function ($c) use ($l) {
      if (($i = count($l->o))<9999) {
        $c->p = NULL;
        $c->u = new U();
        $l->o[$c->k=(int)$c->stream] = $c;
        $c->on('close', function ($c) use ($l) {
          $this->k($l, $c, 'gone');
        });
        $c->on('data', function ($d, $c) use ($l) {
          $this->x($d, $c, $l);
        });
        $this->e($c->k.' connected, total: '.($i+1).'.');
      } else {
        $c->close();
        $this->e('ignored, too many.');
      }
    });
    $s->listen(3333, 0);
    $w = new React\Socket\Server($l);
    $w->on('connection', function ($c) use ($l) {
      $c->on('data', function ($d, $c) use ($l) {
        if ($c->getRemoteAddress()=='127.0.0.1')
          $this->h($d, $c, $l);
        $c->end();
      });
    });
    $w->listen(8033, 0);
    set_time_limit(0);
    $l->run();
  }

  private function x($__d, $c, $l) {
    foreach(explode(PHP_EOL, $c->u->d(trim($__d))) as $_d) {
      $this->e($c->k.' says: '.$_d);
      if (!($d = json_decode($_d, TRUE))) { $this->k($l, $c, 'lost'); break; }
      else if (isset($d['method'])) {
        if ($d['method'] == 'mining.subscribe') {
          $this->e($c->k.' gets subscription '.$d['id'].'.');
          $c->write('{"id":'.$d['id'].',"result":[[["mining.set_difficulty","1"],["mining.notify","1"]],"00",4],"error":null}'."\n");
          if (!$c->p) {
            $c->u->v = (isset($d['params']) && isset($d['params'][0]) && $d['params'][0]) ? $d['params'][0] : 'unknown';
            $c->u->s = array($d['id'], $_d."\n");
          }
        } else if ($d['method'] == 'mining.authorize') {
          $this->e($c->k.' gets authorization '.$d['id'].'.');
          $c->write('{"error":null,"id":'.$d['id'].',"result":true}'."\n");
          if (isset($d['params']) && isset($d['params'][0]) && $d['params'][0]) {
            $c->u->u = $d['params'][0];
            $this->c($l, $c);
          } else { $this->k($l, $c, 'unkown'); break; }
        } else if ($c->p) {
          if(isset($d['method']) && $d['method']=='mining.submit' && isset($d['params']) && isset($d['params'][0]) and $d['params'][0]==$c->u->P['user'])
            $c->u->t(-$d['id']);
          $this->e('server '.$c->k.' gets '.$_d);
          $c->p->write($_d."\n");
        } else if (!isset($c->_p)) $this->c($l, $c);
      } else { $this->k($l, $c, 'said garbage'); break; }
    }
  }

  private function c($l, $c, $i = 0) {
    $c->_p = $i;
    if ($c->p) $c->p->close();
    else if ($c->isWritable()) {
      $a = $c->u->c();
      $c->u->P = $a[$i];
      $n = isset($a[$i+1]) ? $i+1 : 0;
      $x = new React\Dns\Resolver\Factory();
      $_c = new React\SocketClient\Connector($l, $x->createCached('8.8.8.8', $l));
      $_c->create($c->u->P['url'], $c->u->P['port'])->then(function ($s) use ($l, $c) {
        if ($c->isWritable()) {
          $c->p = $s;
          unset($c->_p);
          if ($c->u->s) {
            $c->p->write($c->u->s[1]);
            $c->p->write('{"id": '.($c->u->s[0]+1).', "method": "mining.authorize", "params": ["'.$c->u->P['user'].'", "'.$c->u->P['pass'].'"]}'."\n");
            $c->u->I = array();
            $this->e($c->k.' connected to '.$c->u->P['url'].':'.$c->u->P['port'].' as '.$c->u->P['user'].'.');
            $s->on('close', function ($s) use ($l, $c) {
              if (!isset($c->_p)) $this->c($l, $c);
              else {
                $i = $c->_p;
                unset($c->_p);
                if ($i>=0) $this->c($l, $c, $i);
              }
            });
            $s->on('data', function ($__d, $s) use ($l, $c) {
              if (isset($c) && $c) {
                if ($c->isWritable()) {
                  foreach(explode(PHP_EOL, trim($__d)) as $_d) {
                    if (!($d = json_decode($_d, TRUE))) $this->k($l, $c, 'server lost');
                    if (isset($d['id']) && $d['id'] && $d['id'] == $c->u->s[0]) {
                      if (isset($d['result']) && isset($d['result'][1]) && $d['result'][1]) {
                        $this->e($c->k.' gets extranonce ["'.$d['result'][1].'", '.$d['result'][2].'].');
                        $c->write('{"params":["'.$d['result'][1].'",'.$d['result'][2].'],"method":"mining.set_extranonce","id":null}'."\n");
                      }
                    } else if(!isset($d['method']) || $d['method']!='client.show_message') {
                      $this->e($c->k.' gets: '.$_d);
                      $c->write($_d."\n");
                    }
                    if (isset($d['method']) && $d['method']=='mining.set_difficulty' && isset($d['params']) && isset($d['params'][0]))
                      $c->u->F = $d['params'][0];
                    if (isset($d['result']) && $d['result']===TRUE && isset($d['id']) && $d['id'])
                      $c->u->t($d['id']);
                  }
                } else {
                  $c->_p = -1;
                  $s->close();
                  $this->e($c->k.' not writable, but got data.');
                }
              } else $this->k($l, $c, 'lost before server');
            });
          } else $this->k($l, $c, 'miss subscribe');
        } else {
          $c->_p = -1;
          $s->close();
          $this->e($c->k.' not writable, but connecting.');
        }
      }, function() use ($l, $c, $n) {
        if ($n) $this->c($l, $c, $n);
        else $this->k($l, $c, 'lost pools');
      });
    } else $this->e($c->k.' not writable, ignored.');
  }

  private function k($l, $c, $m) {
    if (!isset($l->o[$c->k])) $this->e($c->k.' does not compute (but '.$m.').') 
      $c->_p = -1;
      if ($c->p) $c->p->close();
      $c->close();
      unset($l->o[$k=$c->k]);
      $this->e($k.' '.$m.', killed.');
    
  }

  private function h($d, $c, $l) {
    $this->e('HTTP request '.$d);
    $r = array('result'=>NULL);
    if (($d = json_decode($d, TRUE)) && isset($d['method']))
      switch($d['method']) {
        case 'wtfisconnected':
          $r['pid'] = getmypid();
          $r['mem'] = memory_get_usage(true);
          $r['mem'] = @round($r['mem']/pow(1024,($i=floor(log($r['mem'],1024)))),2).strtr($i, array('b','kb','mb','gb'));
          foreach($l->o as $o) {
            if (!$o) continue;
            if (!is_null($o->u->u))
              $r['result'][] = array(
                'key'=>$o->k,
                'user'=>$o->u->u,
                'version'=>$o->u->v,
                'since'=>date(DATE_ISO8601, $o->u->T),
                'last'=>date(DATE_ISO8601, $o->u->L),
                'pool'=>$o->u->P,
                'pending'=>$o->u->I,
                'diff'=>$o->u->F,
                '2min avg'=>$o->u->h()
              );
            else $r['result'][] = $o->k.' is zombie.';
          }
          break;
        case 'switchpool':
          foreach($l->o as $o) {
            if (is_null($o->u->u) || $o->u->u!=$d['params'][0]) continue;
            $this->c($l, $o, $d['params'][1]);
          }
          break;
      }
    $c->write(json_encode($r)."\n");
  }

  private function e($m) { # return;
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
    'url'  => 'stratum.kano.is',
    'port'=> 3333,
    'user'=> 'analpaper.0',
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
