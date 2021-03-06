# scheduleCompare
*A PHP-based web application to allow students to compare high school schedules quickly and easily.*

## November 2012 Update
For prospective employers and other interested parties:
This project is old, outdated, and to be quite honest, coded with the grace of a socially awkward penguin trying to run a marathon (read: really, really poorly). I wrote it very quickly several years ago while I was still in high school. 

If I still had a use for the project, I would go back and re-build it from the ground up using something sensible and modern, like Ruby on Rails, Django, or at the very least an MVC approach with OO PHP. I’m leaving it available in case anyone wants to take a look (and it is pretty cool from the front-end), but let it be known that I am well aware that it violates pretty much every programming best practice in the book. With that said, the original content of the readme follows...

## Readme
This is the first release of scheduleCompare, a PHP-based web application that allows students to compare school schedules quickly and easily.

There is still a lot of site-specific code (mainly HTML and CSS), and the back-end code is in an absolutely horrible state, with no OOP or proper programming practices whatsoever.

To install this, upload all the files to the directory of your choosing, then
create a new MySQL database using the tool of your choice and import the `db_schema.sql` file through phpMyAdmin or another tool. The `db_schema.sql` file contains values for the classes and teachers at Eden Prairie High School. You will need to clear these and populate the database with the appropriate data if you're going to use it with another school.

You will need to set the appropriate variables in `includes/config.php`. The base domain is important for cookies, sessions, and various redirects to work correctly. You'll also want to generate a long random value for the salt.

I will provide more detailed instructions in the future, this is just a quickie.