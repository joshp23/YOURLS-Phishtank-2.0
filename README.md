# YOURLS-Phishtank-2.0

#### An anti-spam blacklist-check for YOURLS using the Phishtank API

Phishtank 2.0 for YOURLS is a functiolnal rewrite of the old phishtank plugin, which seems abondoned. There are some noticable additions to the code.

### Features
1. Checks URL submissions against [Phishtanks's](https://www.phishtank.com/) blacklist, and blocks any blacklisted submissions.
2. Will optionally re-check old links when they are clicked to see if they have been blacklisted since 1st submitted.
3. Can delete or preserve and intercept old links that fail recheck.
4. You can customize the intercept page, or use your own url.
5. You can add your Phishtank API key, if you have one.
6. Uses base64 instead of urlencode to send url to Phishtank
7. Uses the YOURLS admin section for option setting. No config files.
8. Integrates with the [Compliance](https://github.com/joshp23/YOURLS-Compliance) flaglist to track links that have "gone bad"

#### NOTE: The options are idiot-proof. If options are never submitted, null values are accepted by the plugin to reflect positive default selections.

### Requirements
1. A working YOURLS installation
2. A Phishtank API key (optional, strongly reccomended if you anticipate high volume)

### Installation
1. Download, extract, and copy the phishtank-2.0 folder to your YOURLS/user/plugins/ folder
2. Enable Phishtank 2.0 in the "Manage Plugins" page under the Admin section of YOURLS
3. Visit the new Options page for Phishtank, enter in your API key, make your choices regarding old links. (default values are fine)
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
