# Develop

Features:
- support for cronjob definitions. while cronjobs become necessary on the application level, they need to be configured per instance (e.g., some cronjobs run more often in prod than in staging)
- more fine-granular copy:shared-data configuration. Some shared folders should be excluded from copying (e.g., caches)

Next major refactoring:
- cleanly separate task generation from execution; would allow to create a simulator for testing / previewing all executed commands
