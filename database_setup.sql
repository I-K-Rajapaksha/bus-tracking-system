-- ============================================================
-- Bus Arrival and Departure Tracking System
-- Database Setup Script for Makumbura Multimodal Center
-- Database: terminal_tracking_system
-- ============================================================

-- Create database
CREATE DATABASE IF NOT EXISTS terminal_tracking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE terminal_tracking_system;

-- ============================================================
-- Table: users
-- Description: User accounts with role-based access control
-- Flexible server
-- Administrator login = bustrackadmin
-- Password = Isu@0724
-- ============================================================
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    user_role ENUM('super_admin', 'terminal_in_operator', 'terminal_out_operator', 'report_viewer') NOT NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    created_by INT,
    INDEX idx_username (username),
    INDEX idx_user_role (user_role),
    INDEX idx_is_active (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: routes
-- Description: Master data for bus routes
-- ============================================================
CREATE TABLE IF NOT EXISTS routes (
    route_id INT AUTO_INCREMENT PRIMARY KEY,
    route_number VARCHAR(20) NOT NULL UNIQUE,
    route_name VARCHAR(100) NOT NULL,
    origin VARCHAR(100) NOT NULL,
    destination VARCHAR(100) NOT NULL,
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_route_number (route_number),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: buses
-- Description: Registered buses with operator information
-- ============================================================
CREATE TABLE IF NOT EXISTS buses (
    bus_id INT AUTO_INCREMENT PRIMARY KEY,
    bus_number VARCHAR(20) NOT NULL UNIQUE,
    route_id INT,
    operator_name VARCHAR(100),
    capacity INT,
    bus_type VARCHAR(50),
    remarks TEXT,
    is_active TINYINT(1) DEFAULT 1,
    registration_date DATE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT,
    INDEX idx_bus_number (bus_number),
    INDEX idx_route_id (route_id),
    INDEX idx_is_active (is_active),
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: bus_arrivals
-- Description: Records of bus arrivals at the terminal
-- ============================================================
CREATE TABLE IF NOT EXISTS bus_arrivals (
    arrival_id INT AUTO_INCREMENT PRIMARY KEY,
    bus_number VARCHAR(20) NOT NULL,
    route_id INT,
    bus_id INT NULL,
    arrival_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    entry_method ENUM('registered', 'manual') NOT NULL DEFAULT 'registered',
    operator_name VARCHAR(100),
    remarks TEXT,
    status ENUM('in_terminal', 'departed') DEFAULT 'in_terminal',
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_bus_number (bus_number),
    INDEX idx_route_id (route_id),
    INDEX idx_arrival_datetime (arrival_datetime),
    INDEX idx_status (status),
    INDEX idx_recorded_by (recorded_by),
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE SET NULL,
    FOREIGN KEY (bus_id) REFERENCES buses(bus_id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: bus_departures
-- Description: Records of bus departures from the terminal
-- ============================================================
CREATE TABLE IF NOT EXISTS bus_departures (
    departure_id INT AUTO_INCREMENT PRIMARY KEY,
    arrival_id INT NOT NULL,
    bus_number VARCHAR(20) NOT NULL,
    route_id INT,
    departure_datetime TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    dwell_time_minutes INT,
    recorded_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_arrival_id (arrival_id),
    INDEX idx_bus_number (bus_number),
    INDEX idx_route_id (route_id),
    INDEX idx_departure_datetime (departure_datetime),
    INDEX idx_recorded_by (recorded_by),
    FOREIGN KEY (arrival_id) REFERENCES bus_arrivals(arrival_id) ON DELETE CASCADE,
    FOREIGN KEY (route_id) REFERENCES routes(route_id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(user_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: audit_logs
-- Description: System audit trail for all user actions
-- ============================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action_type VARCHAR(50) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    action_description TEXT,
    ip_address VARCHAR(45),
    user_agent VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_created_at (created_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Table: system_settings
-- Description: Application configuration settings
-- ============================================================
CREATE TABLE IF NOT EXISTS system_settings (
    setting_id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT,
    setting_description VARCHAR(255),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    INDEX idx_setting_key (setting_key),
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- Insert Default Super Admin Account
-- Username: admin
-- Password: Admin@123 (CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN)
-- ============================================================
INSERT INTO users (username, password_hash, full_name, email, user_role, is_active) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'admin@mmck.lk', 'super_admin', 1);

-- ============================================================
-- Insert Sample Routes
-- ============================================================
INSERT INTO routes (route_number, route_name, origin, destination, is_active, created_by) VALUES
('138', 'Colombo - Puttalam', 'Colombo Fort', 'Puttalam', 1, 1),
('1', 'Colombo - Matara', 'Colombo Fort', 'Matara', 1, 1),
('2', 'Colombo - Galle', 'Colombo Fort', 'Galle', 1, 1),
('3', 'Colombo - Panadura', 'Colombo Fort', 'Panadura', 1, 1),
('100', 'Colombo - Badulla', 'Colombo Fort', 'Badulla', 1, 1),
('177', 'Colombo - Kandy', 'Colombo Fort', 'Kandy', 1, 1),
('245', 'Colombo - Kurunegala', 'Colombo Fort', 'Kurunegala', 1, 1),
('99', 'Colombo - Ratnapura', 'Colombo Fort', 'Ratnapura', 1, 1);

-- ============================================================
-- Insert Sample Registered Buses
-- ============================================================
INSERT INTO buses (bus_number, route_id, operator_name, capacity, bus_type, is_active, created_by) VALUES
('WP-CAA-1234', 1, 'SLTB Western Province', 52, 'Standard', 1, 1),
('WP-CAA-5678', 1, 'SLTB Western Province', 52, 'Standard', 1, 1),
('WP-CBB-2345', 2, 'Private Operator - Silva Transport', 48, 'Luxury', 1, 1),
('WP-CBB-6789', 2, 'Private Operator - Silva Transport', 48, 'Luxury', 1, 1),
('WP-CCC-3456', 3, 'SLTB Western Province', 52, 'Standard', 1, 1),
('SP-AAA-1111', 4, 'SLTB Southern Province', 52, 'Standard', 1, 1),
('CP-BBB-2222', 5, 'SLTB Central Province', 48, 'Semi-Luxury', 1, 1),
('NWP-CCC-3333', 6, 'SLTB North Western Province', 52, 'Standard', 1, 1);

-- ============================================================
-- Insert System Settings
-- ============================================================
INSERT INTO system_settings (setting_key, setting_value, setting_description, updated_by) VALUES
('system_name', 'Bus Tracking System - Makumbura MMC', 'Application display name', 1),
('session_timeout', '3600', 'Session timeout in seconds (1 hour)', 1),
('date_format', 'Y-m-d H:i:s', 'Default date/time format', 1),
('timezone', 'Asia/Colombo', 'System timezone', 1),
('max_login_attempts', '5', 'Maximum failed login attempts before lockout', 1),
('enable_audit_log', '1', 'Enable audit logging (1=Yes, 0=No)', 1);

-- ============================================================
-- Create Views for Reporting
-- ============================================================

-- View: Current buses in terminal
CREATE OR REPLACE VIEW vw_buses_in_terminal AS
SELECT 
    ba.arrival_id,
    ba.bus_number,
    r.route_number,
    r.route_name,
    ba.arrival_datetime,
    TIMESTAMPDIFF(MINUTE, ba.arrival_datetime, NOW()) as minutes_in_terminal,
    ba.operator_name,
    ba.entry_method,
    u.full_name as recorded_by_name
FROM bus_arrivals ba
LEFT JOIN routes r ON ba.route_id = r.route_id
LEFT JOIN users u ON ba.recorded_by = u.user_id
WHERE ba.status = 'in_terminal'
ORDER BY ba.arrival_datetime DESC;

-- View: Today's movements summary
CREATE OR REPLACE VIEW vw_today_summary AS
SELECT 
    DATE(arrival_datetime) as date,
    COUNT(DISTINCT arrival_id) as total_arrivals,
    COUNT(DISTINCT CASE WHEN status = 'departed' THEN arrival_id END) as total_departures,
    COUNT(DISTINCT CASE WHEN status = 'in_terminal' THEN arrival_id END) as currently_in_terminal,
    COUNT(DISTINCT CASE WHEN entry_method = 'registered' THEN arrival_id END) as registered_entries,
    COUNT(DISTINCT CASE WHEN entry_method = 'manual' THEN arrival_id END) as manual_entries
FROM bus_arrivals
WHERE DATE(arrival_datetime) = CURDATE()
GROUP BY DATE(arrival_datetime);

-- ============================================================
-- Stored Procedures
-- ============================================================

DELIMITER //

-- Procedure: Record bus arrival
CREATE PROCEDURE sp_record_arrival(
    IN p_bus_number VARCHAR(20),
    IN p_route_id INT,
    IN p_entry_method VARCHAR(20),
    IN p_operator_name VARCHAR(100),
    IN p_remarks TEXT,
    IN p_recorded_by INT
)
BEGIN
    DECLARE v_bus_id INT;
    DECLARE v_existing_arrival INT;
    
    -- Check if bus is already in terminal
    SELECT arrival_id INTO v_existing_arrival
    FROM bus_arrivals
    WHERE bus_number = p_bus_number AND status = 'in_terminal'
    LIMIT 1;
    
    IF v_existing_arrival IS NOT NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Bus is already in terminal';
    END IF;
    
    -- Get bus_id if registered
    SELECT bus_id INTO v_bus_id
    FROM buses
    WHERE bus_number = p_bus_number AND is_active = 1
    LIMIT 1;
    
    -- Insert arrival record
    INSERT INTO bus_arrivals (
        bus_number, route_id, bus_id, entry_method, 
        operator_name, remarks, recorded_by
    ) VALUES (
        p_bus_number, p_route_id, v_bus_id, p_entry_method,
        p_operator_name, p_remarks, p_recorded_by
    );
    
    SELECT LAST_INSERT_ID() as arrival_id;
END //

-- Procedure: Record bus departure
CREATE PROCEDURE sp_record_departure(
    IN p_arrival_id INT,
    IN p_recorded_by INT
)
BEGIN
    DECLARE v_bus_number VARCHAR(20);
    DECLARE v_route_id INT;
    DECLARE v_arrival_datetime TIMESTAMP;
    DECLARE v_dwell_minutes INT;
    DECLARE v_status VARCHAR(20);
    
    -- Get arrival details
    SELECT bus_number, route_id, arrival_datetime, status
    INTO v_bus_number, v_route_id, v_arrival_datetime, v_status
    FROM bus_arrivals
    WHERE arrival_id = p_arrival_id;
    
    -- Check if arrival exists
    IF v_bus_number IS NULL THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Arrival record not found';
    END IF;
    
    -- Check if already departed
    IF v_status = 'departed' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Bus has already departed';
    END IF;
    
    -- Calculate dwell time in minutes
    SET v_dwell_minutes = TIMESTAMPDIFF(MINUTE, v_arrival_datetime, NOW());
    
    -- Insert departure record
    INSERT INTO bus_departures (
        arrival_id, bus_number, route_id, 
        dwell_time_minutes, recorded_by
    ) VALUES (
        p_arrival_id, v_bus_number, v_route_id,
        v_dwell_minutes, p_recorded_by
    );
    
    -- Update arrival status
    UPDATE bus_arrivals
    SET status = 'departed'
    WHERE arrival_id = p_arrival_id;
    
    SELECT LAST_INSERT_ID() as departure_id, v_dwell_minutes as dwell_time;
END //

DELIMITER ;

-- ============================================================
-- Grant Permissions (adjust as needed for your setup)
-- ============================================================
-- GRANT ALL PRIVILEGES ON terminal_tracking_system.* TO 'terminal_user'@'localhost' IDENTIFIED BY 'your_secure_password';
-- FLUSH PRIVILEGES;

-- ============================================================
-- Database Setup Complete
-- ============================================================
SELECT 'Database setup completed successfully!' as Status;
SELECT 'Default admin credentials: username=admin, password=Admin@123' as Note;
SELECT 'IMPORTANT: Change the admin password immediately after first login!' as Warning;
