<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>TAD API v1 Tester</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8fafc;
            color: #111827;
            margin: 0;
            padding: 24px;
        }

        .container {
            max-width: 1180px;
            margin: 0 auto;
        }

        .card {
            background: #ffffff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 8px 20px rgba(15, 23, 42, 0.05);
        }

        h1, h2, h3 {
            margin-top: 0;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
        }

        input, select, textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 10px 12px;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            margin-bottom: 12px;
            font-size: 14px;
        }

        button {
            border: 0;
            border-radius: 8px;
            padding: 10px 14px;
            cursor: pointer;
            background: #2563eb;
            color: white;
            font-weight: 600;
            margin: 4px 4px 4px 0;
        }

        button.secondary {
            background: #4b5563;
        }

        button.danger {
            background: #dc2626;
        }

        button.success {
            background: #16a34a;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 20px;
        }

        .endpoint-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 8px;
        }

        pre {
            background: #0f172a;
            color: #e5e7eb;
            padding: 16px;
            border-radius: 12px;
            overflow-x: auto;
            min-height: 240px;
            white-space: pre-wrap;
        }

        .token-box {
            font-size: 12px;
            word-break: break-all;
            background: #f3f4f6;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #e5e7eb;
        }

        .status {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .status.ok {
            color: #16a34a;
        }

        .status.error {
            color: #dc2626;
        }

        @media (max-width: 900px) {
            .grid,
            .endpoint-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="card">
        <h1>TAD API v1 Tester</h1>
        <p>
            Halaman ini untuk testing lokal endpoint API TAD.
            Jangan jadikan ini halaman publik production.
        </p>
    </div>

    <div class="grid">
        <div class="card">
            <h2>1. Login API</h2>

            <label for="username">Username</label>
            <select id="username">
                <option value="superadmin">superadmin</option>
                <option value="admin">admin</option>
                <option value="guru">guru</option>
                <option value="orangtua">orangtua</option>
                <option value="murid">murid</option>
            </select>

            <label for="password">Password</label>
            <input id="password" type="password" value="password123">

            <label for="device_name">Device Name</label>
            <input id="device_name" type="text" value="Browser API Tester">

            <button onclick="login()">Login</button>
            <button class="danger" onclick="logout()">Logout</button>
            <button class="secondary" onclick="clearToken()">Clear Token</button>

            <h3>Token Aktif</h3>
            <div id="tokenBox" class="token-box">Belum login.</div>
        </div>

        <div class="card">
            <h2>2. Custom Request</h2>

            <label for="method">Method</label>
            <select id="method">
                <option value="GET">GET</option>
                <option value="POST">POST</option>
            </select>

            <label for="endpoint">Endpoint</label>
            <input id="endpoint" type="text" value="/api/v1/auth/me">

            <label for="payload">JSON Body</label>
            <textarea id="payload" rows="6" placeholder='{"key":"value"}'></textarea>

            <button class="success" onclick="sendCustomRequest()">Send Request</button>
        </div>
    </div>

    <div class="card">
        <h2>3. Quick Test Endpoint</h2>

        <div class="endpoint-grid">
            <button onclick="get('/api/v1/auth/me')">GET /auth/me</button>

            <button onclick="get('/api/v1/dashboard/admin')">GET /dashboard/admin</button>
            <button onclick="get('/api/v1/dashboard/teacher')">GET /dashboard/teacher</button>
            <button onclick="get('/api/v1/dashboard/parent')">GET /dashboard/parent</button>
            <button onclick="get('/api/v1/dashboard/student')">GET /dashboard/student</button>

            <button onclick="get('/api/v1/students')">GET /students</button>
            <button onclick="get('/api/v1/hafalan-records')">GET /hafalan-records</button>
            <button onclick="get('/api/v1/murajaah-records')">GET /murajaah-records</button>
            <button onclick="get('/api/v1/hafalan-targets')">GET /hafalan-targets</button>

            <button onclick="get('/api/v1/surahs')">GET /surahs</button>
            <button onclick="get('/api/v1/surahs?search=Baqarah')">GET /surahs?search=Baqarah</button>
            <button onclick="get('/api/v1/surahs/1/ayahs')">GET /surahs/1/ayahs</button>
        </div>
    </div>

    <div class="card">
        <h2>4. Response</h2>
        <div id="status" class="status">Belum ada request.</div>
        <pre id="response">{}</pre>
    </div>
</div>

<script>
    const baseUrl = window.location.origin;
    let token = localStorage.getItem('tad_api_token') || '';

    updateTokenBox();

    function updateTokenBox() {
        const tokenBox = document.getElementById('tokenBox');

        if (!token) {
            tokenBox.textContent = 'Belum login.';
            return;
        }

        tokenBox.textContent = token;
    }

    function setStatus(message, isOk = true) {
        const status = document.getElementById('status');
        status.textContent = message;
        status.className = isOk ? 'status ok' : 'status error';
    }

    function setResponse(data) {
        document.getElementById('response').textContent = JSON.stringify(data, null, 2);
    }

    async function login() {
        const body = {
            username: document.getElementById('username').value,
            password: document.getElementById('password').value,
            device_name: document.getElementById('device_name').value || 'Browser API Tester'
        };

        try {
            const response = await fetch(baseUrl + '/api/v1/auth/login', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(body)
            });

            const data = await response.json();

            if (!response.ok) {
                setStatus(`ERROR ${response.status}`, false);
                setResponse(data);
                return;
            }

            token = data?.data?.access_token || '';

            if (token) {
                localStorage.setItem('tad_api_token', token);
            }

            updateTokenBox();
            setStatus(`OK ${response.status}`);
            setResponse(data);
        } catch (error) {
            setStatus('REQUEST FAILED', false);
            setResponse({
                message: error.message
            });
        }
    }

    async function logout() {
        if (!token) {
            setStatus('Tidak ada token untuk logout.', false);
            setResponse({
                message: 'Login dulu sebelum logout.'
            });
            return;
        }

        try {
            const response = await fetch(baseUrl + '/api/v1/auth/logout', {
                method: 'POST',
                headers: authHeaders()
            });

            const data = await response.json();

            if (!response.ok) {
                setStatus(`ERROR ${response.status}`, false);
                setResponse(data);
                return;
            }

            clearToken(false);

            setStatus(`OK ${response.status}`);
            setResponse(data);
        } catch (error) {
            setStatus('REQUEST FAILED', false);
            setResponse({
                message: error.message
            });
        }
    }

    function clearToken(showMessage = true) {
        token = '';
        localStorage.removeItem('tad_api_token');
        updateTokenBox();

        if (showMessage) {
            setStatus('Token dibersihkan.');
            setResponse({
                message: 'Token lokal berhasil dihapus.'
            });
        }
    }

    function authHeaders() {
        return {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'Authorization': `Bearer ${token}`
        };
    }

    async function get(endpoint) {
        document.getElementById('method').value = 'GET';
        document.getElementById('endpoint').value = endpoint;
        document.getElementById('payload').value = '';

        await sendRequest('GET', endpoint);
    }

    async function sendCustomRequest() {
        const method = document.getElementById('method').value;
        const endpoint = document.getElementById('endpoint').value;

        await sendRequest(method, endpoint);
    }

    async function sendRequest(method, endpoint) {
        if (!endpoint.startsWith('/')) {
            endpoint = '/' + endpoint;
        }

        const options = {
            method: method,
            headers: authHeaders()
        };

        if (method !== 'GET') {
            const rawPayload = document.getElementById('payload').value.trim();

            if (rawPayload !== '') {
                try {
                    options.body = JSON.stringify(JSON.parse(rawPayload));
                } catch (error) {
                    setStatus('JSON BODY INVALID', false);
                    setResponse({
                        message: 'JSON body tidak valid.',
                        error: error.message
                    });
                    return;
                }
            }
        }

        try {
            const response = await fetch(baseUrl + endpoint, options);

            let data = null;

            try {
                data = await response.json();
            } catch (error) {
                data = {
                    message: 'Response bukan JSON.',
                    raw_status: response.status
                };
            }

            if (!response.ok) {
                setStatus(`ERROR ${response.status}`, false);
                setResponse(data);
                return;
            }

            setStatus(`OK ${response.status}`);
            setResponse(data);
        } catch (error) {
            setStatus('REQUEST FAILED', false);
            setResponse({
                message: error.message
            });
        }
    }
</script>
</body>
</html>