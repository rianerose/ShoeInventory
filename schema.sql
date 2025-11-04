CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(100) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  full_name VARCHAR(100),
  role VARCHAR(20) NOT NULL DEFAULT 'cashier',
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  sku VARCHAR(50) NOT NULL UNIQUE,
  brand VARCHAR(50),
  model VARCHAR(100),
  category VARCHAR(50),
  size VARCHAR(50),
  color VARCHAR(50),
  price DECIMAL(10,2),
  cost DECIMAL(10,2),
  quantity INT DEFAULT 0,
  min_stock_level INT DEFAULT 0,
  image_url VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE sales (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  product_sku VARCHAR(50),
  product_name VARCHAR(100),
  quantity INT,
  unit_price DECIMAL(10,2),
  total_amount DECIMAL(12,2),
  payment_method VARCHAR(50),
  customer_name VARCHAR(100),
  notes TEXT,
  recorded_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id),
  FOREIGN KEY (recorded_by) REFERENCES users(id)
);

CREATE TABLE purchases (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT,
  product_sku VARCHAR(50),
  product_name VARCHAR(100),
  supplier_name VARCHAR(100),
  quantity INT,
  unit_cost DECIMAL(10,2),
  total_cost DECIMAL(12,2),
  delivery_date DATE,
  status VARCHAR(50),
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Seed admin user: password is 'admin123' (change in production)
INSERT INTO users (username, password, full_name, role) VALUES ('admin', '$2b$12$jjeud2cZehgsjEJmDXyvnu/V.j7O0DKp.KkAsLQXoCuC9oX/AfVlS', 'Administrator', 'admin');
