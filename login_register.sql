create database login_register;
use login_register;

create table doctors (
 id INT AUTO_INCREMENT PRIMARY KEY,
 fname varchar(255),
 lname varchar(255),
 email VARCHAR(255) NOT NULL,
 specialization VARCHAR(255) NOT NULL,
 experience INT NOT NULL,
license_front VARCHAR(255) NOT NULL,
license_back VARCHAR(255) NOT NULL,
qualifications text not null,
education text not null,
password VARCHAR(255),  -- To store the hashed password
status ENUM('pending', 'approved', 'declined') DEFAULT 'pending',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);	
CREATE TABLE patients (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    date_of_birth DATE,
    gender ENUM('Male', 'Female', 'Other'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
-- Create appointments table
CREATE TABLE appointments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    patient_id INT NOT NULL,
    appointment_date DATE NOT NULL,
    appointment_time TIME NOT NULL,
    status ENUM('Scheduled', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
   FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE CASCADE
);
CREATE TABLE doctor_schedule (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    date DATE NOT NULL,
    time_slot VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    UNIQUE KEY unique_schedule (doctor_id, date, time_slot)
);
 

-- Create an index to improve query performance
CREATE INDEX idx_doctor_appointments ON appointments(doctor_id, appointment_date);
CREATE INDEX idx_patient_appointments ON appointments(patient_id, appointment_date);


CREATE TABLE admin (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL
);

select * from doctors;
ALTER TABLE doctors;
drop table doctors;
SELECT * FROM mhp WHERE status = 'pending';

INSERT INTO patients (name, email, phone, date_of_birth, gender) VALUES 
(
    'Jane Smith', 
    'jane.smith@example.com', 
    '+1234567890', 
    '1985-05-15', 
    'Female'
);

INSERT INTO appointments (doctor_id, patient_id, appointment_date, appointment_time, status) VALUES 
(
    1,  -- Assumes first doctor's ID 
    1,  -- Assumes first patient's ID
    CURRENT_DATE,  -- Today's date
    '14:30:00',    -- Example time
    'Scheduled'
);

INSERT INTO admin (username, password) VALUES ('admin', '$2y$10$QCrS4tHah8XJRJxSbRoCrenWuNyRD/021IEpUG6FtdjtoVKdXJhZ2');
DELETE FROM admin 
WHERE username = 'admin' 
AND password = '$2y$10$YourHashedPasswordHere';