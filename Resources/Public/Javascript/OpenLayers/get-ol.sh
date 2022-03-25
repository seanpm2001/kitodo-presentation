#!/bin/bash

VERSION="6.12.0"

wget -O ol.zip "https://github.com/openlayers/openlayers/releases/download/v$VERSION/v$VERSION-dist.zip"
unzip -p ol.zip "v$VERSION-dist/ol.js" > openlayers.js
unzip -p ol.zip "v$VERSION-dist/ol.css" > openlayers.css
rm ol.zip
