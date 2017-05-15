# presence-bot

Presence is a slack bot that tells you who is in the office. Park it on a 
raspberry pi, hide it in a cupboard.

## prerequisites

`php7`, `mysql`, cuz I'm lazy

## set up
1. After cloning this repository run `composer install`
2. copy the `.env.example` with your own information to `.env`
3. run `php presence database` to set up the database.
4. Set up a minutely cron task under root that runs `php presence 
scan`.
5. `php presence bot` will start up the bot, it needs root permision to do 
the arp scan, so either sudo or use root. Make sure you have a valid token!
5. Verify the bot works via `@presence help`

## bot commands
the bot is triggered via `presence` or `@{thbotsusername}`
 - `register xx:xx:xx:xx:xx:xx` to associate yourself with a mac address
 - `remove xx:xx:xx:xx:xx:xx` to undo previous association
 - `who is here`, `whoishere`, `who's here` I'll let you know who is in the house
 - `top x` listing of the most active users 1-10
 - `whoami` tells you what devices are registered to you.

## possible things to do
- A command that tells you when a user was there the last time (e.g: mawk was seen  3 days ago)
- Bot can see a user connected that wasn't there earlier and send an announcement (mawk arrived!)
- Assign labels to each device of yours.
- Register devices so they are ignored (other pis, printers etc...)
- Suggestions? create an issue!
