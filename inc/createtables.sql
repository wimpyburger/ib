CREATE TABLE boards (
	id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	title VARCHAR(150) NOT NULL,
	urlid VARCHAR(20) NOT NULL UNIQUE
);