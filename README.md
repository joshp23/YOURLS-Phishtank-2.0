# YOURLS-Phishtank-2.0

#### An anti-spam blacklist-check for YOURLS using the Phishtank API

Phishtank 2.0 for YOURLS is a functiolnal rewrite of the old phishtank plugin, which seems abondoned. There are some noticable additions to the code.

### Features
1. This plugin will check URL submissions against [Phishtanks's](https://www.phishtank.com/) blacklist, and it will block any blacklisted submissions.
2. This plugin will re-check old links when they are clicked to see if they have been blacklisted since 1st submitted.
3. You can set an option in the admin section to keep old links that are found to be bad, redirecting the user to a warning page, or you can just have bad links deleted when discovered in the blacklist.
4. You can customize the danger.php to offer any warning you see fit.
5. You can set an option in the admin section to add your Phishtank API key, if you have one.
6. Uses base64 instead of urlencode to send url to Phishtank

### Requirements
1. A working YOURLS installation
2. A Phishtank API key (optional, strongly reccomended if you anticipate high volume)

### Installation
1. Download, extract, and copy YOURLS-Phishtank-2.0 to your YOURLS/user/plugins/ folder
2. Enable Phishtank 2.0 in the "Manage Plugins" page under the Admin section of YOURLS
3. Visit the new Options page for Phishtank, enter in your API key, make your choice regarding dirty old links.
4. Have Fun!

### Credits
1. The original [phishtank plugin](http://pastie.org/1430803) by [Pau Oliva Fora](http://pof.eslack.org/)
2. OZH's excellent [AntiSpam plugin](https://github.com/YOURLS/antispam)

### Disclaimer
This plugin is offered "as is", and may or may not work for you. Give it a try, and have fun!

===========================

    Copyright (C) 2016 Josh Panter

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
