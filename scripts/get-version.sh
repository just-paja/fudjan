#!/bin/bash

if [ -z "$1" ]
then
	ref="master"
else
	ref="$1"
fi

ver=`git rev-list "$ref" | sort | wc -l`
subver=$((${ver}/100))
microver=$((${ver}-(${subver}*100)))
version="0.${subver}.${microver}"
version_full="${version}"

echo ${version_full}
