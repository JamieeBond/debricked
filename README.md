# Debricked - Code Challenge

API application to return a list of products, that is also filterable.

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
bin/console doctrine:database:create
```
6. Run database migration, confirm with ***yes***:
```
bin/console doctrine:migrations:migrate
```
### Run Tests
1. Run:
```
bin/phpunit
```
![Screenshot](./docs/tests.png? "Test results")
### Usage
1. Change credentials in .env:
```
###> Debricked Login ###
DEBRICKED_USERNAME=!YourUsernameHere!
DEBRICKED_PASSWORD=!YourPasswordHere!
###< Debricked Login ###
```
2. Upload dependency files:
```
http://localhost:8888/upload
```
![Screenshot](./docs/upload.png? "Upload Successful")
3. Run command with optional triggers:
```
php bin/console app:run-scan --action_email=1 --trigger_scan_in_progress=1 --trigger_scan_is_complete=1 --trigger_vulnerabilities_greater_than=2 --trigger_cvss_greater_than=3
```
![Screenshot](./docs/run_command.png? "Scan Finished")
4. View email sent due to triggers being triggered:
```
http://localhost:8025/
```
![Screenshot](./docs/scan_email.png? "Email of Completed Scan")


