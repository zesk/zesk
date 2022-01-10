CREATE DATABASE IF NOT EXISTS testdb;

-- CREATE USER IF NOT EXISTS testuser@'%' IDENTIFIED WITH mysql_native_password BY 'test-password';

CREATE USER IF NOT EXISTS testuser@'%' IDENTIFIED BY 'test-password';
GRANT ALL PRIVILEGES ON testdb.* TO testuser@'%';

CREATE USER IF NOT EXISTS testreaduser@'%' IDENTIFIED BY 'test-read-password';
GRANT SELECT, LOCK TABLES ON testdb.* TO testreaduser@'%';

FLUSH PRIVILEGES;
