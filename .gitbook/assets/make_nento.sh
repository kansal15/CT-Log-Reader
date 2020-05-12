#make clean
find . -type d -name tmp -exec rm -rf {} +
#make image 

#pass KQr?2!Huf!w3o@!Y

#removeppp="-ppp-mod-pppoe -ip6tables -ppp -kmod-pppoe -kmod-pppox -kmod-ppp"
removeipv6="-ip6tables -odhcp6c -kmod-ip6tables -kmod-nf-ipt6 -kmod-nf-conntrack6 -kmod-ipv6 "
add_captureprobes="tcpdump-mini "
add_capturesessions="softflowd "
add_jsinject="luci-app-privoxy "
add_debug="procps-ng-watch"

add="zoneinfo-core grep dnsmasq-full -dnsmasq ipset sudo iwinfo coreutils-tac iptables-mod-conntrack-extra kmod-ifb tc iptables-mod-ipopt kmod-sched php7-cgi php7-mod-json php7-mod-ftp php7-mod-session php7-mod-calendar coreutils-timeout openssh-sftp-server luci-ssl luci-i18n-base-ru"
#us image he need
#make image PROFILE=archer-c7-v4 PACKAGES="php7-cgi $add $add_debug $add_captureprobes $add_capturesessions $removeppp $removeipv6 $removeusb" FILES=files_nento/
#make image PROFILE=tl-wr1043nd-v2 PACKAGES="php7-cgi $add $add_debug $add_captureprobes $add_capturesessions $removeppp $removeipv6 $removeusb" FILES=files_nento/
make image PROFILE=miwifi-mini PACKAGES="php7-cgi $add $add_debug $add_captureprobes $add_capturesessions $removeppp $removeipv6 $removeusb" FILES=files_nento/

