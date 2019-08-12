# agnes

releases safely to various environments, and allows to perform other common tasks.

## config

look in `agnes.yml` for a complete example.

## commands

the following commands have been added:

`php console.php release` creates a github release  
`php console.php deploy` installs such a release  
`php console.php release` rolls back the to an older version of the release  
`php console.php copy:shared` copies the shared data from one place to another.

for details on the commands use the `--help` argument.


## local config

the tool repeatedly opens SSH connections if you have remote servers configured. To avoid the repeated overhead of creating a connection, enable caching in `~/.ssh/config`:

```
Host *
  ControlPath /tmp/ssh-%r@%h:%p
  ControlMaster auto
  ControlPersist yes
```