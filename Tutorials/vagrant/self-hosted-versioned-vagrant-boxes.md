# How to set up a self-hosted "vagrant cloud" with versioned, self-packaged vagrant boxes

## Preamble

Before we start setting things up, I assume this is what you know / what you have:

 * What vagrant is and how it basically works (obviously!)
 * How to set up a webserver like nginx or apache2
 * Basic knowledge about working with linux systems
 * A public or private webserver where you can run/configure a webserver (nginx/apache2) and up/download files.
 * A host system with a GUI (e.g. Windows, Mac OS X, etc.)

The tutorial uses an installation of [Ubuntu 14.04.1 LTS](https://wiki.ubuntu.com/TrustyTahr/ReleaseNotes) 
as the guest machine, [VirtualBox](http://virtualbox.org) at version 4.3.14 as provider 
and [Vagrant](http://vagrantup.com) at version 1.6.3.

## 1. Install the tools

 * Download and install VirtualBox 4.3.14 at http://download.virtualbox.org/virtualbox/4.3.14/ (Choose the installer that fits your system)
 * Download and install Vagrant 1.6.3 at https://dl.bintray.com/mitchellh/vagrant/ (Choose the installer that fits your system)

## 2. Prepare your virtual machine
 
### 2.1 Import an Ubuntu image to VirtualBox
 
 * Download a VirtualBox image of Ubuntu 14.04.1 LTS, e.g. at http://virtualboxes.org/images/ubuntu-server/ (all the following steps refer to this image)
 * Open the VirtualBox GUI and choose `File > Import appliance ...`, select the `.ova` file you downloaded before.
 * Change the appliance settings to fit your needs, for now I'll only change the name of the machine from `ubuntu-14.04-server-amd64` to `devops-template`.
 * Important: Make sure to activate `Reinitialize the MAC address of all network cards` checkbox!
 * Click `Import` and you'll have a new virtual machine added to VirtualBox after a few minutes ready to run.
 
### 2.2 Setup the virtual machine

#### Before you boot the vm for the first time:

 * Select the newly imported vm named `devops-template` in VirtualBox GUI and click `Settings`
 * Select the tab `Network`
 * Activate `Enable Network Adapter` (if not already activated) under the tab `Adapter 1`
 * Select `Attached to: NAT` ([this is a requirement by Vagrant](http://docs.vagrantup.com/v2/virtualbox/boxes.html))
 * Leave everything else as is.
 
#### Configure the guest

 * Select the vm named `devops-template` in VirtualBox GUI and click `Start` (wait until you see the `ubuntu-amd64 login:`) 
 * Type `ubuntu` as loginname an `reverse` as password.
 * First of all, update the machine. This will take a moment. Get a coffee!
  
```bash
$ sudo apt-get update
$ sudo apt-get dist-upgrade -y
```

 * Edit the file `/root/.profile`
   
```bash
$ sudo nano /root/.profile
# ~/.profile: executed by Bourne-compatible login shells.

if [ "$BASH" ]; then
  if [ -f ~/.bashrc ]; then
    . ~/.bashrc
  fi
fi

mesg n # replace this line by "tty -s && mesg n"
```

Note: This avoids an annoying warning, when you `vagrant up` later.

 * Change the hostname
  
```bash
$ sudo nano /etc/hostname
ubuntu-amd64 # replace this by "devops-template"
```
 * Let the machine resolve its own hostname
  
```bash
$ sudo nano /etc/hosts
127.0.0.1   localhost
127.0.1.1   ubuntu-amd64    # replace this by "127.0.1.1 devops-template"

# The following lines are desirable for IPv6 capable hosts
::1         localhost ip6-localhost ip6-loopback
ff02::1     ip6-allnodes
ff02::2     ip6-allrouters
```

 * Finalize language settings
  
```bash
$ sudo locale-gen en_US.UTF-8 # Or whatever language you want to use
$ sudo dpkg-reconfigure locales
$ sudo nano /etc/default/locale
LANG="en_US.UTF-8"
LANGUAGE="en_US"
```

 * Add the `vagrant` user
  
```bash
$ sudo adduser vagrant
# Set the password to "vargrant" too!
# Set the Full Name to "Vagrant", leave the rest blank
```

 * Allow Vagrant to login via insecure private key
   
```bash
# Add a ssh config folder and authorized_keys file
$ sudo mkdir /home/vagrant/.ssh
$ sudo touch /home/vagrant/.ssh/authorized_keys
# Set owner and permissions
$ sudo chown -R vagrant /home/vagrant/.ssh
$ sudo chmod 0700 /home/vagrant/.ssh
$ sudo chmod 0600 /home/vagrant/.ssh/authorized_keys
# Add the insecure public key, see https://github.com/mitchellh/vagrant/tree/master/keys
$ su vagrant
$ curl 'https://raw.githubusercontent.com/mitchellh/vagrant/master/keys/vagrant.pub' >> /home/vagrant/.ssh/authorized_keys
$ exit
```

 * Configure the sudo rights of user `vagrant`

```bash
$ sudo nano /etc/sudoers.d/vagrant
vagrant ALL=(ALL) NOPASSWD: ALL
$ sudo chmod 0440 /etc/sudoers.d/vagrant
```

 * Disable DNS usage for sshd
 
```bash
$ sudo nano /etc/ssh/sshd_config
# Add the following line at the end of the file:
UseDNS no
```

 * **Last but not least:** Change the welcome message and add the version number. We will change this later to see versioning work.
  
```bash
$ rm -rf /etc/motd
$ sudo nano /etc/modt
Welcome to devops-template version 0.1.0!
```

 * Reboot the vm to see your changes take effect:
 
```bash
$ sudo shutdown -r now
```

Now you should see a login terminal like this:

```bash
Ubuntu 14.04.1 LTS devops-template tty1

devops-template login: _
```

Login and check the message of the day (motd) we set up:

```bash
Last login: ...
Welcome to Ubuntu ...

 * Documentation: ...
 
 [...]
 
Welcome to devops-template version 0.1.0!
ubuntu@devops-template:~$ _
```

Got it? Yeah!
