# agnes

Release and deploy safely.

Install with 
```bash
composer require famoser/agnes --dev
```

Then run commands
```bash
php vendor/bin/agnes release v1.0 main`
```

## commands


| Command    | Example                                                                                                                           |
|------------|-----------------------------------------------------------------------------------------------------------------------------------|
| `build`    | `build maint` builds the release from the `main` branch                                                                           |
| `release`  | `release v1.0 main` creates the release `v1.0` from the `main` branch                                                             |
| `deploy`   | `deploy *:*:dev v1.0` installs on all instances matching `*:*:dev` the release `v1.0`                                             |
| `rollback` | `rollback *:*:dev` rolls back instances matching `*:*:dev` to the previous release                                                |
| `clear`    | `clear *:*:dev` clears surplus & invalid installations on the `*:*:dev` instances                                                 |
| `copy`     | `copy example:example.com:dev production` copies the shared data to the instance `example:example.com:dev` its `production` stage |
| `run`      | `run *:*:dev my_script` runs the script called `my_script` on the `*:*:dev` instances                                             |

for details on the commands use the `--help` argument.  
to easily remember the order of the arguments, observe that the target is always first.

## config

By default, the file called `agnes.yml` in your project root is taken as configuration (use `--config-file` to change).

Further, you may supply a config folder which contains:
- additional `.yml` files which will all be merged with the main config file (handy to separate policies and server config)
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
