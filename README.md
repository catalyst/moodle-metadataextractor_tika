# Tika metadata extractor

## Summary

This is a Moodle Metadata API (`tool_metadata`) subplugin designed to extract metadata from Moodle resources using the [Apache Tika toolkit.](https://tika.apache.org/index.html)

### Data Extracted

The base metadata extracted by this plugin for supported resources is loosely based on a subset of the [Dublin Core](https://dublincore.org/) recommended properties in the `/elements/1.1/` namespace.

Supplementary data is based on extracting as much meaningful metadata as the capabilities of Apache Tika allow for based on the mimetype of the content for which metadata is being extracted. 

While all endeavours have been made to reduce the amount of `null` values stored during metadata extraction, often some blank metadata fields are unavoidable, whether due to the application which created content not adhering to metadata standards, or creator of content not providing metadata.

### Supported Resources

Current supported resources for metadata extraction are:
- file: `stored_file` resources,
- url: `mod_url` resources.

## Installation

### Dependencies

__tool_metadata__: As a subplugin, you must have the parent [Metadata API plugin](https://github.com/catalyst/moodle-tool_metadata) for this plugin to work.

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

File metadata extraction is controlled by a scheduled task `\tool_metadata\task\process_file_extractions_task` which will mark files for asynchronous extraction if they have not been extracted yet.

URL metadata extraction is controlled by a scheduled task `\tool_metadata\task\process_url_extractions_task` which will mark URLs for asynchronous extraction if they have not been extracted yet, or the URL has been updated.

Base metadata is stored in Moodle database table `{metadataextractor_tika}`, supplementary metadata based on the media type (determined by the mimetype of the resource content) is stored in various supplementary tables named `{tika_<FILETYPE>_metadata}` where filetype is the type of file determined by the mimetype, currently that includes:

- document: MS Word, Libre Office Writer and other word processing documents,
- pdf: Adobe Portable Document Format content,
- image: Most non-moving image file types,
- audio: Stand alone audio files (not including tracks in video content),
- video: Most video format media (not including streams or adaptive media),
- spreadsheet: MS Excel, Libre Office Calc, plain text CSV and other spreadsheeting application format files,
- presentation: MS Powerpoint, Libre Office Impress and other presentation application format files.

Any content which doesn't fit into one of these file types will only have core metadata extracted and no supplementary record.

Supplementary records are joined to their base records via a unique SHA1 hash of the resource.

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