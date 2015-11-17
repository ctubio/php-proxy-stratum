# php-proxy-stratum
Transparent **stratum+tcp** proxy between miners and pools, with minimal web interface.
```
$ php bin/php-proxy-stratum-daemon.php
```
And feel free to connect your miners to your port ```3333```, also you can configure your webserver with ```pub/``` as the document root and ```php-proxy-stratum.php``` as the index file.

Currently the web interface have auto refresh and a snapshot may look like (yes, is just json output):
```
2015-11-17 01:46:12
wtfisconnected
{
    "result": [
        {
            "user": "analpaper.2",
            "version": "cgminer\/4.8.0",
            "since": "2015-11-17T01:36:26+0000",
            "pool": [
                "eu.stratum.bitcoin.cz",
                3333,
                "analpaper.0",
                "x"
            ],
            "pending": [],
            "diff": 2940,
            "5min GHps avg": "5.440,40"
        },
        {
            "user": "analpaper.3",
            "version": "cgminer\/4.8.0",
            "since": "2015-11-17T01:36:10+0000",
            "pool": [
                "eu.stratum.bitcoin.cz",
                3333,
                "analpaper.0",
                "x"
            ],
            "pending": [],
            "diff": 903,
            "5min GHps avg": "1.194,73"
        }
    ]
}
```

Enjoy:exclamation:

#### Very special thanks to:
- https://github.com/slush0
- https://github.com/ckolivas/cgminer


###### Donations
nope. but you can donate to your favorite developer today! (or tomorrow!)
