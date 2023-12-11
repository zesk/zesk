#!/usr/bin/env bash
#
# Zesk Live Version
#
# Copyright &copy; 2023 Market Acumen, Inc.
#
set -e
cd "$(dirname "${BASH_SOURCE[0]}")/../.."
./bin/build/pipeline/github-version-live.sh zesk/zesk
