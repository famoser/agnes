# Developers

## Testplan

testplan should try every essential feature
assume instance with dev, staging, prod environments
enabled same_release, write_up and write_down policies

- `agnes deploy *:*:prod master` (fails due to write-up)
- `agnes deploy *:*:dev master` 
- `agnes deploy *:*:prod master`
- `agnes copy:shared *:*:prod dev` (fails due to write-down)
- `agnes copy:shared *:*:dev prod`
- `agnes release v1.0 master`
- `agnes deploy *:*:dev v1.0`
- `agnes deploy *:*:dev master`
- `agnes copy:shared *:*:dev prod` (fails due to same-release)
- `agnes rollback *:*:dev`
- `agnes copy:shared *:*:dev prod`
