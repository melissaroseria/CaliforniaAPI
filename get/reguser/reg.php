<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Config dosyasını yükle
$config = require_once('config.php');

// Database bağlantısı
try {
    $dsn = "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['db']['username'], $config['db']['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database bağlantı hatası']);
    exit;
}

// Action belirle
$action = $_GET['action'] ?? 'register';

switch ($action) {
    case 'register':
        registerUser($pdo, $config);
        break;
    case 'quick':
        quickRegister($pdo, $config);
        break;
    case 'bulk':
        createBulkUsers($pdo, $config);
        break;
    case 'check':
        checkUser($pdo, $config);
        break;
    case 'list':
        listUsers($pdo, $config);
        break;
    case 'test':
        testConnection($pdo, $config);
        break;
    default:
        showHelp($config);
}

function registerUser($pdo, $config) {
    try {
        // Verileri al
        $username = $_GET['username'] ?? generateUsername($pdo);
        $email = $_GET['email'] ?? generateEmail($username);
        $password = $_GET['password'] ?? '123456';
        
        // 2014-2015 Eylül tarihleri
        $registerDate = generateNostalgicDate();
        
        // Kullanıcıyı oluştur
        $result = createXenForoUser($pdo, $username, $email, $password, $registerDate);
        
        if ($result['success']) {
            echo json_encode([
                'success' => true,
                'message' => '✅ Kullanıcı başarıyla oluşturuldu!',
                'forum' => $config['forum']['name'],
                'user' => [
                    'id' => $result['user_id'],
                    'username' => $username,
                    'email' => $email,
                    'password' => $password,
                    'register_date' => date('d.m.Y H:i', $registerDate),
                    'member_since' => calculateTimeAgo($registerDate),
                    'status' => 'Aktif'
                ],
                'login_info' => [
                    'url' => $config['forum']['url'],
                    'username' => $username,
                    'password' => $password
                ],
                'note' => 'Foruma giriş yapıp kullanmaya başlayabilirsiniz.',
                'powered_by' => 'DeepSeek AI 🌹'
            ]);
        } else {
            echo json_encode($result);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => 'Hata: ' . $e->getMessage()
        ]);
    }
}

function quickRegister($pdo, $config) {
    // Tek kullanıcı oluştur
    registerUser($pdo, $config);
}

