#!/usr/bin/env bash
#
# Copyright &copy; 2025 Market Acumen, Inc.
#
#
# Installing base packages
#
set -eou pipefail

export DEBIAN_FRONTEND=noninteractive

apt-get -y update
apt-get -y dist-upgrade
apt-get install -y apt-utils 2>/dev/null
apt-get install -y procps
apt-get install -y bash software-properties-common net-tools >/dev/null

# Development stuff
apt-get install -y vim manpages git curl strace >/dev/null

if [ "$#" -gt 0 ]; then
  apt-get install -y "$@"
fi
