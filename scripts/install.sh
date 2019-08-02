download release artifact
unzip release artifact in folder /target/releases/$release_name or variations
create shared folders
set writable folders
set .env file
execute warmup
disable current release (or not?)
execute mirations
switch to new release
clean up old releases

-> need state file inside release folder
{ 
    "release": "v1.2", # release version
    "successful": true, # detect for automatic cleanup if enough releases available; detect errors with install & allow retry
    "online": [{
        "start": "2019-01-01T20:00:00"
        "end": null
    }]
}