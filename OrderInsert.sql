CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(100),
    full_name VARCHAR(150),
    email VARCHAR(100),
    phone_number VARCHAR(20),
    payment_method ENUM('card', 'bank', 'cash') NOT NULL,
    card_number VARCHAR(30),
    cardholder_name VARCHAR(100),
    bank_name VARCHAR(100),
    bank_account VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE order_items (
    order_item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    car_id INT NOT NULL,
    single_price DECIMAL(10,2),
    quantity INT,
    start_date DATE,
    end_date DATE,
    rental_days INT,
    final_price DECIMAL(10,2),
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

ALTER TABLE orders
    ADD total_price DECIMAL(10,2) NOT NULL DEFAULT 0;