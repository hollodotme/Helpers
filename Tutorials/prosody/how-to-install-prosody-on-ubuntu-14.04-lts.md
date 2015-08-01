# How to install XMPP server prosody with mysql auth backend on Ubuntu 14.04 LTS

## Preamble

Prosody is an alternative to ejabberd and is focused on easy setup and configuration and being efficient with system resources.
You can find all the information about prosody [here](http://prosody.im).
   
### What we will do in this tutorial

 * Of course, install Prosody
 * Configure Prosody to use a MySQL backend for authentication

### What you will need

 * ssh access to an ubuntu server with sudo privileges
 * Maybe a frontend for your MySQL server like phpMyAdmin if you're not familiar with the MySQL command line interface
 * A sub-domain to run the jabber server on
 * Make sure your server is accessable on port **5222**.
  
## 1. Configure your domains
  
 * Your server should have an IP address, I will use the `10.100.10.1` for demonstration.
 * I will use the following domains to run the XMPP server on:
   * jabber.hollo.me
   * conference.jabber.hollo.me
 
Of course, you should replace the IP and the domains with your real world ones.
   
### Local configuration via /etc/hosts

Edit your `/etc/hosts` and add the following line to it:

```bash
10.100.10.1     jabber.hollo.me conference.jabber.hollo.me
```

Save the file. That's it.

This configuration is only for testing the service. 

### Domain provider / DNS configuration
 
 * Setup the A-Record of your two domains to point on the IP 10.100.10.1
 
You'll have to do this, if your server should be reachable for other clients in a public or private network.
Makes sense. :)
  
## 2. Install MySQL and Prosody

### SSH into your server

```bash
ssh user-with-sudo@jabber.hollo.me
```

### Update your system (optional)

```bash
jabber.hollo.me$ sudo apt-get update && apt-get dist-upgrade -y
```

### Install all packages we will need

```bash
jabber.hollo.me$ sudo apt-get install mysql-server-5.5 mysql-client-5.5 prosody lua-dbi-mysql
```

Standard MySQL server will do the job, but I prefer using the percona derivate of MySQL, so this line would look like this:

```bash
jabber.hollo.me$ sudo apt-get install percona-xtradb-cluster-server-5.5 percona-xtradb-cluster-client-5.5 prosody lua-dbi-mysql
```

 * You will be asked for a root password for your mysql server during installation; set one you like.
 * After installation both, the mysql server and the prosody server are up and running.

### Secure your MySQL server (optional, but strictly recommended)

```bash
jabber.hollo.me$ sudo mysql_secure_installation
```

## 3. Configure MySQL and Prosody

### Login to your MySQL server

```bash
jabber.hollo.me$ mysql -u root -p
```

### Create a database named "prosody"

```sql
mysql> CREATE DATABASE `prosody`;
```

### Create a user names "prosody" and grant all privileges for database "prosody"

```sql
mysql> CREATE USER 'prosody'@'localhost' IDENTIFIED BY 'secret'; # Replace 'secret' with a password you like!
mysql> GRANT ALL PRIVILEGES ON prosody.* TO 'prosody'@'localhost';
mysql> quit
```

### Configure Prosody to use the MySQL backend

Edit `/etc/prosody/prosody.cfg.lua`:

```bash
jabber.hollo.me$ sudo nano /etc/prosody/prosody.cfg.lua
```

Search for the following config block:

```
--storage = "sql" -- Default is "internal" (Debian: "sql" requires one of the
-- lua-dbi-sqlite3, lua-dbi-mysql or lua-dbi-postgresql packages to work)

-- For the "sql" backend, you can uncomment *one* of the below to configure:
--sql = { driver = "SQLite3", database = "prosody.sqlite" } -- Default. 'database' is the filename.
--sql = { driver = "MySQL", database = "prosody", username = "prosody", password = "secret", host = "localhost" }
--sql = { driver = "PostgreSQL", database = "prosody", username = "prosody", password = "secret", host = "localhost" }
```

Now uncomment the first and the sixth line of this block (remove the `--` and line start)

The block should now look like this:

