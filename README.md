# agnes

Release and deploy safely.

Install with 
```bash
composer require famoser/agnes --dev
```

Then run commands
```bash
php vendor/bin/agnes release v1.0 master`
```

## commands


| Command    | Example |
| ---------- | ------- |
| `release`  | `release v1.0 main` creates the release `v1.0` from the main branch |
| `deploy`   | `deploy *:*:dev v1.0` installs the release `v1.0` on all instances matching `*:*:dev` |
| `rollback` | `rollback *:*:dev` rolls back instances matching `*:*:dev` to the previous release |
| `copy`     | `copy example:example.com:dev production` copies the shared data to the instance `example:example.com:dev` from the `production` stage |
| `build`    | `build master` builds the master release; useful to test the build script |
| `run`      | `run *:*:dev my_script` runs the script called `my_script` on the `*:*:dev` instances |
| `build`    | `build main` builds the release from the main branch |
| `clear`    | `clear *:*:dev` clears surplus & invalid installations on the `*:*:dev` instances |

for details on the commands use the `--help` argument.  
to easily remember the order of arguments observe that the target is always first.

## config

By default, the file called `agnes.yml` in your project root is taken as configuration (use `--config-file` to change).

Additionally to the config file you can supply a config folder which contains:
- additional `.yml` files which will all be merged with the main config file (handy separate policies & server config )
- other files which are needed for the installation but not part of the repository (like `.env.local` files)

In the config files, you can use placeholders like `%env(KEY)` which are replaced by environment variables upon loading the config.
You can define environment variables in a `.env` or `.env.local` file in your project root.

Full example config in [sample.yml](sample.yml).

## advanced config

if you have an SSH connection configured you can speed up command execution greatly by caching the connection in `~/.ssh/config`:

```
Host *
  ControlPath /tmp/ssh-%r@%h:%p
  ControlMaster auto
  ControlPersist yes
```
