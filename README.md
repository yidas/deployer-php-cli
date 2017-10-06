Deployer *by PHP-CLI*
=====================

Code deployment tool based on RSYNC running by PHP-CLI script

FEATURES
--------

***1. Deploy to multiple servers by groups***

***2. Support Git updating for source project***

***3. Support Composer updating for source project***

***4. Support filter for excluding specified files***

***5. Other useful script tools provided***

These rsync php scripts are helping developers to deploy codes from local instance to remote instances.

---

DEMONSTRATION
-------------

Deploy local project to remote servers by just executing the deployer in command:

```
$ ./deployer
```
Or you can call it by PHP-CLI:
```
$ php ./deployer
```

The result could like be:
```
/* --- Git Process Start --- */
Already up-to-date.
/* --- Git Process End --- */

/* --- Rsync Process Start --- */
[Process]: 1
[Group  ]: default
[Server ]: 127.0.0.1
[User   ]: nick_tsai
[Source ]: /home/www/projects/deployer-php-cli
[Remote ]: /var/www/html/projects/
[Command]: rsync -av --delete --exclude "web/upload" --exclude "runtime/log" /home/www/projects/deployer-php-cli nick_tsai@127.0.0.1:/var/www/html/projects/
[Message]:
sending incremental file list
deployer-php-cli/index.php

sent 149,506 bytes  received 814 bytes  60,128.00 bytes/sec
total size is 45,912,740  speedup is 305.43
/* --- Rsync Process End ---  */
```

---

INSTALLATION
------------

Choose and copy the scripts you want from `/src` folder into Linux server, adding excute property to script files by `chmod +x`.  
The scripts including shell script for running php at the first line:
```
#!/usr/bin/php -q
```
You can customize it for correct php bin path in your environment, saving the file with [binary encode](#save-bin-file).

### Servers Setting:

You need to set up the target servers' hostname or IP into the script file:

```
$config['remoteServers'] = [
    'default' => [
        '110.1.1.1',
        '110.1.2.1',
    ],
    'stage' => [
        '110.1.1.1',
    ],
    'prod' => [
        '110.1.2.1',
    ],
];
```

Also, the remote server user need to be assigned:

```
$config['remoteUser'] = 'www-data';
```

---

SCRIPT FILES
------------

- **deployer**   
    Rsync a specified source folder to remote servers under the folder by setting path, supporting filtering files from excludeFiles.
    
    You need to do more setting for p2p directories in `rsyncStatic.php`:
    ```
    $config['sourceFile'] = '/home/www/www.project.com/webroot';
    $config['remotePath'] = '/home/www/www.project.com/';
    ```
    
- **mirror**  
     Rsync a file or a folder from current local path to destination servers with the same path automatically, the current path is base on Linux's "pwd -P" command.

---

USAGE
-----

### deployer

For `deployer`, you need to set project folder path into the file with source & destination directory, then you can run it:
```
$ ./deployer            // Rsync to servers in default group
$ ./deployer stage      // Rsync to servers in stage group
$ ./deployer prod       // Rsync to servers in prod group
```


### mirror

For `mirror`, you can put scripts in your home directory, and cd into the pre-sync file directory:

```
$ ~/mirror file.php      // Rsync file.php to servers with same path
$ ~/mirror folderA       // Rsync whole folderA to servers
$ ~/mirror ./            // Rsync current whole folder
$ ~/mirror ./ stage      // Rsync to servers in stage group
$ ~/mirror ./ prod       // Rsync to servers in prod group
```

---

ADDITION
--------

### Rsync without Password:  

You can put your local user's SSH public key to destination server user for authorization.
```
.ssh/id_rsa.pub >> .ssh/authorized_keys
```

### Save Binary Encode File:  
  
While excuting script, if you get the error like `Exception: Zend Extension ./deployer does not exist`, you may save the script file with binary encode, which could done by using `vim`:

```
:set ff=unix
```



