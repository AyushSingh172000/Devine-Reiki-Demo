<?php
$siteName = !empty($siteSettings['site_name']) ? $siteSettings['site_name'] : 'Reiki Bliss';
$logo = !empty($siteSettings['logo_path']) ? $siteSettings['logo_path'] : 'assets/images/reikilogo1.png';
$waNum = !empty($siteSettings['whatsapp']) ? preg_replace('/[^0-9]/', '', $siteSettings['whatsapp']) : '919426895692';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scheduled Sacred Maintenance | <?= htmlspecialchars($siteName) ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: radial-gradient(circle at 50% 30%, #171932 0%, #090a14 70%, #030408 100%);
            color: #f8fafc;
            font-family: 'Inter', sans-serif;
            text-align: center;
            box-sizing: border-box;
        }
        .maint-card {
            max-width: 580px;
            background: rgba(18, 20, 36, 0.85);
            border: 1px solid rgba(243, 201, 102, 0.3);
            border-radius: 20px;
            padding: 48px 36px;
            box-shadow: 0 20px 50px rgba(0,0,0,0.6);
            backdrop-filter: blur(10px);
        }
        .maint-logo {
            max-height: 80px;
            margin-bottom: 24px;
        }
        h1 {
            font-family: 'Cinzel', serif;
            font-size: 2rem;
            color: #f3c966;
            margin: 0 0 16px;
            letter-spacing: 0.04em;
        }
        p {
            color: rgba(255, 255, 255, 0.8);
            font-size: 1rem;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .btn-wa {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #25d366;
            color: #000;
            font-weight: 700;
            text-decoration: none;
            padding: 12px 26px;
            border-radius: 9999px;
            font-size: 0.95rem;
            box-shadow: 0 4px 15px rgba(37, 211, 102, 0.3);
        }
    </style>
</head>
<body>
    <div class="maint-card">
        <img src="<?= htmlspecialchars($logo) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="maint-logo">
        <h1>Sacred Energy Maintenance</h1>
        <p><?= htmlspecialchars($maintMsg ?? 'We are currently enhancing our digital sanctuary. Please visit us shortly.') ?></p>
        <?php if (!empty($waNum)): ?>
            <a href="https://wa.me/<?= htmlspecialchars($waNum) ?>" target="_blank" rel="noopener" class="btn-wa">
                Connect on WhatsApp →
            </a>
        <?php endif; ?>
    </div>
</body>
</html>
