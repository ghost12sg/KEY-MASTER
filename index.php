<?php
$storage_file = 'keys.json';

function load_data($file) {
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?: [];
}

function save_data($file, $data) {
    file_put_contents($file, json_encode(array_values($data), JSON_PRETTY_PRINT));
}

$keys = load_data($storage_file);
$status_msg = "";

if (isset($_POST['generate_key'])) {
    $expiry = $_POST['expiry_date'];
    $is_custom = isset($_POST['use_custom']);
    $custom_val = trim($_POST['custom_key_name']);
    
    $final_key = ($is_custom && !empty($custom_val)) ? 
                 strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $custom_val)) : 
                 "SG-" . strtoupper(bin2hex(random_bytes(4)));

    $exists = false;
    foreach($keys as $k) { if($k['key'] == $final_key) $exists = true; }

    if (!$exists) {
        $keys[] = ['key' => $final_key, 'expiry' => $expiry];
        save_data($storage_file, $keys);
        $status_msg = "success|License Generated Successfully!";
    } else {
        $status_msg = "danger|Error: Key already exists!";
    }
}

if (isset($_GET['delete'])) {
    $target = $_GET['delete'];
    $keys = array_filter($keys, function($item) use ($target) { return $item['key'] !== $target; });
    save_data($storage_file, $keys);
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Elite Key Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700&family=Fira+Code:wght@500&display=swap');

        :root {
            --bg: #06080f;
            --card: #0f172a;
            --accent: #6366f1;
            --success: #10b981;
            --danger: #ef4444;
        }

        body {
            background-color: var(--bg);
            background-image: radial-gradient(circle at 50% 0%, #1e1b4b 0%, #06080f 70%);
            color: #f1f5f9;
            font-family: 'Plus Jakarta Sans', sans-serif;
            min-height: 100vh;
        }

        .glass-card {
            background: rgba(15, 23, 42, 0.6);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
        }

        .premium-table { border-collapse: separate; border-spacing: 0 12px; width: 100%; }
        
        .premium-table thead th {
            color: #64748b;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 10px 20px;
            border: none;
        }

        .premium-table tr.data-row { 
            background: rgba(30, 41, 59, 0.4); 
            border-radius: 20px;
            transition: 0.3s ease;
        }

        .premium-table td { padding: 18px 20px; border: none; vertical-align: middle; }
        .premium-table td:first-child { border-radius: 20px 0 0 20px; }
        .premium-table td:last-child { border-radius: 0 20px 20px 0; }

        .key-badge {
            font-family: 'Fira Code', monospace;
            background: #000;
            padding: 10px 16px;
            border-radius: 12px;
            color: #a5b4fc;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: 1px solid rgba(99, 102, 241, 0.2);
            font-size: 0.85rem;
        }

        .copy-btn { color: #475569; cursor: pointer; transition: 0.2s; padding: 5px; }
        .copy-btn:hover { color: #fff; }

        /* Status UI */
        .status-wrapper {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 14px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 100px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }

        .status-dot { width: 8px; height: 8px; border-radius: 50%; position: relative; }
        .status-active .status-dot { background: var(--success); box-shadow: 0 0 10px var(--success); }
        .status-active .status-dot::after {
            content: ''; position: absolute; width: 100%; height: 100%;
            background: var(--success); border-radius: 50%; animation: pulse 2s infinite;
        }
        .status-expired .status-dot { background: var(--danger); box-shadow: 0 0 10px var(--danger); }

        .status-text { font-size: 0.65rem; font-weight: 800; text-transform: uppercase; }
        .status-active .status-text { color: var(--success); }
        .status-expired .status-text { color: var(--danger); }

        @keyframes pulse {
            0% { transform: scale(1); opacity: 0.8; }
            70% { transform: scale(2.5); opacity: 0; }
            100% { transform: scale(1); opacity: 0; }
        }

        .btn-create {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            border: none; color: #fff; padding: 14px; border-radius: 16px;
            font-weight: 700; width: 100%; transition: 0.3s;
        }

        .form-control {
            background: rgba(0,0,0,0.3);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff; border-radius: 14px; padding: 14px;
        }

        #custom_wrap { display: none; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="text-center mb-5">
        <h2 class="fw-bold"><i class="fas fa-shield-halved text-primary me-2"></i>KEY<span class="text-primary">VAULT</span></h2>
        <p class="text-secondary small">Database-free Premium License Manager</p>
    </div>

    <div class="row g-4 justify-content-center">
        <!-- Generator -->
        <div class="col-lg-5">
            <div class="glass-card p-4">
                <h5 class="mb-4 fw-bold">Generate New Key</h5>
                <form method="POST">
                    <div class="mb-3">
                        <label class="small text-secondary mb-2">Expiry Date</label>
                        <input type="date" name="expiry_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                    </div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" id="customToggle" name="use_custom">
                        <label class="form-check-label small" for="customToggle">Enable Custom Key Name</label>
                    </div>
                    <div class="mb-4" id="custom_wrap">
                        <input type="text" name="custom_key_name" class="form-control" placeholder="e.g. MY-KEY-123">
                    </div>
                    <button type="submit" name="generate_key" class="btn-create">GENERATE LICENSE</button>
                </form>
            </div>
        </div>

        <!-- List -->
        <div class="col-lg-10">
            <div class="glass-card p-4 mt-2">
                <div class="d-flex justify-content-between align-items-center mb-2 px-2">
                    <h5 class="m-0 fw-bold text-white">Active Licenses</h5>
                    <span class="badge bg-dark border border-secondary rounded-pill"><?= count($keys) ?> Total</span>
                </div>

                <div class="table-responsive">
                    <table class="table premium-table">
                        <thead>
                            <tr>
                                <th>License Key</th>
                                <th>Status</th>
                                <th class="text-end">Manage</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach(array_reverse($keys) as $k): 
                                $expired = strtotime($k['expiry']) < strtotime(date('Y-m-d'));
                            ?>
                            <tr class="data-row">
                                <td>
                                    <div class="key-badge">
                                        <span><?= $k['key'] ?></span>
                                        <!-- Changed: Passing 'this' and the key string -->
                                        <i class="far fa-copy copy-btn" onclick="copyKeyToClipboard(this, '<?= $k['key'] ?>')"></i>
                                    </div>
                                </td>
                                <td>
                                    <div class="status-wrapper <?= $expired ? 'status-expired' : 'status-active' ?>">
                                        <div class="status-dot"></div>
                                        <span class="status-text"><?= $expired ? 'Expired' : 'Active' ?></span>
                                    </div>
                                </td>
                                <td class="text-end">
                                    <a href="?delete=<?= $k['key'] ?>" class="btn btn-sm text-danger opacity-75 me-2" onclick="return confirm('Delete?')">
                                        <i class="fas fa-trash-can fs-5"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Custom Toggle
    document.getElementById('customToggle').addEventListener('change', function() {
        document.getElementById('custom_wrap').style.display = this.checked ? 'block' : 'none';
    });

    // FIXED COPY FUNCTION
    function copyKeyToClipboard(element, text) {
        // Modern Clipboard API (Requires HTTPS)
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(() => {
                showCopySuccess(element);
            });
        } else {
            // Fallback for Non-HTTPS (Localhost or HTTP)
            let textArea = document.createElement("textarea");
            textArea.value = text;
            textArea.style.position = "fixed"; // Avoid scrolling to bottom
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                document.execCommand('copy');
                showCopySuccess(element);
            } catch (err) {
                console.error('Unable to copy', err);
            }
            document.body.removeChild(textArea);
        }
    }

    // Success UI Feedback
    function showCopySuccess(el) {
        el.classList.replace('far', 'fas');
        el.classList.add('text-success');
        
        // Simple Alert for mobile/desktop feedback
        // alert("Key Copied: " + el.previousElementSibling.innerText); 
        
        setTimeout(() => {
            el.classList.replace('fas', 'far');
            el.classList.remove('text-success');
        }, 2000);
    }
</script>

</body>
</html>