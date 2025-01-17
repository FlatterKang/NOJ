# NOJ (CodeMaster)

![NOJ](/noj.png)

NOJ's another online judge platform, stands for NJUPT Online Judge. It's written in PHP, GO, Python and other function-supporting languages.

![License](https://img.shields.io/github/license/ZsgsDesign/NOJ.svg?style=flat-square)
[![Scrutinizer Code Quality](https://img.shields.io/scrutinizer/g/ZsgsDesign/NOJ.svg?style=flat-square)](https://scrutinizer-ci.com/g/ZsgsDesign/NOJ/?branch=master)
[![FOSSA Status](https://img.shields.io/badge/license%20scan-passing-green.svg?style=flat-square)](https://app.fossa.io/projects/git%2Bgithub.com%2FZsgsDesign%2FCodeMaster?ref=badge_shield)
[![Code Coverage](https://img.shields.io/scrutinizer/coverage/g/ZsgsDesign/NOJ/master.svg?style=flat-square)](https://scrutinizer-ci.com/g/ZsgsDesign/NOJ/?branch=master)
[![Build Status](https://img.shields.io/scrutinizer/build/g/ZsgsDesign/NOJ.svg?style=flat-square)](https://scrutinizer-ci.com/g/ZsgsDesign/NOJ/build-status/master)
![GitHub repo size](https://img.shields.io/github/repo-size/ZsgsDesign/NOJ.svg?style=flat-square)
![Stars](https://img.shields.io/github/stars/zsgsdesign/noj.svg?style=flat-square)
![Forks](https://img.shields.io/github/forks/zsgsdesign/noj.svg?style=flat-square)

### Community Contributors

| [<img src="https://github.com/ZsgsDesign.png?s=64" width="100px;"/><br /><sub><b>John Zhang</b></sub>](https://github.com/ZsgsDesign)<br />**Leader**   | [<img src="https://github.com/DavidDiao.png?s=64" width="100px;"/><br /><sub><b>David Diao</b></sub>](https://github.com/DavidDiao)<br />**Deaputy**<br />  | [<img src="https://github.com/pikanglong.png?s=64" width="100px;"/><br /><sub><b>Cone Pi</b></sub>](https://github.com/pikanglong)<br />**BackEnd**  | [<img src="https://github.com/X3ZvaWQ.png?s=64" width="100px;"/><br /><sub><b>X3ZvaWQ</b></sub>](https://github.com/X3ZvaWQ)<br />**BackEnd** | [<img src="https://github.com/Brethland.png?s=64" width="100px;"/><br /><sub><b>Brethland</b></sub>](https://github.com/Brethland)<br />**VirtualJudge** | [<img src="https://github.com/goufaan.png?s=64" width="100px;"/><br /><sub><b>goufaan</b></sub>](https://github.com/goufaan)<br />**FrontEnd**   |  [<img src="https://github.com/ChenKS12138.png?s=64" width="100px;"/><br /><sub><b>ChenKS12138</b></sub>](https://github.com/ChenKS12138)<br />**FrontEnd** |
| :---: | :---: | :---: | :---: | :---: | :---: | :---: |
| [<img src="https://github.com/Rp12138.png?s=64" width="100px;"/><br /><sub><b>Rp12138</b></sub>](https://github.com/Rp12138)<br />**BackEnd**   |

## Installation

CentOS will be recommended for hosting NOJ, but all major operating systems are theoretically supported.

Till now, NOJ have been successfully deployed to the following systems:

- Ubuntu 16.04.3 LTS
- CentOS Linux release 7.6.1810 (Core)
- Windows 10 Professional 10.0.17134 Build 17134

Here is detailed step about deploying NOJ:

1. You need to have a server and installed the following:
    - [PHP 7.3(Recommend 7.3.4)](http://php.net/downloads.php)
    - [Composer 1.8.5(Recommend 1.8.5)](https://getcomposer.org)
    - [MySQL 5.5.3(Recommend 8.0)](https://www.mysql.com/)
    - [Redis 3.2.1(Recommend 5.0)](https://redis.io)

2. Clone NOJ to your website folder;

```
cd /path-to-noj/
git clone https://github.com/ZsgsDesign/NOJ ./
```

3. Change your website root to `public` folder and then, if there is a `open_basedir` restriction, remove it;

4. Now run the following commands at the root folder of NOJ;

```
composer install
```

> Notice: you may find this step(or others) fails with message like "func() has been disabled for security reasons", it means you need to remove restrictions on those functions, basically Laravel and Composer require proc_open and proc_get_status to work properly.

5. Almost done, you still got to modify a few folders and give them permission to write;

```
chmod -R 775 storage/
chmod -R 775 bootstrap/
chmod -R 775 app/Http/Controllers/VirtualCrawler/
chmod -R 775 app/Http/Controllers/VirtualJudge/
```

6. OK, right now we still need to configure environment, a typical `.env` just like the `.env.example`, you simply need to type the following codes;

```
cp .env.example .env
vim .env
```

After editing `.env`, use this to generate a new key:

```
php artisan key:generate
```

7. Now, we need to configure the database, thankfully Laravel have migration already;

```
php artisan migrate
```

8. Lastly, we need to configure the virtual judger and online judger;

```
crontab -e
* * * * * php /path-to-noj/artisan schedule:run

php artisan queue:work --queue=noj,codeforces,contesthunter,poj,vijos,pta,uva,hdu,uvalive
```

9. NOJ's up-and-running, enjoy!

## Supported Feature

- [X] Basic Home Page
- [X] General
    - [X] Cron Support
    - [X] Queue Support
    - [X] Notification Support
        - [X] Browser
        - [X] Mail
    - [X] System Version
    - [x] System Bug Report
- [X] User System
    - [X] User Login
    - [X] User Register
    - [X] User Password Retrive
    - [X] User Email Verify
    - [X] DashBoard
        - [X] Statistics
        - [X] Activities
        - [X] Profile
    - [X] Settings
- [ ] Search System
    - [X] Basic Redirect
    - [ ] Problem Search
    - [ ] Status Search
    - [ ] Group Search
    - [ ] Contest Search
    - [ ] OnmiSearch Support
- [ ] Problem System
    - [X] Problem List
    - [X] Problem Tag
    - [X] Problem Filter
    - [X] Problem Details
    - [X] Problem Solution
    - [ ] Problem Discussion
    - [X] Problem Submit
        - [X] Problem Immersive Mode
        - [X] Problem Editor
        - [X] Problem Submit History
        - [X] Problem Compiler List
        - [X] Problem Status Bar
        - [X] Problem Virtual Judge
            - [X] Submit to VJ
                - [X] CodeForces
                - [X] UVa
                - [X] UVa Live
                - [ ] SPOJ
                - [X] HDU
                - [X] Contest Hunter
                - [X] POJ
                - [X] Vijos
                - [X] PTA
            - [X] Retrive Status
        - [X] Problem Online Judge
            - [X] Judge Server
            - [X] Judger
            - [X] Submit to OJ
            - [X] Retrive Status
- [X] Status System
    - [X] Status List
    - [X] Status Filter
    - [X] Status Details
        - [X] Syntax Highlight
        - [X] Verdict
        - [X] Code Download
        - [X] Code Share
- [X] Ranking System
    - [X] Casual Ranking List
    - [X] Professional Ranking List
- [ ] Contest System
    - [X] Contest List
    - [ ] Contest Tag
    - [X] Contest Ranking
    - [X] Contest Filter
    - [X] Contest Details
        - [x] Contest Registration
        - [X] Contest Temp Account
        - [X] Leader Board
        - [X] Contest CountDown
        - [X] Contest Problem List
        - [X] Contest Problem Details
        - [X] Contest Announcements
        - [X] Contest Admin Portal
            - [X] Account Generate
            - [X] Judge Status
            - [X] Issue Announcements
        - [X] In-Contest Problem Switch
        - [X] Problem Temp Block
    - [X] Contest Ranking System
    - [ ] Contest Clone
    - [ ] Contest Virtual Participate
- [ ] Group System
    - [X] Group List
    - [X] Group Details
        - [X] Group Timeline
        - [ ] Group Member Management
            - [X] Invite
            - [X] Remove Members
            - [X] Approve Requests
            - [ ] Sub Group
        - [X] Group Profile
        - [X] Group General Info
        - [ ] Group Functions
            - [X] Group Announcement
            - [ ] Group Posts
            - [ ] Group Contests
                - [X] Group-wide Contests
                - [ ] Site-wide Contests
            - [ ] Group Own ProblemSet
                - [ ] Add Problem
            - [X] Group Settings
- [ ] Admin Portal
    - [X] User Management
    - [X] Contest Management
    - [X] Problem Management


## Credit

[Laravel](https://github.com/laravel/laravel)

[Markdown](https://github.com/GrahamCampbell/Laravel-Markdown)

[Simple-HTML-Dom](https://github.com/Kub-AT/php-simple-html-dom-parser)

[JudgeServer](https://github.com/MarkLux/JudgeServer)

[HTML Purifier](https://github.com/mewebstudio/Purifier)

See `composer.json` or [Dependency List](https://app.fossa.com/attribution/263d9a48-87a3-4043-b6f4-42e0f5755351) for more info.

## License
[![FOSSA Status](https://app.fossa.io/api/projects/git%2Bgithub.com%2FZsgsDesign%2FCodeMaster.svg?type=large)](https://app.fossa.io/projects/git%2Bgithub.com%2FZsgsDesign%2FCodeMaster?ref=badge_large)
