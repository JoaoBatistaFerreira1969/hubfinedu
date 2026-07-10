-- HuB Finedu - Database Schema
-- Execute no phpMyAdmin da Hostinger

CREATE TABLE IF NOT EXISTS users (
    id VARCHAR(64) PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    password VARCHAR(255),
    email VARCHAR(255) NOT NULL UNIQUE,
    name VARCHAR(100) DEFAULT '',
    surname VARCHAR(100) DEFAULT '',
    city VARCHAR(100) DEFAULT '',
    phone VARCHAR(50) DEFAULT '',
    cpf VARCHAR(20) DEFAULT '',
    confirmed TINYINT(1) DEFAULT 0,
    confirmation_token VARCHAR(100) DEFAULT NULL,
    trial_ends_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    provider VARCHAR(20) DEFAULT 'local',
    photo VARCHAR(500) DEFAULT '',
    xp INT DEFAULT 0,
    level INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_token (confirmation_token)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL UNIQUE,
    description TEXT,
    enabled TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS modules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(20) NOT NULL,
    description TEXT,
    category_id INT DEFAULT NULL,
    order_num INT NOT NULL DEFAULT 0,
    total_questions INT DEFAULT 0,
    passing_score DECIMAL(5,2) DEFAULT 50.00,
    max_attempts INT DEFAULT 10,
    time_per_question INT DEFAULT 180,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    category_id INT NOT NULL,
    granted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    expires_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_category (user_id, category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    module_id INT NOT NULL,
    custom_id VARCHAR(20) DEFAULT NULL,
    topic VARCHAR(200) DEFAULT '',
    question_text TEXT NOT NULL,
    option_a VARCHAR(500) NOT NULL,
    option_b VARCHAR(500) NOT NULL,
    option_c VARCHAR(500) NOT NULL,
    option_d VARCHAR(500) NOT NULL,
    correct_answer CHAR(1) NOT NULL,
    justification TEXT,
    difficulty INT DEFAULT 3,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_module (module_id),
    INDEX idx_topic (topic),
    INDEX idx_custom_id (custom_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    module_id INT NOT NULL,
    attempt_number INT NOT NULL DEFAULT 1,
    score DECIMAL(5,2) DEFAULT 0,
    total_questions INT DEFAULT 0,
    correct_answers INT DEFAULT 0,
    started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    completed_at DATETIME DEFAULT NULL,
    time_spent INT DEFAULT 0,
    status VARCHAR(20) DEFAULT 'in_progress',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    INDEX idx_user_module (user_id, module_id),
    INDEX idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS quiz_answers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    attempt_id INT NOT NULL,
    question_id INT NOT NULL,
    selected_answer CHAR(1) DEFAULT NULL,
    is_correct TINYINT(1) DEFAULT 0,
    time_spent INT DEFAULT 0,
    answered_at DATETIME DEFAULT NULL,
    FOREIGN KEY (attempt_id) REFERENCES quiz_attempts(id) ON DELETE CASCADE,
    FOREIGN KEY (question_id) REFERENCES questions(id) ON DELETE CASCADE,
    INDEX idx_attempt (attempt_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS user_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(64) NOT NULL,
    module_id INT NOT NULL,
    best_score DECIMAL(5,2) DEFAULT 0,
    attempts_used INT DEFAULT 0,
    is_unlocked TINYINT(1) DEFAULT 0,
    is_completed TINYINT(1) DEFAULT 0,
    completed_at DATETIME DEFAULT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (module_id) REFERENCES modules(id) ON DELETE CASCADE,
    UNIQUE KEY uk_user_module (user_id, module_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Inserir categorias padrão
INSERT INTO categories (name, code, description) VALUES
('CPA', 'CPA', 'Certificação de Profissional de Agente Autônomo'),
('C Pro-R', 'CPROR', 'Certificação de Profissional de Relacionamento'),
('C Pro-I', 'CPROI', 'Certificação de Profissional de Investimentos'),
('Educação Financeira', 'EDUFIN', 'Educação Financeira Geral'),
('Gestão Financeira', 'GESTFIN', 'Gestão Financeira Empresarial');

-- Inserir módulos padrão (sem categoria definida)
INSERT INTO modules (name, code, description, order_num, total_questions, passing_score, max_attempts, time_per_question) VALUES
('Módulo 1', 'M1', 'Módulo introdutório', 1, 10, 50.00, 10, 180),
('Módulo 2', 'M2', 'Segundo módulo', 2, 20, 50.00, 10, 180),
('Módulo 3', 'M3', 'Terceiro módulo', 3, 30, 50.00, 10, 180),
('Módulo 4', 'M4', 'Quarto módulo', 4, 45, 50.00, 10, 180),
('Módulo 5', 'M5', 'Quinto módulo', 5, 55, 50.00, 10, 180),
('Módulo 6', 'M6', 'Sexto módulo', 6, 60, 50.00, 10, 180),
('Módulo 7', 'M7', 'Sétimo módulo', 7, 70, 50.00, 10, 180),
('Simulado', 'SIM', 'Simulado final com questões de todos os módulos', 8, 70, 50.00, 10, 180);
