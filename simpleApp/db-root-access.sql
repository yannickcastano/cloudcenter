USE mysql;
UPDATE user set authentication_string=PASSWORD("S3cur1ty01") where User='root';
UPDATE user set plugin="mysql_native_password" where User='root';
FLUSH privileges;
