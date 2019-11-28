# Tika metadata extractor

This is a Moodle Metadata API (`tool_metadata`) subplugin designed to extract metadata from Moodle resources using the [Apache Tika toolkit.](https://tika.apache.org/index.html) 

## Installation

### Requirements

As a subplugin, you must have the parent [Metadata API plugin](https://github.com/catalyst/moodle-tool_metadata)  for this plugin to work.

This subplugin is dependent on having Java 8 (or higher) installed and working on your Moodle server, you can find instruction on how to install Java on the [Java website.](https://www.java.com/en/download/)

Additionally, the Apache Tika application is required to conduct the metadata parsing, you can download this from [downloads section of Apache Tika website.](https://tika.apache.org/download.html)

___Note:__ Please ensure you download the Tika app and not src, server or eval. The file name should look something like `tika-app-X.XX.jar`_

Alternatively you can build the application yourself using the Maven 2 build system and the [source code from GitHub.](https://github.com/apache/tika/)

### Setup

Once you have Java and the tika-app jar installed on your server, clone this repo into the `/tool/metadata/extractor/tika` path of your Moodle codebase and login as Admin to install the plugin.

Once installed you need to navigate on your Moodle site to `Site administration > Plugin > Metadata >  Manage metadata extractor plugins` and enable the Tika subplugin. Then click on the `Settings` link and set the path to your tika-app jar and Save changes. 

The plugin should now be ready to extract metadata from your Moodle files, you can test this by using the Metadata API CLI tool, and the file id of a Moodle file.

Example:
```bash
php admin/tool/metadata/cli/extractmetadata.php --fileid=45 --plugin=tika -j
```

## License ##

2019 Catalyst IT Australia

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <http://www.gnu.org/licenses/>.


This plugin was developed by Catalyst IT Australia:

https://www.catalyst-au.net/

<img alt="Catalyst IT" src="https://raw.githubusercontent.com/catalyst/moodle-local_smartmedia/master/pix/catalyst-logo.svg?sanitize=true" width="400">