# F3 PowerDNS Web Interface

Web interface for the PowerDNS server (with a MySQL backend) built using the Fat Free Framework (F3). Allows you to administrate your PowerDNS install via a pretty web front end, supports (hopefully) International Domains as well as regular domains.

## What's It All About?

[PowerDNS](https://powerdns.com/) is a great DNS server available for linux, it allows you to use various Backends instead of just a normal bind style zone file. I have been using it for a while and decided to write my own web front end for administering my domains, the original one I wrote was functional but horrific and the code for that will never see the light of day.

Fat Free Framework is a nifty little framework that I fell in love with a few years ago, easy to learn, simple to use. Perfect for an idiot like me..

The aim is to provide a fully functional web interface that you can just drop onto your server and easily allow you and your customers to do all your DNSy stuff.

## Todo

Pretty much everything, currently only part of the DNS Admin stuff works, it is a work in progress and there is currently no install guide! Check back soon.

#### Site Admin
- [x] ~~Add Domains~~
- [x] ~~Edit Domains~~
- [x] ~~Delete Domains~~
- [ ] Add Users
- [x] ~~Edit Users~~
- [x] ~~Delete Users~~
- [ ] Assign Domain to different user

#### Domain Admin
- [ ] Add Domains
- [ ] Edit Domains
- [ ] Delete Domains
- [ ] Add Users
- [ ] Edit Users
- [ ] Delete Users

#### Domain User
- [ ] Edit Domain

#### Other Bits
- [ ] Password Reset
- [ ] PDNS Stats
- [ ] Domain and Record Limits (Per User and/or Per Domain)
- [ ] Tidy Up Plugins Folder

## Prerequisities

You will need to make sure you have installed and working the following

* [PowerDNS](https://powerdns.com/) (with MySQL backend)
* [Nginx](https://nginx.org/) (or [Apache](https://httpd.apache.org/)) webserver
* [MySQL](https://dev.mysql.com/downloads/)
* [PHP 7](https://php.net)

Getting these installed and working is beyond the scope of this readme, there are enough tutorials floating around the web (hint, [Google](https://www.google.co.uk) is your friend)

## Installing

Read the INSTALL.md in the install folder (once I have written it.)

## Built With

**PHP**
* [Fat Free Framework](https://fatfreeframework.com)

**JS**
* [JQuery](https://jquery.com/)
* [PNotify](https://sciactive.com/pnotify/)
* [DataTables](https://datatables.net)
* [Select2](select2.github.io)
* [X-Editable](https://vitalets.github.io/x-editable/)


**HTML/CSS**
* [Bootstrap](https://getbootstrap.com)
* [Argon](https://creative-tim.com/)
* [Font Awesome](http://fontawesome.io/)
* [Select2 Bootstrap 4 Theme](https://github.com/ttskch/select2-bootstrap4-theme)
* [Bootstrap Buttons](https://github.com/haubek/bootstrap4c-buttons)

## Credit To

* **Lukas Metzger** - Totally ripped some of his Javascript from his [PDNS Manager](https://pdnsmanager.lmitsystems.de/). 

## Authors

* **Mr Sleeps** - [MrSleeps](https://github.com/MrSleeps)

## License

This project is licensed under the GNU GENERAL PUBLIC LICENSE - see the [LICENSE.md](LICENSE.md) file for details
