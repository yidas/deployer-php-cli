Deployer by PHP-CLI
=====

Linux rsync tool for code deployment running by PHP-CLI script.  
These rsync php scripts are helping developers to deploy codes from local instance to remote instances.

---

Demonstration
-----

Deploy local project to remote servers by just executing the deployer in BASH

```
$ ./deployer
```
Or you can call it by PHP-CLI:
```
$ php ./deployer
```

---

Installation
-----

Choose and copy the scripts you want from `/src` folder into Linux server, adding excute property to script files by `chmod +x`.  
The scripts including shell script for running php at the first line:
```
#!/usr/bin/php -q
```
You can customize it for correct php bin path in your environment, saving the file with [binary encode](#save-bin-file).

### Servers setting:

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

Script files list
-----

- `rsync`  
     Rsync a file or a folder from current local path to destination servers with the same path automatically, the current path is base on Linux's "pwd -P" command.

- `deployer`  
    Rsync a specified source folder to remote servers under the folder by setting path, supporting filtering files from excludeFiles.
    
    You need to do more setting for p2p directories in `rsyncStatic.php`:
    ```
    $config['sourceFile'] = '/home/www/www.project.com/webroot';
    $config['remotePath'] = '/home/www/www.project.com/';
    ```

---

How to use
-----

For `rsync`, you can put scripts in your home directory, and cd into the pre-sync file directory:

```
$ ~/rsync file.php      // Rsync file.php to servers with same path
$ ~/rsync folderA       // Rsync whole folderA to servers
$ ~/rsync ./            // Rsync current whole folder
$ ~/rsync ./ stage      // Rsync to servers in stage group
$ ~/rsync ./ prod       // Rsync to servers in prod group
```

For `deployer`, you need to set project folder path into the file with source & destination directory, then you can run it:
```
$ ./deployer            // Rsync to servers in default group
$ ./deployer stage      // Rsync to servers in stage group
$ ./deployer prod       // Rsync to servers in prod group
```

---

Addition usage
-----

- #### Rsync without password:  
    You can put your local user's SSH public key to destination server user for authorization.
    ```
    .ssh/id_rsa.pub >> .ssh/authorized_keys
    ```

- #### Save binary encode file: <a name="save-bin-file"></a>  
  
    While excuting script, if you get the error like `Exception: Zend Extension ./deployer does not exist`, you may save the script file with binary encode, which could done by using `vim`:

    ```
    :set ff=unix
    ```



