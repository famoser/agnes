# agnes

releases safely to various environments, and allows to perform other common tasks.

## commands

`php console.php release v1.0 master` creates the release `v1.0` from the latest master  
`php console.php deploy v1.0 *:*:dev` installs the release `v1.0` on all instances matching `*:*:dev`  
`php console.php rollback *:*:dev` rolls back instances matching `*:*:dev` to the previous release
`php console.php copy:shared example:example.com:production example:example.com:dev` copies the shared data from the instance matching `example:example.com:production` to `example:example.com:dev`

for details on the commands use the `--help` argument.

## config

By default, the file called `agnes.yml` in your project root is taken as configuration (use `--config-file` to change).

Additionally to the config file you can supply a config folder which contains:
- additional `.yml` files which will all be merged with the main config file (handy separate policies & server config )
- other files which are needed for the installation but not part of the repository (like `.env.local` files)

In the config files, you can use placeholders like `%env(KEY)` which are replaced by environment variables upon loading the config.
You can define environment variables in a `.env` or `.env.local` file in your project root.

Full example config:

```
agnes:
  github_api_token: '%env(GITHUB_API_TOKEN)%'
  build_target: # where the release will be built
    connection: # the connection
      type: local # can also be of type ssh, then additionally destination must be specified
    path: .build

application:
  repository: famoser/agnes

  shared_folders: # these folders will be shared between releases
    - var/persistent

  # declare files not part of the repository. 
  # these files will be taken from the config folder from within ./files/server/environment/stage
  files: 
    # if deploying on example:example.com:staging, 
    # this file will be looked for at ./files/example/example.com/staging/.env.local
    # if its not found, the deployment will not be started (because its marked as required)
    - path: .env.local
      required: true

  scripts:
    # executed on a freshly cloned repository
    # prepare the application for deployment; gathering dependencies & such
    release:  
      - composer install --verbose --prefer-dist --no-interaction --no-dev --optimize-autoloader --no-scripts
      - '{{php}} -v' # place

    # executed after the release packet from above is put in its final location, before putting it online
    # initialize caches, migrate databases & other
    deploy:
      - echo "deployed"

    # executed on the current instance before rolling back to the previous instance
    # the path of the previous instance is given by the environment variable $PREVIOUS_RELEASE_PATH
    # execute migrations
    rollback:
      - echo "rollbacked"

# the servers define where your application will be deployed
# you can match here defined instances with the expression *:*:*
# the first part matches to the server name (with * matching all), here the only available server is "example"
# the second part matches to the environment name (with * matching all), here the only available environment is "example.com"
# the third part matches to the stage (with * matching all), here the available stages are dev, staging, education & production
servers:
  # the server
  example:
    connection:
      type: ssh
      destination: 'admin@example.com'
      system: FreeBSD # or Linux (default)
    path: ~/www
    keep_releases: 2 # how many releases to keep besides the current one. the others are removed after deployment
    script_overrides:
      php: /usr/local/php73/bin/php # you can use a placeholder in your scripts like {{php}} which is replaced to the value here
    environments:
      example.com: [dev, staging, education, production]

# policies prevent actions to be executed that would be unsafe to do so from the application perspective
# for example, you can define here that it is not possible to deploy to production before the same release was not on a dev environment
policies:
  strategy: unanimous # all matching policies must be valid. no other options at the moment
  allow_if_all_abstain: true # if no matching policy is found, the execution is allowed. no other options at the moment

  deploy:
    # requires that to deploy to a higher stage, the same release must be deployed to the next lower stage
    # if dev has v0.1 deployed, it would be allowed to deploy this to staging but not to production or education
    - type: stage_write_up
      layers:
        0: [dev]
        1: [staging]
        2: [production, education]
    
    # requires that releases deployed to the specified environments fulfil a commitish constraint
    # in this case, can only deploy releases created from the master branch to production, education or staging
    - type: release_whitelist
      filter: # restrict where to apply the policy to
        stages: [production, education, staging] # restrict to specific stages
        # environments: [example.com] can also restrict to environments
        # servers: [example] can also restrict to servers
      commitishes: [master]

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