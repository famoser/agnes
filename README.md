# agnes

releases safely to various environments, and allows to perform other common tasks.

there is also a UI available at [famoser/agnes-ui](https://github.com/famoser/agnes-ui).

## commands

`php bin/agnes release v1.0 master` creates the release `v1.0` from the latest master  
`php bin/agnes deploy v1.0 *:*:dev` installs the release `v1.0` on all instances matching `*:*:dev`  
`php bin/agnes rollback *:*:dev` rolls back instances matching `*:*:dev` to the previous release  
`php bin/agnes copy:shared example:example.com:dev production` copies the shared data to the instance matching `example:example.com:dev` from the `production` stage

for details on the commands use the `--help` argument.

## config

By default, the file called `agnes.yml` in your project root is taken as configuration (use `--config-file` to change).

Additionally to the config file you can supply a config folder which contains:
- additional `.yml` files which will all be merged with the main config file (handy separate policies & server config )
- other files which are needed for the installation but not part of the repository (like `.env.local` files)

In the config files, you can use placeholders like `%env(KEY)` which are replaced by environment variables upon loading the config.
You can define environment variables in a `.env` or `.env.local` file in your project root.

Full example config:

```yml
agnes:
  build_target: # where the release will be built
    connection: # the connection
      type: local # can also be of type ssh, then additionally destination must be specified
    path: .build

github:
  api_token: '%env(GITHUB_API_TOKEN)%'
  repository: famoser/agnes

files:
  shared_folders: # these folders will be shared between releases
    - var/persistent

  # declare files you place in the configuration folder which are uploaded to the installation location 
  # files will be expected at <config folder>/server/environment/stage
  configuration_files: 
    # if deploying on example:example.com:staging, 
    # this file will be looked for at ./files/example/example.com/staging/.env.local
    # if its not found, the deployment will not be started (because its marked as required)
    - path: .env.local
      required: true

# scripts can be run using the agnes command or automatically at predefined hook points
# hook points are build, deploy, after_deploy, rollback, after_rollback
# you can additionally constrain the scripts to run only in specific environments
scripts:
    # build hook
    # executed on a freshly cloned repository
    # install dependencies, ...
    # produces a build which is used for a release or deployed to an installation 
    build:
        hook: build
        script:
          - composer install --verbose --prefer-dist --no-interaction --no-dev --optimize-autoloader --no-scripts
          - '{{php}} -v' # place
    
    # deploy hook
    # executed on the final location of the build, before putting it online
    # initialize caches, migrate databases, ...
    # if a previous installation exists it is indicated in $HAS_PREVIOUS_INSTALLATION (value either true or false)
    # if a previous installation exists then the path is given with $PREVIOUS_INSTALLATION_PATH
    # for example `if [[ "$HAS_PREVIOUS_INSTALLATION" == true ]]; then cp -r $PREVIOUS_INSTALLATION_PATH/var/transient var/transient; fi`
    deploy:
        hook: deploy
        script:
          - php bin/console doctrine:migrations:migrate -q
    
    # rollback hook
    # executed on the current instance before rolling back to the previous instance
    # revert migrations, invalidate cache, ...
    # the path of the previous installation is given in $PREVIOUS_INSTALLATION_PATH
    rollback:
        hook: rollback
        script:
          - echo "rollbacked"

    # executed right after the symlink changes
    restart_php:
        hooks: [after_deploy, after_rollback]
        script:
          - killall -9 php-cgi

    # execute commands on deploy hook, constrained to specific instances
    fixtures:
        hook: after_deploy
        instance_filter: *:*:dev
        script:
          - php bin/console doctrine:fixtures:load -q

# commands create tasks which are executed on a specific instance
# after the execution finishes, you can define the next following action
# you can constrain the proceeding action to specific instances
# only copy:shared actions are supported; within deploy / rollback actions     
tasks:
    prod_data_on_staging:
        after: deploy
        instance_filter: *:*:staging
        action: copy
        arguments: { source: production }

# instances are the target of your commands, each consisting of a server, an environment and a stage
# you can match here defined instances with the expression *:*:*
# the first part matches to the server name (with * matching all), here the only available server is "example"
# the second part matches to the environment name (with * matching all), here the only available environment is "example.com"
# the third part matches to the stage (with * matching all), here the available stages are dev, staging, education & production
instances:
  example:
    connection:
      type: ssh
      destination: 'admin@example.com'
      system: FreeBSD # or Linux (default)
    path: ~/www
    keep_installations: 2 # how many installations to keep besides the current one. the others are removed after deployment
    script_overrides:
      php: /usr/local/php73/bin/php # you can use a placeholder in your scripts like {{php}} which is replaced to the value here
    environments:
      example.com: [dev, staging, education, production]

# policies prevent actions to be executed that would be unsafe to do so from the perspective of the application
# for example, you can define here that it is not possible to deploy to production before the same release was not on a dev environment
policies:
  strategy: unanimous # all matching policies must be valid (no other options at the moment)
  allow_if_all_abstain: true # if no matching policy is found, the execution is allowed (no other options at the moment)

  deploy:
    # requires that to deploy to a higher stage, the same release must be deployed to the next lower stage
    # if dev has v0.1 deployed, it would be allowed to deploy this to staging but not to production or education
    - type: stage_write_up
      layers:
        0: [dev]
        1: [staging]
        2: [production, education]

  copy_shared:
    # requires that the target must be one stage lower that the source
    # in this case, allows production to overwrite dev but not the other way around 
    - type: stage_write_down
      layers:
        0: [dev, staging, education]
        1: [production]
    # requires that the target/source release have to match  
    - type: same_release
```

## advanced config

if you have an SSH connection configured
you can speed up command execution greatly by caching the connection in `~/.ssh/config`:

```
Host *
  ControlPath /tmp/ssh-%r@%h:%p
  ControlMaster auto
  ControlPersist yes
```
