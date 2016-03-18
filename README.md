# php-proxy-stratum
ReactPHP **stratum+tcp** proxy between miners and pools with database and minimal web interface.

Basically it may be run with the following command: 
```
$ php bin/php-proxy-stratum-daemon.php
```
Or use the init script:
```
 $ sudo cp dist/init.sh /etc/init.d/php-proxy-stratum
 $ sudo chmod +x /etc/init.d/php-proxy-stratum
 $ sudo update-rc.d php-proxy-stratum defaults
 $ sudo update-rc.d php-proxy-stratum enable
 $ sudo service php-proxy-stratum start
```
And feel free to connect your miners at port ```3333```, also you can configure your webserver with ```pub/``` as the document root and ```php-proxy-stratum.php``` as the index file.

Currently the web interface features auto refresh and pool switch; a snapshot may look like (yes, is just json output):
```
2015-12-10 17:32:52
wtfisconnected

{
    "result": [
        {
            "key": 27,
            "user": "analpaper.2",
            "version": "cgminer\/4.8.0",
            "since": "2015-12-10T16:56:22+0000",
            "last": "2015-12-10T17:32:46+0000",
            "pool": {
                "id": 0,
                "url": "stratum.kano.is",
                "port": 3333,
                "user": "analpaper.0",
                "pass": "x"
            },
            "pending": [],
            "diff": 3801,
            "2min avg": "5,71 TH\/s"
        },
        {
            "key": 35,
            "user": "analpaper.3",
            "version": "cgminer\/4.8.0",
            "since": "2015-12-10T16:56:24+0000",
            "last": "2015-12-10T17:32:47+0000",
            "pool": {
                "id": "1",
                "url": "stratum.kano.is",
                "port": "3333",
                "user": "analpaper.0",
                "pass": "x"
            },
            "pending": [],
            "diff": 1042,
            "2min avg": "1,23 TH\/s"
        }
    ],
    "pid": 22559,
    "mem": "1.5mb"
}
```

Enjoy:exclamation:

#### Very special thanks to:
- https://github.com/reactphp
- https://github.com/slush0
- https://github.com/ckolivas/cgminer
- ~~https://www.btcguild.com/new_protocol.php~~ http://archive.is/CH9a0
- https://en.bitcoin.it/wiki/Stratum_mining_protocol


###### Donations
nope. but you can donate to your favorite developer today! (or tomorrow!)
