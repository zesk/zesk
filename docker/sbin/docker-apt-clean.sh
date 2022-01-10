#
# Cleanup
#
set -e

export DEBIAN_FRONTEND=noninteractive

apt-get -y autoclean
apt-get -y autoremove