function createBulkUsers($pdo, $config) {
    $count = $_GET['count'] ?? 5;
    $count = min($count, 10); // Maksimum 10 kullanıcı
    
    $createdUsers = [];
    $errors = [];
    
    for ($i = 0; $i < $count; $i++) {
        try {
            $username = generateUniqueUsername($pdo);
            $email = generateEmail($username);
            $password = generatePassword();
            $registerDate = generateNostalgicDate();
            
            $result = createXenForoUser($pdo, $username, $email, $password, $registerDate);
            
            if ($result['success']) {
                $userDetails = [
                    'id' => $result['user_id'],
                    'username' => $username,
                    'display_name' => getDisplayName($username),
                    'email' => $email,
                    'password' => $password,
                    'avatar' => getRandomAvatar(),
                    'register_date' => date('d.m.Y H:i', $registerDate),
                    'register_timestamp' => $registerDate,
                    'member_since' => calculateMemberSince($registerDate),
                    'user_group' => getRandomUserGroup(),
                    'post_count' => rand(0, 1000),
                    'likes_received' => rand(0, 500),
                    'trophy_points' => rand(0, 100),
                    'status' => 'Aktif',
                    'login_ready' => true
                ];
                $createdUsers[] = $userDetails;
            } else {
                $errors[] = [
                    'attempt' => $i + 1,
                    'error' => $result['error']
                ];
            }
            
            // Biraz bekle
            usleep(50000);
            
        } catch (Exception $e) {
            $errors[] = [
                'attempt' => $i + 1,
                'error' => $e->getMessage()
            ];
        }
    }
    
    // Forum istatistikleri (gerçek + rastgele)
    $totalUsers = getTotalUsers($pdo);
    $activeUsers = getActiveUsersCount($pdo);
    
    $response = [
        'success' => true,
        'message' => '🎉 ' . count($createdUsers) . ' nostaljik kullanıcı oluşturuldu!',
        'forum' => [
            'name' => $config['forum']['name'],
            'url' => $config['forum']['url'],
            'year' => $config['forum']['year'],
            'era' => '2014-2015 Forum Dönemi'
        ],
        'batch_info' => [
            'requested' => $count,
            'created' => count($createdUsers),
            'failed' => count($errors),
            'batch_id' => time() . rand(100, 999)
        ],
        'users' => $createdUsers,
        'summary' => [
            'toplam_uye' => $totalUsers,
            'aktif_uye' => $activeUsers,
            'yeni_kayit' => count($createdUsers),
            'basari_orani' => count($createdUsers) > 0 ? 
                round((count($createdUsers) / $count) * 100, 1) . '%' : '0%'
        ],
        'forum_stats' => [
            'toplam_mesaj' => rand(50000, 200000),
            'toplam_konu' => rand(5000, 20000),
            'cevrimici_kullanici' => rand(50, 300),
            'en_yeni_uye' => count($createdUsers) > 0 ? $createdUsers[0]['username'] : 'Yok',
            'gunluk_aktif' => rand(100, 1000)
        ],
        'login_info' => [
            'forum_url' => $config['forum']['url'],
            'note' => 'Yukarıdaki kullanıcı bilgileri ile foruma giriş yapabilirsiniz.',
            'tip' => 'Şifrelerinizi güvenli bir yerde saklayın!'
        ],
        'errors' => $errors,
        'system' => [
            'timestamp' => date('Y-m-d H:i:s'),
            'execution_time' => round(microtime(true) - $_SERVER['REQUEST_TIME_FLOAT'], 3) . ' sn',
            'memory_usage' => round(memory_get_peak_usage() / 1024 / 1024, 2) . ' MB'
        ],
        'note' => 'Bu kullanıcılar gerçek forum hesaplarıdır. Foruma giriş yapıp kullanmaya başlayabilirsiniz.',
        'powered_by' => 'DeepSeek AI 🌹',
        'nostalgia_note' => '2014-2015 yıllarının forum nostaljisi ile...'
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}
  
  
function checkUser($pdo, $config) {
    $username = $_GET['username'] ?? '';
    $email = $_GET['email'] ?? '';
    
    $checks = [];
    
    if ($username) {
        $stmt = $pdo->prepare("SELECT user_id, username, register_date FROM xf_user WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        $checks['username'] = [
            'username' => $username,
            'available' => !$user,
            'status' => $user ? '❌ Alınmış' : '✅ Kullanılabilir',
            'exists' => $user ? [
                'id' => $user['user_id'],
                'register_date' => date('d.m.Y H:i', $user['register_date'])
            ] : null
        ];
    }
    
    if ($email) {
        $stmt = $pdo->prepare("SELECT user_id, username FROM xf_user WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        $checks['email'] = [
            'email' => $email,
            'available' => !$user,
            'status' => $user ? '❌ Kayıtlı' : '✅ Kullanılabilir',
            'exists' => $user ? [
                'id' => $user['user_id'],
                'username' => $user['username']
            ] : null
        ];
    }
    
    echo json_encode([
        'success' => true,
        'forum' => $config['forum']['name'],
        'checks' => $checks,
        'stats' => [
            'total_users' => getTotalUsers($pdo),
            'last_24h' => getRecentUsersCount($pdo, 86400)
        ],
        'timestamp' => date('H:i:s')
    ]);
}

function listUsers($pdo, $config) {
    $limit = min($_GET['limit'] ?? 10, 50);
    $page = $_GET['page'] ?? 1;
    $offset = ($page - 1) * $limit;
    
    $stmt = $pdo->prepare("
        SELECT 
            u.user_id,
            u.username,
            u.email,
            u.register_date,
            u.user_state,
            u.user_group_id,
            u.message_count,
            u.trophy_points,
            p.password_date
        FROM xf_user u
        LEFT JOIN xf_user_profile p ON u.user_id = p.user_id
        ORDER BY u.user_id DESC 
        LIMIT ? OFFSET ?
    ");
    
    $stmt->execute([$limit, $offset]);
    $users = $stmt->fetchAll();
    
    // Toplam kullanıcı sayısı
    $totalStmt = $pdo->query("SELECT COUNT(*) as total FROM xf_user");
    $total = $totalStmt->fetch()['total'];
    
    // Formatla
    $formattedUsers = [];
    foreach ($users as $user) {
        $formattedUsers[] = [
            'ID' => $user['user_id'],
            'Kullanıcı Adı' => $user['username'],
            'Email' => $user['email'],
            'Kayıt Tarihi' => date('d.m.Y H:i', $user['register_date']),
            'Üyelik Süresi' => calculateTimeAgo($user['register_date']),
            'Durum' => $user['user_state'] === 'valid' ? 'Aktif' : 'Beklemede',
            'Mesaj Sayısı' => $user['message_count'],
            'Trophy Puanı' => $user['trophy_points'],
            'Grup' => getUserGroupName($user['user_group_id'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'forum' => $config['forum']['name'],
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit),
            'showing' => count($formattedUsers)
        ],
        'users' => $formattedUsers,
        'summary' => [
            'toplam_üye' => $total,
            'aktif_üye' => getActiveUsersCount($pdo),
            'bugün_kayıt' => getRecentUsersCount($pdo, 86400)
        ],
        'powered_by' => 'DeepSeek AI 🌹',
        'export_time' => date('d.m.Y H:i:s')
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

function testConnection($pdo, $config) {
    try {
        // Tablo istatistikleri
        $tables = ['xf_user', 'xf_user_authenticate', 'xf_user_profile'];
        $stats = [];
        
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM $table");
            $stats[$table] = $stmt->fetch()['count'];
        }
        
        // Son 5 kullanıcı
        $stmt = $pdo->query("
            SELECT username, register_date, email 
            FROM xf_user 
            ORDER BY user_id DESC 
            LIMIT 5
        ");
        $recentUsers = $stmt->fetchAll();
        
        echo json_encode([
            'success' => true,
            'message' => '✅ API Aktif ve Çalışıyor!',
            'forum' => [
                'name' => $config['forum']['name'],
                'url' => $config['forum']['url'],
                'year' => $config['forum']['year']
            ],
            'database' => [
                'host' => $config['db']['host'],
                'name' => $config['db']['dbname'],
                'status' => 'Bağlantı başarılı ✓'
            ],
            'statistics' => [
                'toplam_kullanıcı' => $stats['xf_user'],
                'şifre_kayıtları' => $stats['xf_user_authenticate'],
                'profil_kayıtları' => $stats['xf_user_profile']
            ],
            'recent_users' => array_map(function($user) {
                return [
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'register_date' => date('d.m.Y', $user['register_date'])
                ];
            }, $recentUsers),
            'api_endpoints' => [
                'GET ?action=quick' => 'Hızlı kayıt (1 kullanıcı)',
                'GET ?action=bulk&count=5' => 'Toplu kayıt (5 kullanıcı)',
                'GET ?action=check&username=XXX' => 'Kullanıcı adı kontrolü',
                'GET ?action=list&limit=10&page=1' => 'Kullanıcı listesi',
                'GET ?action=register&username=X&email=X&password=X' => 'Manuel kayıt'
            ],
            'system' => [
                'server_time' => date('H:i:s'),
                'server_date' => date('d.m.Y'),
                'php_version' => PHP_VERSION,
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
            ],
            'note' => 'Forum California - 2015 Nostaljisi',
            'powered_by' => 'DeepSeek AI 🌹'
        ], JSON_PRETTY_PRINT);
        
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
}

function showHelp($config) {
    echo json_encode([
        'success' => true,
        'title' => 'Forum California - Kullanıcı Kayıt API',
        'version' => '3.0',
        'description' => 'XenForo tabanlı forum için otomatik kullanıcı kayıt sistemi',
        'forum' => [
            'name' => $config['forum']['name'],
            'url' => $config['forum']['url'],
            'nostalgia_year' => $config['forum']['year']
        ],
        'endpoints' => [
            [
                'method' => 'GET',
                'endpoint' => '?action=quick',
                'description' => '🎲 Tek kullanıcı oluştur (otomatik bilgiler)'
            ],
            [
                'method' => 'GET', 
                'endpoint' => '?action=bulk&count=5',
                'description' => '👥 Toplu kullanıcı oluştur (1-10 arası)'
            ],
            [
                'method' => 'GET',
                'endpoint' => '?action=register&username=XXX&email=XXX&password=XXX',
                'description' => '📝 Manuel kullanıcı kaydı'
            ],
            [
                'method' => 'GET',
                'endpoint' => '?action=check&username=XXX&email=XXX',
                'description' => '🔍 Kullanıcı adı/email uygunluğu kontrolü'
            ],
            [
                'method' => 'GET',
                'endpoint' => '?action=list&limit=10&page=1',
                'description' => '📋 Kullanıcı listesi (sayfalı)'
            ],
            [
                'method' => 'GET',
                'endpoint' => '?action=test',
                'description' => '⚙️ Sistem ve bağlantı testi'
            ]
        ],
        'examples' => [
            'https://viosrio.serv00.net/get/reg.php?action=bulk&count=5',
            'https://viosrio.serv00.net/get/reg.php?action=check&username=yenikullanici',
            'https://viosrio.serv00.net/get/reg.php?action=list&limit=5'
        ],
        'features' => [
            '✅ Gerçek XenForo kullanıcı kaydı',
            '✅ 2014-2015 nostaljik tarihler',
            '✅ Otomatik kullanıcı adı önerileri',
            '✅ Şifre hash güvenliği',
            '✅ Detaylı kullanıcı listesi',
            '✅ JSON formatında yanıtlar'
        ],
        'author' => 'DeepSeek AI 🌹',
        'note' => 'Oluşturulan kullanıcılar foruma gerçek giriş yapabilir!'
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
}

// ==================== YARDIMCI FONKSİYONLAR ====================

function createXenForoUser($pdo, $username, $email, $password, $registerDate) {
    try {
        // Kullanıcı adı kontrolü
        $stmt = $pdo->prepare("SELECT user_id FROM xf_user WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Kullanıcı adı alınmış'];
        }
        
        // Email kontrolü
        $stmt = $pdo->prepare("SELECT user_id FROM xf_user WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Email adresi kayıtlı'];
        }
        
        // Yeni user_id
        $stmt = $pdo->query("SELECT MAX(user_id) as max_id FROM xf_user");
        $result = $stmt->fetch();
        $userId = ($result['max_id'] ?: 1) + 1;
        
        // Secret key
        $secretKey = bin2hex(random_bytes(16));
        
        // 1. xf_user
        $stmt = $pdo->prepare("
            INSERT INTO xf_user (
                user_id, username, email, user_group_id, user_state, register_date,
                language_id, style_id, timezone, visible, activity_visible,
                secondary_group_ids, display_style_group_id, permission_combination_id,
                secret_key, privacy_policy_accepted, terms_accepted
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $userId, $username, $email, 2, 'valid', $registerDate,
            1, 0, 'Europe/Istanbul', 1, 1, '', 2, 3,
            hex2bin($secretKey), $registerDate, $registerDate
        ]);
        
        // 2. xf_user_authenticate
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $authData = serialize(['hash' => $hashedPassword]);
        
        $authStmt = $pdo->prepare("
            INSERT INTO xf_user_authenticate (user_id, scheme_class, data) 
            VALUES (?, ?, ?)
        ");
        $authStmt->execute([$userId, 'XF:Core12', $authData]);
        
        // 3. xf_user_profile
        $profileStmt = $pdo->prepare("
            INSERT INTO xf_user_profile (user_id, password_date) 
            VALUES (?, ?)
        ");
        $profileStmt->execute([$userId, $registerDate]);
        
        // 4. xf_user_option
        $optionStmt = $pdo->prepare("
            INSERT INTO xf_user_option (user_id, creation_watch_state, interaction_watch_state) 
            VALUES (?, ?, ?)
        ");
        $optionStmt->execute([$userId, 'watch_email', 'watch_email']);
        
        // 5. xf_user_privacy
        $privacyStmt = $pdo->prepare("INSERT INTO xf_user_privacy (user_id) VALUES (?)");
        $privacyStmt->execute([$userId]);
        
        // 6. xf_user_group_relation
        $groupStmt = $pdo->prepare("
            INSERT INTO xf_user_group_relation (user_id, user_group_id, is_primary) 
            VALUES (?, ?, ?)
        ");
        $groupStmt->execute([$userId, 2, 1]);
        
        return ['success' => true, 'user_id' => $userId];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function generateUsername($pdo = null) {
    $firstParts = ['Kara', 'Beyaz', 'Kırmızı', 'Mavi', 'Yeşil', 'Sarı', 'Mor', 'Altın', 'Gümüş', 'Bronz'];
    $secondParts = ['Aslan', 'Kaplan', 'Kartal', 'Şahin', 'Kurt', 'Çakal', 'Panter', 'Leopar', 'Jaguar', 'Puma'];
    $thirdParts = ['TR', 'X', 'Pro', 'Max', 'HD', 'Lite', 'Plus', 'Gold', 'Silver', 'Turbo'];
    
    return $firstParts[array_rand($firstParts)] . 
           $secondParts[array_rand($secondParts)] . 
           rand(10, 99) . 
           $thirdParts[array_rand($thirdParts)];
}

function generateUniqueUsername($pdo) {
    $maxAttempts = 20;
    
    for ($i = 0; $i < $maxAttempts; $i++) {
        $username = generateUsername();
        $stmt = $pdo->prepare("SELECT user_id FROM xf_user WHERE username = ?");
        $stmt->execute([$username]);
        
        if (!$stmt->fetch()) {
            return $username;
        }
    }
    
    return 'User' . time() . rand(100, 999);
}

function generateEmail($username) {
    $domains = [
        '@gmail.com', '@hotmail.com', '@yahoo.com', '@outlook.com',
        '@yandex.com', '@mail.com', '@aol.com', '@icloud.com'
    ];
    
    $cleanUsername = strtolower(preg_replace('/[^a-z0-9]/', '', $username));
    
    // Nostaljik email formatları
    $formats = [
        $cleanUsername . rand(1995, 2015) . $domains[array_rand($domains)],
        $cleanUsername . '_' . rand(1, 99) . $domains[array_rand($domains)],
        substr($cleanUsername, 0, 6) . rand(100, 999) . $domains[array_rand($domains)]
    ];
    
    return $formats[array_rand($formats)];
}

function generatePassword() {
    // %25 ihtimalle basit şifre (nostaljik), %75 ihtimalle güvenli şifre
    if (rand(0, 3) === 0) {
        // Basit şifreler (nostaljik için)
        $simplePasswords = [
            '123456',
            'password',
            'qwerty',
            'abc123',
            'monkey',
            'letmein',
            'dragon',
            'baseball',
            'football',
            'mustang'
        ];
        
        $username = generateUsername();
        $customPasswords = [
            $username . '123',
            'forum' . rand(2005, 2015),
            'sifre' . rand(100, 999),
            'parola' . rand(1, 99),
            'pass' . rand(1000, 9999)
        ];
        
        return rand(0, 1) ? $simplePasswords[array_rand($simplePasswords)] 
                          : $customPasswords[array_rand($customPasswords)];
    } else {
        // Güvenli şifre
        $charSets = [
            'abcdefghijklmnopqrstuvwxyz',
            'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 
            '0123456789',
            '!@#$%^&*'
        ];
        
        $password = '';
        // Her karakter setinden en az 1 karakter
        foreach ($charSets as $set) {
            $password .= $set[rand(0, strlen($set) - 1)];
        }
        
        // Kalan 6 karakter rastgele
        $allChars = implode('', $charSets);
        for ($i = 0; $i < 6; $i++) {
            $password .= $allChars[rand(0, strlen($allChars) - 1)];
        }
        
        // Karıştır
        return str_shuffle($password);
    }
}

function generateNostalgicDate() {
    // 2014 veya 2015 yılında, Eylül ayında rastgele bir tarih
    $year = rand(0, 1) ? 2014 : 2015;
    $month = 9; // Eylül
    $day = rand(1, 30);
    $hour = rand(8, 22);
    $minute = rand(0, 59);
    $second = rand(0, 59);
    
    return mktime($hour, $minute, $second, $month, $day, $year);
}

function calculateTimeAgo($timestamp) {
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'az önce';
    if ($diff < 3600) return floor($diff / 60) . ' dakika önce';
    if ($diff < 86400) return floor($diff / 3600) . ' saat önce';
    if ($diff < 604800) return floor($diff / 86400) . ' gün önce';
    if ($diff < 2592000) return floor($diff / 604800) . ' hafta önce';
    if ($diff < 31536000) return floor($diff / 2592000) . ' ay önce';
    
    $years = floor($diff / 31536000);
    return $years . ' yıl önce';
}

function getTotalUsers($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM xf_user");
    return $stmt->fetch()['total'];
}

function getRecentUsersCount($pdo, $seconds) {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM xf_user WHERE register_date > ?");
    $stmt->execute([time() - $seconds]);
    return $stmt->fetch()['count'];
}

function getActiveUsersCount($pdo) {
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM xf_user WHERE user_state = 'valid'");
    return $stmt->fetch()['count'];
}

function getUserGroupName($groupId) {
    $groups = [
        1 => 'Misafir',
        2 => 'Üye',
        3 => 'Yönetici',
        4 => 'Moderatör'
    ];
    
    return $groups[$groupId] ?? 'Bilinmeyen';
}
  
function getDisplayName($username) {
    // Kullanıcı adını daha okunaklı yap
    $patterns = [
        '/([a-z])([A-Z])/' => '$1 $2', // CamelCase boşluk ekle
        '/(\d+)([A-Za-z])/' => '$1 $2', // Sayıdan sonra boşluk
        '/TR$/' => ' TÜRKİYE',
        '/Pro$/' => ' PRO',
        '/Gold$/' => ' GOLD'
    ];
    
    $displayName = $username;
    foreach ($patterns as $pattern => $replacement) {
        $displayName = preg_replace($pattern, $replacement, $displayName);
    }
    
    return $displayName;
}

function getRandomAvatar() {
    $avatars = [
        'https://i.imgur.com/2zZ5Z5Z.png', // Retro avatar 1
        'https://i.imgur.com/5X7Z8Z9.jpg', // Retro avatar 2
        'https://i.imgur.com/9Y0Z1Z2.gif', // Retro GIF avatar
        'https://i.imgur.com/3Z4Z5Z6.jpg', // Anime avatar
        'https://i.imgur.com/7Z8Z9Z0.png', // Game character
        null, // No avatar (default)
        null  // No avatar (default)
    ];
    
    return $avatars[array_rand($avatars)];
}

function getRandomUserGroup() {
    $groups = [
        ['name' => 'Yeni Üye', 'color' => '#808080', 'badge' => '👶'],
        ['name' => 'Aktif Üye', 'color' => '#32CD32', 'badge' => '🔥'],
        ['name' => 'Kıdemli Üye', 'color' => '#1E90FF', 'badge' => '⭐'],
        ['name' => 'VIP Üye', 'color' => '#FFD700', 'badge' => '👑'],
        ['name' => 'Efsane Üye', 'color' => '#9400D3', 'badge' => '🏆']
    ];
    
    return $groups[array_rand($groups)];
}

function calculateMemberSince($timestamp) {
    $years = date('Y') - date('Y', $timestamp);
    
    if ($years <= 0) return 'Yeni Üye';
    if ($years == 1) return '1 Yıllık Üye';
    if ($years < 5) return "{$years} Yıllık Üye";
    
    return "{$years} Yıllık Efsane Üye";
}
?>