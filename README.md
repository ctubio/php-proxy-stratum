# php-proxy-stratum
Transparent **stratum+tcp** proxy between miners and pools, with minimal web interface.
```
$ php bin/php-proxy-stratum-daemon.php
```
And feel free to connect your miners to your port ```3333```, also you can configure your webserver with ```pub/``` as the document root and ```php-proxy-stratum.php``` as the index file.

Currently the web interface have auto refresh and a snapshot may look like (yes, is just json output):
```
2015-11-29 20:40:01
wtfisconnected

{
    "result": [
        {
            "user": "analpaper.3",
            "version": "cgminer\/4.8.0",
            "since": "2015-11-29T20:37:45+0000",
            "last": "2015-11-29T20:39:55+0000",
            "pool": {
                "id": "1",
                "url": "eu.stratum.bitcoin.cz",
                "port": "3333",
                "user": "analpaper.0",
                "pass": "x"
            },
            "pending": [],
            "diff": 624,
            "2min avg": "1,21 TH\/s"
        },
        {
            "user": "analpaper.2",
            "version": "cgminer\/4.8.0",
            "since": "2015-11-29T20:37:43+0000",
            "last": "2015-11-29T20:40:00+0000",
            "pool": {
                "id": 0,
                "url": "sha256.eu.nicehash.com",
                "port": 3334,
                "user": "1DiS2bVRR35jwxmbSMmtqkobRmTiD9Tevv.0",
                "pass": "x"
            },
            "pending": [],
            "diff": 1024,
            "2min avg": "4,91 TH\/s"
        }
    ]
}
```

Enjoy:exclamation:

#### Very special thanks to:
- https://github.com/slush0
- https://github.com/ckolivas/cgminer
- https://www.btcguild.com/new_protocol.php
- https://en.bitcoin.it/wiki/Stratum_mining_protocol


###### Donations
nope. but you can donate to your favorite developer today! (or tomorrow!)
