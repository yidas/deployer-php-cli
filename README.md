# rsync-cli.php

Linux rsync tool running by PHP script.  
These rsync php scripts are helping developers to deploy codes from local instance to remote instances.

---

## Installation

Choose and copy the scripts you want from `/scripts` folder to Linux, adding excute property to script files by `chmod +x`.  
The scripts including shell script for running php at the first line:
```
#!/usr/bin/php -q
```
You can customize it for correct php bin path in your environment, saving the file with binary encode.

#### Servers setting
you need to set up the target servers' hostname or IP into the script file:

```
// Config: Distant server list
$config['serverList'] = [
    'default' => [],
    'stage' => [
        '110.1.1.1',
    ],
    'prod' => [
        '110.1.2.1',
    ],
];
```

---

## Script files list

- `rsync.php`  
    Rsync a file or a folder from current path to destinate servers with the same path automatically, the current path is base on Linux's "pwd -P" command.

- `rsyncStatic.php`  
    Rsync a specified source folder to destinate servers under the setting path, supporting filtering files from excludeList.

---

## How to use

For `rsync.php`, you can put scripts in your home directory, and cd into the pre-sync file directory:

```
$ ~/rsync.php file.php      // Rsync file.php to servers with same path
$ ~/rsync.php folderA       // Rsync whole folderA to servers
$ ~/rsync.php ./            // Rsync current whole folder
$ ~/rsync.php ./ stage      // Rsync to servers in stage group
```

For `rsyncStatic.php`, you need to set project folder path into the file with source & destination directory, then you can run it:
```
$ ./rsyncStatic.php           // Rsync to servers in default group
$ ./rsyncStatic.php stage     // Rsync to servers in stage group
```

---

## Addition usage

#### - Rsync without password:
You can put your local user's SSH public key to destination server user for authorization.
```
.ssh/id_rsa.pub >> .ssh/authorized_keys
```


