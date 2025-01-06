#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
#
# Cleanup
#
set -eou pipefail

export DEBIAN_FRONTEND=noninteractive
apt-get -y autoclean
apt-get -y autoremove
