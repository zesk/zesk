#!/bin/bash
echo $(basename "$0") is deprecated 2019-03. Use file-trigger.sh instead.
export HERE="$(cd $(dirname "$BASH_SOURCE"); pwd)"
$HERE/file-trigger.sh $*
