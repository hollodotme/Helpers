# How to set up a self-hosted "vagrant cloud" with versioned, self-packaged vagrant boxes

## Preamble

Before we start setting things up, I assume this is what you know / what you have:

 * What vagrant is and how it basically works (obviously!)
 * How to set up a webserver like nginx or apache2
 * Basic knowledge about working with linux systems
 * A public or private webserver where you can run/configure a webserver (nginx/apache2) and upload/download files.
 * A host system with a GUI (e.g. Windows, Mac OS X, etc.)

The tutorial uses an installation of [Ubuntu 14.04.2 LTS](https://wiki.ubuntu.com/TrustyTahr/ReleaseNotes) 
as the guest machine, [VirtualBox](http://virtualbox.org) at version 5.0.0 as provider 
and [Vagrant](http://vagrantup.com) at version 1.7.4.

## 1. Install the tools

 * Download and install VirtualBox 5.0.0 at http://download.virtualbox.org/virtualbox/5.0.0/ (Choose the installer that fits your system)
 * Download and install Vagrant 1.7.4 at https://dl.bintray.com/mitchellh/vagrant/ (Choose the installer that fits your system)

## 2. Prepare your virtual machine
 
### 2.1 Import an Ubuntu image to VirtualBox
 
 * Download a VirtualBox image of Ubuntu 14.04.2 LTS, e.g. at http://virtualboxes.org/images/ubuntu-server/ (all the following steps refer to this image)
 * Open the VirtualBox GUI and choose `File > Import appliance ...`, select the `.ova` file you downloaded before.
 * Change the appliance settings to fit your needs, for now I'll only change the name of the machine from `ubuntu-14.04-server-amd64` to `devops-template`.
 * Important: Make sure to activate `Reinitialize the MAC address of all network cards` checkbox!
 * Click `Import` and you'll have a new virtual machine added to VirtualBox after a few minutes ready to run.
 
### 2.2 Setup the virtual machine

#### Before you boot the vm for the first time:

 * Select the newly imported vm named `devops-template` in VirtualBox GUI and click `Settings`
 * Select the tab `Network`
 * Activate `Enable Network Adapter` (if not already activated) under the tab `Adapter 1`
 * Select `Attached to:` `NAT` ([this is a requirement by Vagrant](http://docs.vagrantup.com/v2/virtualbox/boxes.html))
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

 * **Important:** Install the VirtualBox Guest Additions __with the proper version__
 
Hint: Do not install the guest additions with `apt-get install virtualbox-guest-additions-iso` as this release is mostly outdated.
I said above that I'm using VirtualBox in Version `5.0.0`, so the guest additions should be of the same version.

```bash
# prepare
$ sudo apt-get install -y linux-headers-generic build-essential dkms
# get the right ISO from http://download.virtualbox.org/virtualbox/
$ wget http://download.virtualbox.org/virtualbox/5.0.0/VBoxGuestAdditions_5.0.0.iso
# create a mount folder
$ sudo mkdir /media/VBoxGuestAdditions
# mount the ISO
$ sudo mount -o loop,ro VBoxGuestAdditions_5.0.0.iso /media/VBoxGuestAdditions
# install the guest additions
$ sudo sh /media/VBoxGuestAdditions/VBoxLinuxAdditions.run
# remove the ISO
$ rm VBoxGuestAdditions_5.0.0.iso
# unmount the ISO
$ sudo umount /media/VBoxGuestAdditions
# remove the mount folder
$ sudo rmdir /media/VBoxGuestAdditions
```

 * **Last but not least:** Change the welcome message and add the version number. We will change this later to see versioning work.
  
```bash
$ rm -rf /etc/motd
$ sudo nano /etc/motd
--
Welcome to devops-template version 0.1.0!
--
```

 * Reboot the vm to see your changes take effect:
 
```bash
$ sudo shutdown -r now
```

Now you should see a login terminal like this:

```bash
Ubuntu 14.04.2 LTS devops-template tty1

devops-template login: _
```

Login and check the message of the day (motd) we set up:

```bash
Last login: ...
Welcome to Ubuntu ...

 * Documentation: ...
 
 [...]
 
--
Welcome to devops-template version 0.1.0!
--
ubuntu@devops-template:~$ _
```

 * Shutdown the vm

```bash
$ sudo shutdown -h now
```

Got it? Yeah, preparation is done!

## 3. Package the vagrant box

Remember, we said in the message of the day, that this is version `0.1.0`.

 * Open a terminal on your host machine
 * Create two new directories: `VagrantBoxes` and `VagrantTest`
 
 
```bash
$ mkdir ~/VagrantBoxes
$ mkdir ~/VagrantTest
```

### 3.1 Create the box out of the vm named `devops-template` in VirtualBox
 
 * Change into the `VagrantBoxes` directory.
 * Package the box
 
```bash
$ cd ~/VagrantBoxes
$ vagrant package --base 'devops-template' --output 'devops_0.1.0.box'
```

Note: Because we will build multiple versions of the `devops-template` vm, we will put the version number in the name of the box file.

Packaging will take some time, you may get your next coffee!

When packaging is done, you should see an output like this:

```bash
==> devops-template: Exporting VM...
==> devops-template: Compressing package to: ~/VagrantBoxes/devops_0.1.0.box
```

### 3.2 Test the box

 * Change into the `VagrantTest` directory.
 
```bash
$ cd ~/VagrantTest
```
 
 * Add the box to vagrant
 
```bash
$ vagrant box add 'devops' file://~/VagrantBoxes/devops_0.1.0.box
```

If successful, you should see output like this:
 
```bash
==> box: Adding box 'devops' (v0) for provider:
    box: Downloading: file://~/VagrantBoxes/devops_0.1.0.box
==> box: Successfully added box 'devops' (v0) for 'virtualbox'!
```

Note the `v0`. There is no version specified yet. 

 * Init a vagrant project
 
```bash
$ vagrant init
```
 
Now there is a `Vagrantfile` with default settings in your current directory.
 
 * Edit this `Vagrantfile` using a text editor
 
```bash
# Change the following line from
config.vm.box = "base"
# to
config.vm.box = "devops"
# save the file
```
 
Note: `base` is vagrant's default name for a box. But we told `vagrant box add` the name `devops`.
 
 * Bring the machine up
 
```bash
$ vagrant up
```

If done, you should see output like this:

```bash
Bringing machine 'default' up with 'virtualbox' provider...
==> default: Importing base box 'devops'...
==> default: Matching MAC address for NAT networking...
==> default: Setting the name of the VM: VagrantTest_default_1406634147824_25052
==> default: Clearing any previously set network interfaces...
==> default: Preparing network interfaces based on configuration...
    default: Adapter 1: nat
==> default: Forwarding ports...
    default: 22 => 2222 (adapter 1)
==> default: Booting VM...
==> default: Waiting for machine to boot. This may take a few minutes...
    default: SSH address: 127.0.0.1:2222
    default: SSH username: vagrant
    default: SSH auth method: private key
    default: Warning: Connection timeout. Retrying...
==> default: Machine booted and ready!
==> default: Checking for guest additions in VM...
==> default: Mounting shared folders...
    default: /vagrant => ~/VagrantTest
```

**Note:** Vagrant's default behaviour is to boot the vm in headless mode (without a GUI in background). 
While booting the vm you can see the new vm in your VirtualBox GUI as "-> Running"

 * SSH into the machine
 
```bash
$ vagrant ssh
```

Now you should see our previously added message of the day.

 * Check if your `VagrantTest` folder is mounted
 
```bash
$ cd /vagrant/
$ ls -la
```

And you should see something like this:
 
```bash
drwxr-xr-x  1 vagrant vagrant  136 Jul 29 13:38 .
drwxr-xr-x 23 root    root    4096 Jul 29 13:42 ..
drwxr-xr-x  1 vagrant vagrant  102 Jul 29 13:38 .vagrant
-rw-r--r--  1 vagrant vagrant 4813 Jul 29 13:39 Vagrantfile
```

**Okay, your box works fine, well done!**

 * Exit the SSH session and destroy the machine
  
```bash
$ exit # On guest
```

```bash
$ vagrant destroy # On host
```

 * Remove the box from vagrant (we will add it later with a version again!)
 
```bash
$ vagrant box remove 'devops'
Removing box 'devops' (v0) with provider 'virtualbox'...
```

## 4. Using a box catalog for versioning

In order to serve multiple versions of a vagrant box and enable update notifications we need to set up a box catalog.
This catalog is written in JSON code to a single file.

To keep things simple at this point we will carry on in the local filesystem of your host. 

### 4.1 Set up the catalog for the `devops` box

 * Change into the `VagrantBoxes` directory.
  
```bash
cd ~/VagrantBoxes
```

 * Create the file `devops.json` with the following content
 
```json
{
    "name": "devops",
    "description": "This box contains Ubuntu 14.04.2 LTS 64-bit.",
    "versions": [{
        "version": "0.1.0",
        "providers": [{
                "name": "virtualbox",
                "url": "file://~/VagrantBoxes/devops_0.1.0.box",
                "checksum_type": "sha1",
                "checksum": "d3597dccfdc6953d0a6eff4a9e1903f44f72ab94"
        }]
    }]
}
```

**What is going on here?**
 * We tell the catalog to be related to the box name `devops`. All versions will be grouped under this name.
 * We created a clear statement of what is in the box.
 * We defined the first version 0.1.0
 * We tell that, version 0.1.0 is available for provider virtualbox under the URL file://~/VagrantBoxes/devops_0.1.0.box 
  * That's why you should put the version into the filename!
 * We tell vagrant that there is a sha1-checksum to check when importing the box.
  * To determine the checksum of your box, you can simple do sth. like this:
  
```bash
$ openssl sha1 ~/VagrantBoxes/devops_0.1.0.box
SHA1(~/VagrantBoxes/devops_0.1.0.box)= d3597dccfdc6953d0a6eff4a9e1903f44f72ab94
```

Note: This is done on a linux based system. On Windows there will be another way.

Your catalog is finished!
 
### 4.2 Link the catalog to vagrant instead of the box

 * Change into the `VagrantTest` directory
 
```bash
$ cd ~/VagrantTest
```

 * Edit the `Vagrantfile` file in a text editor
 
```ruby
# Under ...
config.vm.box = "devops"
# ... add the line
config.vm.box_url = "file://~/VagrantBoxes/devops.json"
# save the file
```

 * Run `vagrant up` __without__ adding the box manually using `vagrant box add`
 
```bash
$ vagrant up
```

When done, you should see output like this:

```bash
Bringing machine 'default' up with 'virtualbox' provider...
==> default: Box 'devops' could not be found. Attempting to find and install...
    default: Box Provider: virtualbox
    default: Box Version: >= 0
==> default: Loading metadata for box 'file://~/VagrantBoxes/devops.json'
    default: URL: file://~/VagrantBoxes/devops.json
==> default: Adding box 'devops' (v0.1.0) for provider: virtualbox
    default: Downloading: file://~/VagrantBoxes/devops_0.1.0.box
    default: Calculating and comparing box checksum...
==> default: Successfully added box 'devops' (v0.1.0) for 'virtualbox'!
```

**Note** the line `Loading metadata for box 'file://~/VagrantBoxes/devops.json'` that confirms the catalog is read.
**And note** the line `Adding box 'devops' (v0.1.0) for provider: virtualbox` that confirms that version 0.1.0 is added to VirtualBox.
The last two lines confirm that our checksum in the `devops.json` file was correct.

```bash
==> default: Importing base box 'devops'...
==> default: Matching MAC address for NAT networking...
==> default: Checking if box 'devops' is up to date...
==> default: Setting the name of the VM: VagrantTest_default_1406640770074_13210
==> default: Clearing any previously set network interfaces...
==> default: Preparing network interfaces based on configuration...
    default: Adapter 1: nat
==> default: Forwarding ports...
    default: 22 => 2222 (adapter 1)
==> default: Booting VM...
==> default: Waiting for machine to boot. This may take a few minutes...
    default: SSH address: 127.0.0.1:2222
    default: SSH username: vagrant
    default: SSH auth method: private key
    default: Warning: Connection timeout. Retrying...
==> default: Machine booted and ready!
==> default: Checking for guest additions in VM...
==> default: Mounting shared folders...
    default: /vagrant => ~/VagrantTest
```

Again, our machine is up and running. 
If you wish you now can re-check by ssh into the machine, reading the message of the day and listing the content of `/vagrant` on the guest. 

Okay, now we have built a vagrant box with an initial version. **Let's add another version.**

 * Halt the vm (**do not** destroy it!)
 
```bash
$ vagrant halt
==> default: Attempting graceful shutdown of VM...
```

### 4.3 Raise the box version by changing the template vm

 * Open the VirtualBox GUI, select the vm named `devops-template` and click `Start`
 * Log into the vm after it has booted
 * Change the version number in `/etc/motd` from `0.1.0` to `0.1.1` and save the file
  
```bash
$ sudo nano /etc/motd
--
Welcome to devops-template version 0.1.1!
--
```

 * Shutdown the vm
 
```bash
$ sudo shutdown -h now
```

**To be clear:** Changing the version number in `/etc/motd` has nothing to do with the version itself. It just simulates 
a minor but visible change to the vm template. Instead of changing the content of a file, you'll be installing software 
or editing configs on a real-world vm.
 
 * Change into `VagrantBoxes` directory.
 
```bash
$ cd ~/VagrantBoxes
```

 * Package the template vm to a new vagrant box
 
```bash
$ vagrant package --base 'devops-template' --output 'devops_0.1.1.box'
==> devops-template: Exporting VM...
==> devops-template: Compressing package to: ~/VagrantBoxes/devops_0.1.1.box
```

Note the raised version number `0.1.1` in the output filename!

Your directory listing of `~/VagrantBoxes` should look like this now:

```bash
$ ls -a1 ~/VagrantBoxes
.
..
devops.json
devops_0.1.0.box
devops_0.1.1.box
```

If you wish you can test your new box like done under chapter 3.2.

### 4.4 Add the new version to the catalog

 * Edit the `devops.json` file in a text editor and extend the content to look like this:
 
```json
{
    "name": "devops",
    "description": "This box contains Ubuntu 14.04.1 LTS 64-bit.",
    "versions": [{
        "version": "0.1.0",
        "providers": [{
                "name": "virtualbox",
                "url": "file:///Users/hollodotme/VagrantBoxes/devops_0.1.0.box",
                "checksum_type": "sha1",
                "checksum": "d3597dccfdc6953d0a6eff4a9e1903f44f72ab94"
        }]
    },{
        "version": "0.1.1",
        "providers": [{
                "name": "virtualbox",
                "url": "file:///Users/hollodotme/VagrantBoxes/devops_0.1.1.box",
                "checksum_type": "sha1",
                "checksum": "0b530d05896cfa60a3da4243d03eccb924b572e2"
        }]
    }]
}
```

**Don't forget** to determine the checksum of the newly created box `devops_0.1.1.box`!

### 4.5 Check for outdated vagrant box and update

 * Change into `VagrantTest` directory.
 
```bash
$ cd ~/VagrantTest
```

 * Ask if your vagrant box is outdated
 
```bash
$ vagrant box outdated
Checking if box 'devops' is up to date...
A newer version of the box 'devops' is available! You currently
have version '0.1.0'. The latest is version '0.1.1'. Run
`vagrant box update` to update.
```

Suprise, suprise - a new version is available!

**Note:** Instead of manually asking for outdated boxes, vagrant will notify you automatically when you use the 
Vagrant commands like `vagrant up`, `vagrant reload`, `vagrant resume`, etc.!

 * Update the box
  
```bash
$ vagrant box update
==> default: Checking for updates to 'devops'
    default: Latest installed version: 0.1.0
    default: Version constraints:
    default: Provider: virtualbox
==> default: Updating 'devops' with provider 'virtualbox' from version
==> default: '0.1.0' to '0.1.1'...
==> default: Loading metadata for box 'file://~/VagrantBoxes/devops.json'
==> default: Adding box 'devops' (v0.1.1) for provider: virtualbox
    default: Downloading: file://~/VagrantBoxes/devops_0.1.1.box
    default: Calculating and comparing box checksum...
==> default: Successfully added box 'devops' (v0.1.1) for 'virtualbox'!
```

**Note:** The box with version `0.1.0` still exists. Vagrant will never prune your boxes automatically because of potential data loss.

 * Remove the old box
 
```bash
$ vagrant box remove 'devops'
You requested to remove the box 'devops' with provider
'virtualbox'. This box has multiple versions. You must
explicitly specify which version you want to remove with
the `--box-version` flag. The available versions for this
box are:

 * 0.1.0
 * 0.1.1
```

Okay, so...

```bash
$ vagrant box remove 'devops' --box-version '0.1.0'
~/VagrantTest/Vagrantfile:5: warning: already initialized constant VAGRANTFILE_API_VERSION
~/VagrantTest/Vagrantfile:5: warning: previous definition of VAGRANTFILE_API_VERSION was here
Box 'devops' (v0.1.0) with provider 'virtualbox' appears
to still be in use by at least one Vagrant environment. Removing
the box could corrupt the environment. We recommend destroying
these environments first:

default (ID: 3206d9d1a427459daac770f2e7e81f1b)

Are you sure you want to remove this box? [y/N]
```

**N**o! Let's destroy it first.

```bash
$ vagrant destroy
    default: Are you sure you want to destroy the 'default' VM? [y/N] y
==> default: Destroying VM and associated drives...
```

Now remove it, please!

```bash
$ vagrant box remove 'devops' --box-version '0.1.0'
Removing box 'devops' (v0.1.0) with provider 'virtualbox'...
```

Hell yeah!

### 4.6 Check for the change

 * Bring up the machine
 
```bash
$ vagrant up
```

 * SSH into the machine
 
```bash
$ vagrant ssh
```

Now you should see the previously changed version number `0.1.1` in the message of the day after login.

```bash
--
Welcome to devops-template version 0.1.1!
--
```

**So we are up-to-date!**

__What we did so far:__
 * We created a virtual machine template
 * We built two versioned vagrant boxes out of the virtual machine template
 * We established a box catalog to serve the versions to the client
 * We updated an outdated box
 
__What we will do now:__
 * Hosting the box catalog and the boxes on a webserver a.k.a. set up our own vagrant cloud.
 
So cleanup your desk: exit your SSH session, destroy the vm and remove it from vagrant.

```bash
$ exit # On guest
```

```bash
$ vagrant destroy # On host
$ vagrant box remove 'devops'
```
 
## 5. Hosting

As I mentioned at the beginning, I assume that you have private/public webserver and access to its config and filesystem.

For explanation I'll use the domain `www.example.com` targeting to this webserver.
Furthermore `www.example.com` points to `/var/www/` on the webserver's filesystem (document root).

### 5.1 Suggested directory structure

To keep things easy I prefer to separate the catalog and the box files physically in the filesystem.
Keep on reading and you'll understand why.

```bash
- /var/www                              # document root
        `- vagrant
           `- devops                    # box name folder
              |- boxes                  # contains all available box files
              |  |- devops_0.1.0.box    # version 0.1.0
              |  `- devops_0.1.1.box    # version 0.1.1
              `- devops.json            # box catalog
```

Translated to URLs we have three targets to care about (we will use these later):
 * The catalog: http://www.example.com/vagrant/devops/devops.json
 * Box (v0.1.0): http://www.example.com/vagrant/devops/boxes/devops_0.1.0.box
 * Box (v0.1.1): http://www.example.com/vagrant/devops/boxes/devops_0.1.1.box
 
### 5.2 Webserver configuration

I want to explain the basic webserver configuration with nginx on a linux server, because this is my favorite software.
The configuration can be ported to apache and/or windows as well.

 * SSH into your server.
 
```bash
$ ssh user@example.com
```

 * Install nginx
  
```bash
$ sudo apt-get install nginx-full
```

 * Create the target folders and set permissions
 
```bash
# Create folders
$ sudo mkdir -p /var/www/vagrant/devops/boxes
# Set owner to www-data
$ sudo chown -R www-data:www-data /var/www
# Set permissions
$ sudo chmod -R 0751 /var/www
```

 * Delete the `default` sym-linked config for virtual hosts (vhost)
  * Just to make sure there is no colliding config! 
  
```bash
$ sudo rm -rf /etc/nginx/sites-enabled/default
```

 * Create a new specific vhost config for `www.example.com`
  
```bash
sudo nano /etc/nginx/sites-available/example.com
```

... with the following content:

```bash
server {
    listen   80 default_server;
    listen   [::]:80 ipv6only=on default_server;
    
    server_name example.com www.example.com;

    root /var/www;

    # Match the box name in location and search for its catalog
    # e.g. http://www.example.com/vagrant/devops/ resolves /var/www/vagrant/devops/devops.json  
    location ~ ^/vagrant/([^\/]+)/$ {
        index $1.json;
        try_files $uri $uri/ $1.json =404;
        autoindex off;
    }

    # Enable auto indexing for the folder with box files
    location ~ ^/vagrant/([^\/]+)/boxes/$ {
        try_files $uri $uri/ =404;
        autoindex on;
        autoindex_exact_size on;
        autoindex_localtime on;
    }

    # Serve json files with content type header application/json
    location ~ \.json$ {
        add_header Content-Type application/json;
    }

    # Serve box files with content type application/octet-stream
    location ~ \.box$ {
        add_header Content-Type application/octet-stream;
    }

    # Deny access to document root and the vagrant folder
    location ~ ^/(vagrant/)?$ {
        return 403;
    }
}
```

 * Sym-link the vhost config to enable it
 
```bash
$ sudo ln -s /etc/nginx/sites-available/example.com /etc/nginx/sites-enabled/000-example.com
```
  
 * Restart nginx and exit the webserver
 
```bash
$ service nginx restart
$ exit
```

### 5.3 Change the box catalog

Now, that our boxes won't be stored any longer on the local filesystem, we have to change their locations in the box catalog.

 * Open `~/VagrantBoxes/devops.json` in your text editor and change the content to this:
 
```json
{
    "name": "devops",
    "description": "This box contains Ubuntu 14.04.1 LTS 64-bit.",
    "versions": [{
        "version": "0.1.0",
        "providers": [{
                "name": "virtualbox",
                "url": "http://www.example.com/vagrant/devops/boxes/devops_0.1.0.box",
                "checksum_type": "sha1",
                "checksum": "d3597dccfdc6953d0a6eff4a9e1903f44f72ab94"
        }]
    },{
        "version": "0.1.1",
        "providers": [{
                "name": "virtualbox",
                "url": "http://www.example.com/vagrant/devops/boxes/devops_0.1.1.box",
                "checksum_type": "sha1",
                "checksum": "0b530d05896cfa60a3da4243d03eccb924b572e2"
        }]
    }]
}
```

 * Upload this file to your webserver to directory `/var/www/vagrant/devops/`.
 
If you open the URL http://www.example.com/vagrant/devops/ in your browser you should see your JSON box catalog.

### 5.4 Upload your boxes

 * Upload both box files to your webserver to directory `/var/www/vagrant/devops/boxes/`.
 
If you open the URL http://www.example.com/vagrant/devops/boxes/ in your browser you should see a directory 
listing with both box files listed.
 
### 5.5 Change the `Vagrantfile`

 * Change into the `~/VagrantTest` directory on your host.
  
```bash
$ cd ~/VagrantTest
```

 * Open the `Vagrantfile` file in your text editor

```json
# Change the line
config.vm.box_url = "file://~/VagrantBoxes/devops.json"
# to
config.vm.box_url = "http://www.example.com/vagrant/devops/"
# save the file
```

### 5.6 Get finally up and running

 * Bring up the machine
 
```bash
$ vagrant up
Bringing machine 'default' up with 'virtualbox' provider...
==> default: Box 'devops' could not be found. Attempting to find and install...
    default: Box Provider: virtualbox
    default: Box Version: >= 0
==> default: Loading metadata for box 'http://www.example.com/vagrant/devops/'
    default: URL: http://www.example.com/vagrant/devops/
==> default: Adding box 'devops' (v0.1.1) for provider: virtualbox
    default: Downloading: http://www.example.com/vagrant/devops/boxes/devops_0.1.1.box
    default: Calculating and comparing box checksum...
==> default: Successfully added box 'devops' (v0.1.1) for 'virtualbox'!
==> default: Importing base box 'devops'...
==> default: Matching MAC address for NAT networking...
==> default: Checking if box 'devops' is up to date...
==> default: Setting the name of the VM: VagrantTest_default_1406660957112_34972
==> default: Clearing any previously set network interfaces...
==> default: Preparing network interfaces based on configuration...
    default: Adapter 1: nat
==> default: Forwarding ports...
    default: 22 => 2222 (adapter 1)
==> default: Booting VM...
==> default: Waiting for machine to boot. This may take a few minutes...
    default: SSH address: 127.0.0.1:2222
    default: SSH username: vagrant
    default: SSH auth method: private key
    default: Warning: Connection timeout. Retrying...
    default: Warning: Remote connection disconnect. Retrying...
==> default: Machine booted and ready!
==> default: Checking for guest additions in VM...
==> default: Mounting shared folders...
    default: /vagrant => ~/VagrantTest
```

**And here it is: Your own vagrant cloud!**

## Epilog

 * Please trigger fixes to this tutorial as an issue to this repo here on github
 * For questions you can find me in the [vagrant google group](https://groups.google.com/forum/#!usersettings/general)
 * Thanks for reading, I hope this helps boosting your environment!

## Further reading

 * [Sets up a nginx server which hosts vagrant boxes](https://github.com/ebmeierj/local_vagrant_box_hosting) by [ebmeierj](https://github.com/ebmeierj)
