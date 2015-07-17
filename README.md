Wordpress DB Migrate
====================

This is a really simple php script I run locally when migrating a wordpress site somewhere to work on it locally. Wordpress stores a bunch of stuff as serialized data so a simple search/replace of the database for a domain won't cut it.

Just load it in a browser and input the database connection details and what you want to search and replace for and it **should** parse serialized arrays and objects to replace any matches and serialize them up again.

This doesn't solve 100% of the migration issues and it's hardly very robust and secure but it does enough for me that it's not a huge headache and I'll try to update it to be cleaner and more secure as time allows. I welcome any advise/feedback.