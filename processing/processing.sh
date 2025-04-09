#!/bin/bash

rm -R ./modifiedfiles/*

php ./processing.php

cd ./modifiedfiles
find . -type d -empty -delete

