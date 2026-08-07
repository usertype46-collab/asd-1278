-- 1. 技師資料表
CREATE TABLE IF NOT EXISTS therapists (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT,
    gender TEXT CHECK(gender IN ('M', 'F')),
    skills TEXT NOT NULL, -- 儲存 JSON 陣列如: ["深層經絡", "足底反射區", "重力道"]
    status TEXT DEFAULT 'active' CHECK(status IN ('active', 'busy', 'off')),
    rating REAL DEFAULT 5.0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 2. 客戶 CRM 資料表
CREATE TABLE IF NOT EXISTS clients (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    phone TEXT UNIQUE NOT NULL,
    prefer_gender TEXT DEFAULT 'any', -- 偏好技師性別
    prefer_pressure TEXT DEFAULT 'medium', -- 偏好力道: light, medium, heavy
    notes TEXT, -- 疾病史、避開部位、偏好
    total_visits INTEGER DEFAULT 0,
    last_visit_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- 3. 預約與服務紀錄表
CREATE TABLE IF NOT EXISTS bookings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    client_id INTEGER NOT NULL,
    therapist_id INTEGER NOT NULL,
    service_item TEXT NOT NULL, -- 服務項目 (如: 60分鐘足底按摩)
    booking_time DATETIME NOT NULL,
    duration_mins INTEGER DEFAULT 60,
    status TEXT DEFAULT 'pending' CHECK(status IN ('pending', 'completed', 'cancelled')),
    price REAL DEFAULT 0.0,
    feedback_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES clients(id),
    FOREIGN KEY (therapist_id) REFERENCES therapists(id)
);

-- 預設預備數據 (初始化技師與標籤)
INSERT INTO therapists (name, phone, gender, skills, status) VALUES 
('張技師 (#08)', '0912345678', 'M', '["深層經絡", "重力道", "肩頸舒壓", "刮痧拔罐"]', 'active'),
('李技師 (#12)', '0922334455', 'F', '["足底反射區", "輕柔放鬆", "孕婦按摩", "淋巴引流"]', 'active'),
('王技師 (#19)', '0933557799', 'M', '["運動修復", "重力道", "關節正骨", "深層經絡"]', 'busy');