```
storage = "sql" -- Default is "internal" (Debian: "sql" requires one of the
-- lua-dbi-sqlite3, lua-dbi-mysql or lua-dbi-postgresql packages to work)

-- For the "sql" backend, you can uncomment *one* of the below to configure:
--sql = { driver = "SQLite3", database = "prosody.sqlite" } -- Default. 'database' is the filename.
sql = { driver = "MySQL", database = "prosody", username = "prosody", password = "secret", host = "localhost" }
--sql = { driver = "PostgreSQL", database = "prosody", username = "prosody", password = "secret", host = "localhost" }
```

 * Replace "secret" with the password you set for the MySQL user 'prosody'.
 * Leave anything else as is.
 * Save the file and exit nano.

**Note:** By default Prosody will handle the database scheme by itself the first time it is needed. So we don't need to create tables ourselves. 

### Create a VHost for Prosody with your domains
 
First, create a copy of the example.com VHost file:
 
```bash
jabber.hollo.me$ sudo cp /etc/prosody/conf.avail/example.com.cfg.lua /etc/prosody/conf.avail/jabber.hollo.me.cfg.lua
```

Edit `/etc/prosody/conf.avail/jabber.hollo.me.cfg.lua`:

```bash
jabber.hollo.me$ sudo nano /etc/prosody/conf.avail/jabber.hollo.me.cfg.lua
```

The config should look like this:

```
-- Section for example.com

VirtualHost "example.com"
        enabled = false -- Remove this line to enable this host

        -- Assign this host a certificate for TLS, otherwise it would use the one
        -- set in the global section (if any).
        -- Note that old-style SSL on port 5223 only supports one certificate, and will always
        -- use the global one.
        ssl = {
                key = "/etc/prosody/certs/example.com.key";
                certificate = "/etc/prosody/certs/example.com.crt";
                }

------ Components ------
-- You can specify components to add hosts that provide special services,
-- like multi-user conferences, and transports.
-- For more information on components, see http://prosody.im/doc/components

-- Set up a MUC (multi-user chat) room server on conference.example.com:
Component "conference.example.com" "muc"

-- Set up a SOCKS5 bytestream proxy for server-proxied file transfers:
--Component "proxy.example.com" "proxy65"

---Set up an external component (default component port is 5347)
--Component "gateway.example.com"
--      component_secret = "password"
```

Edit the content to look like this:

```
-- Section for jabber.hollo.me

VirtualHost "jabber.hollo.me"

Component "conference.jabber.hollo.me" "muc"
```

 * Save the file and exit nano.
 * Yes, that's all we need.

Now create a symlink to activate the VHost. Prosody automatically loads all VHosts under `/etc/prosody/conf.d`.

```bash
jabber.hollo.me$ sudo ln -sf ../conf.avail/jabber.hollo.me.cfg.lua /etc/prosody/conf.d/jabber.hollo.me.cfg.lua
```

### Restart Prosody

The basic configuration is done. Now restart Prosody to let the changes take effect.

```bash
jabber.hollo.me$ sudo service prosody restart
```

## 4. Create jabber users

 * Prosody serves a command line interface `prosodyctl` that easily lets you register some users.
 * We will add two test users for demonstration
 
```bash
jabber.hollo.me$ sudo prosodyctl register testuser1 jabber.hollo.me secret1
jabber.hollo.me$ sudo prosodyctl register testuser2 jabber.hollo.me secret2
```

**Note:** This is the first time Prosody uses the MySQL backend and will apply the nessecary database scheme.

### Check if database scheme was applied (optional)

Login to your MySQL server:

```bash
jabber.hollo.me$ mysql -u root -p
```

Show all tables in database `prosody`:

```sql
mysql> SHOW TABLES FROM `prosody`; 
```

Output should look like this:

```
+-------------------+
| Tables_in_prosody |
+-------------------+
| prosody           |
+-------------------+
1 row in set (0.00 sec)
```

Now check, if our two testusers were inserted.

```sql
mysql> SELECT * FROM `prosody`.`prosody` WHERE 1; 
```

Output should look like this:

```
+-----------------+-----------+----------+----------+--------+---------+
| host            | user      | store    | key      | type   | value   |
+-----------------+-----------+----------+----------+--------+---------+
| jabber.hollo.me | testuser1 | accounts | password | string | secret1 |
| jabber.hollo.me | testuser2 | accounts | password | string | secret2 |
+-----------------+-----------+----------+----------+--------+---------+
2 rows in set (0.00 sec)
```

**Note:** By default the user's secrets are stored in plaintext. You can enable hashing in the config, see: 
[mod_auth_internal_hashed](http://prosody.im/doc/modules/mod_auth_internal_hashed)

