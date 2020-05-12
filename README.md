# Git Collaboration implementation

## Terminology

* **git-http-backend** - Server side implementation of Git over HTTP .A simple CGI program to serve the contents of a Git repository to Git clients accessing the repository over http:// and https:// protocols 
* **git-shell** _:-_ Ristrictes git user to only do Git activities with a limited shell tool called _git-shell_ that comes with Git. If you set this as your git user’s login shell, then the git user can’t have normal shell access to your server.

## Getting Started

If you are logged in with non root user swich to root shell

```
$ sudo bash 
```

Install Git \(if not installed already\):

```text
$ apt install git
```

Find location of **git-http-backend** on our system by searching for it:

```text
root@apollo:~# find / -name git-http-backend
/usr/lib/git-core/git-http-backend
```

Now, in order for the **git-http-backend** to work properly with Apache we need to enable these modules: **mod\_cgi**, **mod\_alias**, and **mod\_env**. On my system _mod\_alias_ and _mod\_env_ were already enabled by default, you can check your system with \(_apachectl -M_\), so I only needed to enable _mod\_cgi_:

```text
root@apollo:~# a2enmod cgi
Enabling module cgi.
To activate the new configuration, you need to run:
  systemctl restart apache2
```

For security purposes, it is generally a good practice to execute CGI-scripts as a different user than the web server user, hence we create the unprivileged user and group called git, we will also install and make use of the apache2 suexec package:

First, create a git group:

```text
root@apollo:~# groupadd git
```

You can easily restrict the git user to only do Git activities with a limited shell tool called _git-shell_ that comes with Git. If you set this as your git user’s login shell, then the git user can’t have normal shell access to your server. To use this, specify git-shell instead of bash or csh for your user’s login shell. To do so, you must first add git-shell to _/etc/shells_ if it’s not in there already:

Check available shells:

```text
root@apollo:~# more /etc/shells
# /etc/shells: valid login shells
/bin/sh
/bin/bash
/bin/rbash
/bin/dash
/usr/bin/tmux
/usr/bin/screen
```

Find out the path to git-shell:

```text
root@apollo:~# find / -name git-shell
/usr/bin/git-shell
/usr/lib/git-core/git-shell
```

Ok, now add _/usr/bin/git-shell_ to _/etc/shells_, I use vi to edit and save...the file should now look like this:

```text
root@apollo:~# more /etc/shells
# /etc/shells: valid login shells
/bin/sh
/bin/bash
/bin/rbash
/bin/dash
/usr/bin/tmux
/usr/bin/screen
/usr/bin/git-shell
```

Now create a home directory for the git user at _/opt/git_:

```text
root@apollo:~# cd /opt/
root@apollo:/opt# mkdir git
```

Now create a git user. We’ll make this user a member of the git group, with a home directory of _/opt/git_, and with a shell of _/usr/bin/git-shell_:

```text
root@apollo:/opt# useradd -s /usr/bin/git-shell -g git -d /opt/git git
```

Make the git user and group the owner of the /opt/git folder:

```text
root@apollo:/opt# chown git:git git/
```

