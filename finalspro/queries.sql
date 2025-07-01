USE PTprocessing;

-- USERS Table
CREATE TABLE Users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(10) UNIQUE,
    email_address VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    user_type ENUM('Admin_Secretary', 'partTime_Professor') NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

SELECT * FROM Users;

DELETE FROM users
WHERE user_id = 24;

UPDATE users
SET email_address = "diosajane@gmail.com"
WHERE user_id = 3;

-- Insert employee
INSERT INTO Users (email_address, password, user_type)
VALUES (
    'jennypatanag@gmail.com', 
    '123', 
    'Admin_Secretary'
);

INSERT INTO Users (email_address, password, user_type)
VALUES (
    'prof@gmail.com', 
    '321', 
    'partTime_Professor'
);

-- ADMIN SECRETARY table
CREATE TABLE Admin_Secretary (
    admin_id INT PRIMARY KEY,             
    employee_id VARCHAR(10) NOT NULL,      
    lastname VARCHAR(50) NOT NULL,
    firstname VARCHAR(50) NOT NULL,
    image LONGBLOB,
    FOREIGN KEY (admin_id) REFERENCES Users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (employee_id) REFERENCES Users(employee_id) ON DELETE CASCADE
);

SELECT * FROM  admin_secretary;

INSERT INTO Admin_Secretary (admin_id, employee_id, lastname, firstname, image)
VALUES (3, '0000003', 'Diosa', 'Ruby Jane', NULL);

DELETE FROM admin_secretary
WHERE user_id = 1;

DROP TABLE admin_secretary;

-- PART TIME PROFESSOR table
CREATE TABLE PartTime_Professor (
    professor_id INT AUTO_INCREMENT PRIMARY KEY,
    email_address VARCHAR(100) UNIQUE NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    middle_initial CHAR(1),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

SELECT * FROM  PartTime_Professor;

DELETE FROM PartTime_Professor
WHERE professor_id = 26;

DROP TABLE PartTime_Professor;


