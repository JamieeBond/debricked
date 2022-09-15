# Debricked - Code Challenge

## Summary
An API dependency vulnerability rule engine. Dependency files are uploaded then forwarded to [Debricked's](https://debricked.com/) API. Then the scan command will send a email action, based on triggers which have been set when the command is ran.

## Documentation

### Set up
1. Clone the repository:
```
git clone https://github.com/JamieeBond/debricked.git
```
2. Change directory:
```
cd debricked
```
3. Start the environment:
```
docker-compose up
```
3. Access the environment:
```
docker-compose exec php bash
```
4. Install dependencies:
```
composer install
```
5. Create the database:
```
php bin/console doctrine:database:create
```
6. Run database migration, confirm with ***yes***:
```
php bin/console doctrine:migrations:migrate
```
### Run Tests
1. Run:
```
php bin/phpunit
```
![Screenshot](./docs/tests.png? "Test results")
### Usage
1. Change Debricked credentials in .env:
```
###> Debricked Login ###
DEBRICKED_USERNAME=!YourUsernameHere!
DEBRICKED_PASSWORD=!YourPasswordHere!
###< Debricked Login ###
```
2. Change email addresses in .env (optional):
```
###> Email Action Address ###
EMAIL_ACTION_FROM=your-email-action-from@db-scanner.jb
EMAIL_ACTION_TO=your-email-action-to@db-scanner.jb
###< Email Action Address ###
```

3. **POST** dependency files with [Postman](https://www.postman.com/) to:
```
http://localhost:8888/upload
```
**Required in the body:**
- **repositoryName** = The name of the Repository
- **commitName** = The name of the commit
- **files[]** = An array of files

![Screenshot](./docs/upload.png? "Upload Successful")
4. Run command with optional triggers *(to automate, the command can be ran as a cron)*:
```
php bin/console app:run-scan --action_email=1 --trigger_scan_in_progress=1 --trigger_scan_is_complete=1 --trigger_vulnerabilities_greater_than=2 --trigger_cvss_greater_than=3
```
**Optional options:**

***Actions:***
- **--action_email=1** = Sends an email if any set triggers are triggered

***Triggers*** *(An action is required e.g. --action_email=1, to be alerted if a trigger is triggered )*:
- **--trigger_scan_in_progress=1** = Trigger if a scan is still in progress
- **--trigger_scan_is_complete=1** = Trigger if a scan is complete
- **--trigger_vulnerabilities_greater_than=2** = Trigger when vulnerabilities are greater than the given number
- **--trigger_cvss_greater_than=3** = Trigger when cvvs are greater than the given number

![Screenshot](./docs/run_command.png? "Scan Finished")
5. View email(s) sent due to triggers being triggered:
```
http://localhost:8025/
```
![Screenshot](./docs/scan_email.png? "Email of Completed Scan")