Now, I’ve decided to use a subdomain called git with my domain so that the url will look similar to this: [https://git.example.com/repos](https://git.example.com/repos) For this to work I need to add a subdomain record to my DNS-configuration. I will use a CNAME-record for this. With the host-command I can now verify that the new record does resolve in dns:

```text
root@apollo:/opt# host -t CNAME git.creang.com
git.creang.com is an alias for creang.com.
```

Now, let’s set up a VirtualHost in Apache for this subdomain:

```text
root@apollo:/opt# vi /etc/apache2/sites-enabled/vhosts-default.conf

<IfModule mod_ssl.c>
        <VirtualHost *:443>
                ServerName git.creang.com
                DocumentRoot /opt/git
                ErrorLog ${APACHE_LOG_DIR}/error.log
                CustomLog ${APACHE_LOG_DIR}/access.log combined
                <Directory /opt/git>
                        Options ExecCGI Indexes FollowSymLinks
                        AllowOverride All
                        Require all granted
                </Directory>
                SSLEngine on
                SSLCertificateFile /etc/apache2/ssl-stuff/MyCert.crt
                SSLCertificateKeyFile /etc/apache2/ssl-stuff/MyKey.key
                <Location />
                        AuthType Basic
                        AuthName "Private Git Access"
                        AuthUserFile /opt/git/.htpasswd
                        Require valid-user
                </Location>
                ScriptAlias /repos /var/www/sbin/git-http-backend-wrapper
                SuexecUserGroup git git
        </VirtualHost>
</IfModule>
```

Since _SSLCertificateChainFile_ is deprecated for Apache 2.4.8 and later, I appended the CA\_bundle.crt to my signed crt.

```text
root@apollo:/etc/apache2/ssl-stuff# cat Comodo_CA_bundle.crt >> MyCert.crt
```

My Certificate \(MyCert.crt\) is a signed multi-domain cert that I bought and it has my git-subdomain url in it. You can verify yours with a command similar to this:

```text
root@apollo:/etc/apache2/ssl-stuff# openssl x509 -in MyCert.crt -text -noout
```

Should show something like this:

```text
...
X509v3 Subject Alternative Name:
    DNS:www.creang.com, DNS:creang.com, DNS:git.creang.com
```

Now install apache2-suexec:

```text
root@apollo:/opt# apt-get install apache2-suexec-pristine

Reading package lists... Done
Building dependency tree
Reading state information... Done
The following NEW packages will be installed:
  apache2-suexec-pristine
0 upgraded, 1 newly installed, 0 to remove and 4 not upgraded.
Need to get 13.9 kB of archives.
After this operation, 130 kB of additional disk space will be used.
Get:1 http://archive.ubuntu.com/ubuntu bionic-updates/universe amd64 apache2-suexec-pristine amd64 2.4.29-1ubuntu4.5 [13.9 kB]
Fetched 13.9 kB in 0s (110 kB/s)
Selecting previously unselected package apache2-suexec-pristine.
(Reading database ... 104023 files and directories currently installed.)
Preparing to unpack .../apache2-suexec-pristine_2.4.29-1ubuntu4.5_amd64.deb ...
Unpacking apache2-suexec-pristine (2.4.29-1ubuntu4.5) ...
Setting up apache2-suexec-pristine (2.4.29-1ubuntu4.5) ...
update-alternatives: using /usr/lib/apache2/suexec-pristine to provide /usr/lib/apache2/suexec (suexec) in auto mode
Processing triggers for man-db (2.8.3-2ubuntu0.1) ...
```

Enable suEXEC Support so that the git user and group can be used when running the CGI-Script:

```text
root@apollo:/opt# a2enmod suexec
Enabling module suexec.
To activate the new configuration, you need to run:
  systemctl restart apache2
```

To work with the SuExec security model a wrapper script needs to be created that configures the environment when SuExec executes the script. The script simply sets the correct env-variables and calls git-http-backend.

```text
root@apollo:/opt# cd /var/www/
root@apollo:/var/www# mkdir sbin
root@apollo:/var/www# vi sbin/git-http-backend-wrapper

#!/bin/sh
export GIT_HTTP_EXPORT_ALL=true
exec /usr/lib/git-core/git-http-backend
```

Change owner to git user and group on this folder and script and make it executable:

```text
root@apollo:/var/www# chown -R git:git sbin/
root@apollo:/var/www# chmod 755 sbin/git-http-backend-wrapper
```

Now create the htpasswd-file. This will require the apache-utils package, install if not installed already:

```text
root@apollo:/var/www# apt install apache2-utils

Reading package lists... Done
Building dependency tree
Reading state information... Done
apache2-utils is already the newest version (2.4.29-1ubuntu4.5).
apache2-utils set to manually installed.
0 upgraded, 0 newly installed, 0 to remove and 0 not upgraded.
```

Create the _.htpasswd_ file with a new basic-auth user, replace with your user, you can later add more users to this file should you want to:

```text
root@apollo:/var/www# htpasswd -c /opt/git/.htpasswd jbilander
New password:
Re-type new password:
Adding password for user jbilander
```

Make the git user and group owner of this file:

```text
root@apollo:/var/www# chown git:git /opt/git/.htpasswd
```

Now let's create a repository in /opt/git

```text
root@apollo:/var/www# cd /opt/git/

root@apollo:/opt/git# git init --bare --shared=group myProject
Initialized empty shared Git repository in /opt/git/myProject/
```

Set the repo to http.receivepack true:

```text
root@apollo:/opt/git# cd myProject/
root@apollo:/opt/git/myProject# git config --file config http.receivepack true
```

The config file will now look like this:

```text
root@apollo:/opt/git/myProject# more config
[core]
        repositoryformatversion = 0
        filemode = true
        bare = true
        sharedrepository = 1
[receive]
        denyNonFastforwards = true
[http]
        receivepack = true
```

Set the git user and group as the owner, recursively, of this repo:

```text
root@apollo:/opt/git/myProject# cd ..
root@apollo:/opt/git# chown -R git:git myProject/
```

Restart Apache:

```text
root@apollo:/var/www# systemctl restart apache2
```

Now let's access and clone this repo from a client over https. I will do this from the command line in Windows just to show how, you may prefer to use a gui client here like SmartGit:

