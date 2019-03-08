#!/bin/bash
echo $(basename "$0") is deprecated. Use file-trigger.sh
export HERE="$(cd $(dirname "$BASH_SOURCE"); pwd)"
$HERE/file-trigger.sh $*
