# deployer

deploys releases to environments

## server config

enable caching of ssh connections by placing in `~/.ssh/config`:

```
Host *
  ControlPath /tmp/ssh-%r@%h:%p
  ControlMaster auto
  ControlPersist yes
```