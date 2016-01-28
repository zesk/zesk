# FreeBSD
pkg_add -r curl
curl -O --basic --user anonymous:zeskquality http://code.marketacumen.com/zesk/trunk/modules/server/bin/seed.sh; chmod +x seed.sh; ./seed.sh -y

# Linux
apt-get install -y curl; 
curl -O --basic --user anonymous:zeskquality http://code.marketacumen.com/zesk/trunk/modules/server/bin/seed.sh; chmod +x seed.sh; ./seed.sh -y
