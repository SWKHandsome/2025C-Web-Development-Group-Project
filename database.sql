SET NAMES utf8mb4;
SET time_zone = '+08:00';

DROP TABLE IF EXISTS lost_items;
DROP TABLE IF EXISTS packages;
DROP TABLE IF EXISTS users;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role ENUM('student','admin') NOT NULL,
    student_id VARCHAR(30) DEFAULT NULL,
    staff_id VARCHAR(30) DEFAULT NULL,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    faculty VARCHAR(80) DEFAULT NULL,
    office VARCHAR(80) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    last_login DATETIME DEFAULT NULL,
    avatar_path VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE packages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT NOT NULL,
    recipient_name VARCHAR(120) NOT NULL,
    tracking_number VARCHAR(80) NOT NULL,
    parcel_code VARCHAR(40) DEFAULT NULL,
    courier ENUM('Lalamove','Lazada','Shopee','Pos Laju','Other') DEFAULT 'Other',
    arrival_at DATETIME NOT NULL,
    deadline_at DATETIME NOT NULL,
    status ENUM('pending','collected') DEFAULT 'pending',
    collected_at DATETIME DEFAULT NULL,
    collected_by_name VARCHAR(120) DEFAULT NULL,
    collected_by_student_id VARCHAR(30) DEFAULT NULL,
    shelf_code VARCHAR(40) DEFAULT NULL,
    notes TEXT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_packages_student FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_packages_recorder FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE lost_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    item_name VARCHAR(120) NOT NULL,
    description TEXT,
    found_location VARCHAR(120) NOT NULL,
    found_at DATETIME NOT NULL,
    expiry_at DATETIME NOT NULL,
    status ENUM('pending','collected') DEFAULT 'pending',
    photo_path VARCHAR(255) DEFAULT NULL,
    storage_location VARCHAR(80) DEFAULT NULL,
    recorded_by INT NOT NULL,
    claimed_by INT DEFAULT NULL,
    claimed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_lost_recorded FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_lost_claimed FOREIGN KEY (claimed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO users (role, student_id, staff_id, full_name, email, phone, faculty, office, password_hash)
VALUES
('admin', NULL, 'ADM001', 'Aida Rahman', 'admin@campus.local', '03-88775566', 'Logistics Office', 'Parcel Hub', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student', 'S1234567', NULL, 'Jason Lim', 'jason@campus.local', '012-5558888', 'School of Business', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'),
('student', 'S7654321', NULL, 'Emily Tan', 'emily@campus.local', '011-2233445', 'Faculty of Computing', NULL, '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');

INSERT INTO packages (student_id, recipient_name, tracking_number, parcel_code, courier, arrival_at, deadline_at, status, shelf_code, notes, recorded_by)
VALUES
(2, 'Jason Lim', 'LM123MY908', 'PKG-221', 'Lalamove', NOW() - INTERVAL 1 DAY, NOW() + INTERVAL 29 DAY, 'pending', 'Rack B-10', 'Requires student card verification.', 1),
(3, 'Emily Tan', 'SHOPEE77881', 'PKG-310', 'Shopee', NOW() - INTERVAL 4 DAY, NOW() + INTERVAL 24 DAY, 'pending', 'Locker C-04', 'Fragile electronics.', 1),
(2, 'Jason Lim', 'LAZ221144', 'PKG-098', 'Lazada', NOW() - INTERVAL 50 DAY, NOW() - INTERVAL 10 DAY, 'collected', 'Rack A-02', 'Collected during counter session.', 1);

INSERT INTO lost_items (item_name, description, found_location, found_at, expiry_at, status, photo_path, storage_location, recorded_by)
VALUES
('Black Backpack', 'Contains lecture notes and a USB drive.', 'Library Level 2', NOW() - INTERVAL 2 DAY, NOW() + INTERVAL 26 DAY, 'pending', NULL, 'Cabinet L1', 1),
('Sports Bottle', 'Blue bottle with sticker.', 'Sports Complex foyer', NOW() - INTERVAL 10 DAY, NOW() + INTERVAL 20 DAY, 'pending', NULL, 'Shelf S3', 1);
