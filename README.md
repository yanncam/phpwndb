# 💡 phpwndb : search credentials leaked on pwndb database with variation

PHPwnDB is an adaptation of tools like [pwndb.py](https://github.com/davidtavarez/pwndb) in PHP for using it simply via a web browser.
PHPwnDB permits search based on `domain.tld`, `username`, `firstname lastname` and the use of **wildcard**.
Results can be filtered to produce instant wordlists *read-to-use* through hashs cracker tools or Burp Intruder.

## 🔍 How it works?

PHPwnDB use `tor` to interact with `http://pwndb2am4tzkvold.onion`. So before use PHPwnDB, the **Tor** service must be running as a local proxy (default : `socks5h://127.0.0.1:9050`).

PHPwnDB allows an auditor to :

- Search credentials from a `domain.tld` to retrieve leaks matching emails like `xxx@domain.tld`;
- Search credentials from a specific login or email, like `bill.gates` or `bgates@microsoft.com`;
- Search credentials from a specific identity (first name + last name), by computing permutation, login generation and others combinaisons.

Wildcard can be used like : `_` to indicate any character. So it possible to make search like : 
- `bill_gates` to find `bill.gates, bill-gates, bill0gates, etc.`;
- `bgates_, bgates__, bgates___` to find `bgates0, bgates10, bgates999, bgatesadm, etc.`

All results of PHPwnDB are displayed in a textarea whick can be easyly filtered to:
- Display only **logins** (part before any `@`);
- Display only **domains** (part after any `@`);
- Display only **passwords** leaked (part after `:`);
- Or any combination of these filter, which permit a quick generation of a wordlist `username:password` or `username@domain.tld:password` or `username` or `username@domain.tld` or `password` to use it through `hashcat` or the **Burp Intruder** during web security assessments.

## 🔨 Installation

```
## With a standard Kali Linux, tor is already installed. If not:
# sudo apt-get install tor
## Then run tor in a separated shell / service:
tor & # or through a new screen command
## once tor is started, the service listen on 127.0.0.1:9050
## Be sure you have Apache + PHP7 + Curl
# apt install libapache2-mod-php php-curl
# phpenmod curl
# service apache2 restart
## deploy PHPwnDB into Apache/PHP document root
cd /var/www/html/
git clone https://github.com/yanncam/phpwndb/
```

Then, just browse `http://SERVER/phpwndb/phpwndb.php`.

## 🔥 Demonstration / Example / How to use?

## 🧰 To go deeper...

PHPwnDB needs **Tor** deployed locally to access `http://pwndb2am4tzkvold.onion`. The PHP script proxyfies his request through the Tor proxy listening on `127.0.0.1:9050` by default.

DNS resolution of the domain `pwndb2am4tzkvold.onion` needs to be done through the Tor network to work. So the PHP script use the **Curl** PHP library with the `CURLOPT_PROXYTYPE`` define to `CURLPROXY_SOCKS5_HOSTNAME` (7). Plus, the scheme to reference the proxy target is explicitly defined to `socks5h://127.0.0.1:9050`. The `h` at the end of `sock5h` is needed to delegate DNS resolution to the proxy (Tor) itself (need PHP 7 at least with recent Curl module).

## 🍻 Credits

- GreetZ to all the Le££e team :)
