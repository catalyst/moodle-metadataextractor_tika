# Tika metadata extractor

This is a Moodle Metadata API (`tool_metadata`) subplugin designed to extract metadata from Moodle resources using the [Apache Tika toolkit.](https://tika.apache.org/index.html) 

## Installation

### Dependencies

__tool_metadata__: As a subplugin, you must have the parent [Metadata API plugin](https://github.com/catalyst/moodle-tool_metadata) for this plugin to work.

__local_aws__: The AWS SDK wrapped up in the [local_aws plugin](https://github.com/catalyst/moodle-local_aws) includes required libraries (such as Guzzle for HTTP requests) and is planned to be utilised for support of future planned capabilities, including AWS Lambda support.

### Requirements

For this plugin to function, you require the Apache Tika toolkit configured in some form as a service to conduct metadata and content extraction.

There are currently two supported configurations for your Apache Tika service in order to use this plugin, they are a local install of the Tika application, or a dedicated Tika server.

#### Local Tika Application

For a local Tika application, you are dependent on having Java 8 (or higher) installed and working on your Moodle server, you can find instruction on how to install Java on the [Java website.](https://www.java.com/en/download/)

Additionally, the Apache Tika application is required to conduct the metadata parsing, you can download this from [downloads section of Apache Tika website.](https://tika.apache.org/download.html)

___Note:__ Please ensure you download the Tika app and not src, server or eval. The file name should look something like `tika-app-X.XX.jar`_

Alternatively you can build the application yourself using the Maven 2 build system and the [source code from GitHub.](https://github.com/apache/tika/)

#### Tika Server

You can run Tika on a server using a Java install and a `tika-server-X.XX.jar` version of some description, as a normal web server application.

In general, Tika is accessed over port 9998.

The easiest way to get a Tika server up and running is the Docker image 'docker-tikaserver' which can be [cloned from github](https://github.com/LogicalSpark/docker-tikaserver) or directly pulled from the docker hub via `docker pull logicalspark/docker-tikaserver`

### Setup

Once you have either Java and the tika-app jar installed on your server, or access to a dedicated Tika server, clone this repo into the `/tool/metadata/extractor/tika` path of your Moodle codebase and login as Admin to install the plugin.

Once installed you need to navigate on your Moodle site to `Site administration > Plugin > Metadata >  Manage metadata extractor plugins` and enable the Tika subplugin. Then click on the `Settings` link.

Choose the correct 'Service type' according to whether you are using a local install or Tika server then Save changes.

Following this, fill out either the path to your local install of the Tika App, or the hostname and port of your Tika server and Save changes again. 

The plugin should now be ready to extract metadata from your Moodle files, you can test this by using the Metadata API CLI tool, and the file id of a Moodle file.

### Metadata Extraction

File metadata extraction is controlled by a scheduled task `\tool_metadata\task\process_file_extractions_task` which will mark files for asynchronous extraction if they have not been extracted yet, or the file has been updated.

All metadata is stored in Moodle database table `{metadataextractor_tika}`.

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