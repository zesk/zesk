#!/bin/bash
#
# Installing base packages
#
set -e

export DEBIAN_FRONTEND=noninteractive

apt-get -y update
apt-get -y dist-upgrade
apt-get install -y apt-utils 2> /dev/null
apt-get install -y procps
apt-get install -y bash software-properties-common net-tools > /dev/null

# Development stuff
apt-get install -y vim manpages git curl strace > /dev/null

if [ -n "$@" ]; then
  apt-get install -y "$@"
fi
