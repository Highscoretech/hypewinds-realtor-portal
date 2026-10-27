-- Hypewinds realtor portal. Safe to re-run: every table is IF NOT EXISTS.

CREATE TABLE IF NOT EXISTS portal_users (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	role ENUM('realtor','admin') NOT NULL DEFAULT 'realtor',
	full_name VARCHAR(190) NOT NULL,
	email VARCHAR(190) NOT NULL,
	-- Sign-in name, generated from the email at registration.
	username VARCHAR(60) NULL,
	password_hash VARCHAR(255) NOT NULL,
	-- Six-digit CID, also the referral code. Admins have none.
	ref_code CHAR(6) NULL,
	-- The member whose link they joined with. Set once at sign-up and never
	-- changed: it decides who earned commission on past sales.
	premium_star_id INT UNSIGNED NULL,
	phone VARCHAR(40) NOT NULL DEFAULT '',
	city VARCHAR(100) NOT NULL DEFAULT '',
	date_of_birth DATE NULL,
	state_of_origin VARCHAR(100) NOT NULL DEFAULT '',
	gender VARCHAR(20) NOT NULL DEFAULT '',
	country VARCHAR(100) NOT NULL DEFAULT '',
	bank_name VARCHAR(100) NOT NULL DEFAULT '',
	account_name VARCHAR(190) NOT NULL DEFAULT '',
	account_number VARCHAR(40) NOT NULL DEFAULT '',
	created_at DATETIME NOT NULL,
	last_login_at DATETIME NULL,
	PRIMARY KEY (id),
	UNIQUE KEY email (email),
	UNIQUE KEY ref_code (ref_code),
	KEY premium_star_id (premium_star_id),
	UNIQUE KEY uq_users_username (username),
	CONSTRAINT fk_users_star FOREIGN KEY (premium_star_id) REFERENCES portal_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_properties (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	name VARCHAR(190) NOT NULL,
	location VARCHAR(190) NOT NULL DEFAULT '',
	price DECIMAL(14,2) NULL,
	status VARCHAR(40) NOT NULL DEFAULT 'Available',
	-- Optional link to the listing on the main website.
	url VARCHAR(500) NOT NULL DEFAULT '',
	created_at DATETIME NOT NULL,
	PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_sales (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	seller_id INT UNSIGNED NOT NULL,
	property_type VARCHAR(20) NOT NULL DEFAULT 'land',
	description VARCHAR(255) NOT NULL DEFAULT '',
	amount DECIMAL(14,2) NOT NULL,
	sold_on DATE NOT NULL,
	recorded_by INT UNSIGNED NOT NULL,
	created_at DATETIME NOT NULL,
	PRIMARY KEY (id),
	KEY seller_id (seller_id),
	CONSTRAINT fk_sales_seller FOREIGN KEY (seller_id) REFERENCES portal_users (id),
	CONSTRAINT fk_sales_recorder FOREIGN KEY (recorded_by) REFERENCES portal_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- One commission per sale, paid to the seller's premium-star. The rate is
-- copied onto the row so changing the setting never rewrites past earnings.
CREATE TABLE IF NOT EXISTS portal_commissions (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	sale_id INT UNSIGNED NOT NULL,
	beneficiary_id INT UNSIGNED NOT NULL,
	seller_id INT UNSIGNED NOT NULL,
	rate_percent DECIMAL(5,2) NOT NULL,
	amount DECIMAL(14,2) NOT NULL,
	created_at DATETIME NOT NULL,
	PRIMARY KEY (id),
	UNIQUE KEY sale_id (sale_id),
	KEY beneficiary_id (beneficiary_id),
	CONSTRAINT fk_comm_sale FOREIGN KEY (sale_id) REFERENCES portal_sales (id),
	CONSTRAINT fk_comm_beneficiary FOREIGN KEY (beneficiary_id) REFERENCES portal_users (id),
	CONSTRAINT fk_comm_seller FOREIGN KEY (seller_id) REFERENCES portal_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_notifications (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	body TEXT NOT NULL,
	created_at DATETIME NOT NULL,
	PRIMARY KEY (id),
	KEY user_id (user_id),
	CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES portal_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS portal_settings (
	name VARCHAR(64) NOT NULL,
	value TEXT NOT NULL,
	PRIMARY KEY (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Only a hash of the token is stored, so a leaked table cannot be used to
-- reset anyone's password.
CREATE TABLE IF NOT EXISTS portal_password_resets (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	user_id INT UNSIGNED NOT NULL,
	token_hash CHAR(64) NOT NULL,
	expires_at DATETIME NOT NULL,
	used_at DATETIME NULL,
	PRIMARY KEY (id),
	UNIQUE KEY token_hash (token_hash),
	CONSTRAINT fk_reset_user FOREIGN KEY (user_id) REFERENCES portal_users (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Failed sign-ins, for throttling password guessing.
CREATE TABLE IF NOT EXISTS portal_login_failures (
	id INT UNSIGNED NOT NULL AUTO_INCREMENT,
	email VARCHAR(190) NOT NULL,
	ip VARCHAR(45) NOT NULL,
	created_at DATETIME NOT NULL,
	PRIMARY KEY (id),
	KEY email_time (email, created_at),
	KEY ip_time (ip, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO portal_settings (name, value) VALUES
	('commission_percent', '5'),
	('whatsapp_group', '');
