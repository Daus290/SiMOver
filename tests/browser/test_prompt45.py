import subprocess, time, json, urllib.request, socket, base64, os, struct

def ws_handshake(sock, host, port, path):
    key = base64.b64encode(os.urandom(16)).decode('utf-8')
    req = f"GET {path} HTTP/1.1\r\nHost: {host}:{port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n"
    sock.sendall(req.encode('utf-8'))
    resp = sock.recv(4096)
    if b"101 " not in resp:
        raise Exception("WS handshake failed: " + resp.decode('utf-8', errors='ignore'))

def ws_send(sock, payload_str):
    data = payload_str.encode('utf-8')
    length = len(data)
    frame = bytearray([0x81])
    mask = os.urandom(4)
    if length <= 125:
        frame.append(0x80 | length)
    elif length <= 65535:
        frame.append(0x80 | 126)
        frame.extend(struct.pack('>H', length))
    else:
        frame.append(0x80 | 127)
        frame.extend(struct.pack('>Q', length))
    frame.extend(mask)
    frame.extend(bytearray(data[i] ^ mask[i % 4] for i in range(length)))
    sock.sendall(frame)

def ws_recv(sock):
    buf = sock.recv(2)
    if not buf:
        return None
    b2 = buf[1]
    length = b2 & 0x7F
    if length == 126:
        length = struct.unpack('>H', sock.recv(2))[0]
    elif length == 127:
        length = struct.unpack('>Q', sock.recv(8))[0]
    payload = bytearray()
    while len(payload) < length:
        chunk = sock.recv(min(4096, length - len(payload)))
        if not chunk:
            break
        payload.extend(chunk)
    return payload.decode('utf-8', errors='ignore')

def cdp_call(sock, req_id, method, params=None):
    msg = {"id": req_id, "method": method}
    if params:
        msg["params"] = params
    ws_send(sock, json.dumps(msg))
    while True:
        resp_str = ws_recv(sock)
        if not resp_str:
            return None
        resp = json.loads(resp_str)
        if resp.get("id") == req_id:
            return resp.get("result", {})

def eval_js(sock, req_id, expr):
    res = cdp_call(sock, req_id, "Runtime.evaluate", {
        "expression": expr,
        "returnByValue": True,
        "awaitPromise": True
    })
    return res.get("result", {}).get("value")

def take_screenshot(sock, req_id, filepath):
    res = cdp_call(sock, req_id, "Page.captureScreenshot", {"format": "png"})
    data = res.get("data")
    if data:
        with open(filepath, "wb") as f:
            f.write(base64.b64decode(data))
        return True
    return False

def main():
    chrome = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
    port = 9229
    proc = subprocess.Popen([
        chrome,
        '--headless=new',
        '--disable-gpu',
        f'--remote-debugging-port={port}',
        '--window-size=1440,900',
        'about:blank'
    ], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(1.5)

    try:
        targets = json.loads(urllib.request.urlopen(f'http://localhost:{port}/json').read())
        target = [t for t in targets if t.get('type') == 'page'][0]
        path = target['webSocketDebuggerUrl'].split(f':{port}')[1]
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.connect(('localhost', port))
        ws_handshake(sock, 'localhost', port, path)

        req_id = 1

        # Enable Network and set session cookie
        req_id += 1
        cdp_call(sock, req_id, "Network.enable")

        # Find latest active admin session
        session_id = "d69c78e0eea8b7ea0ec6469f658804c2"
        req_id += 1
        cdp_call(sock, req_id, "Network.setCookie", {
            "name": "ci_session",
            "value": session_id,
            "domain": "localhost",
            "path": "/"
        })

        # 1. TEST ADMIN DASHBOARD (Desktop 1440x900)
        req_id += 1
        cdp_call(sock, req_id, "Emulation.setDeviceMetricsOverride", {
            "width": 1440,
            "height": 900,
            "deviceScaleFactor": 1,
            "mobile": False
        })

        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/admin/dashboard"})
        time.sleep(2)

        req_id += 1
        dashboard_data = eval_js(sock, req_id, """(() => {
            const getR = (sel) => {
                const el = document.querySelector(sel);
                if (!el) return 'NOT_FOUND';
                const s = window.getComputedStyle(el);
                return s.borderRadius;
            };

            const badges = Array.from(document.querySelectorAll('.table-admin .badge-deteksi')).map(b => ({
                text: b.textContent.trim(),
                className: b.className,
                hasIcons: b.querySelectorAll('i, svg').length,
                borderRadius: window.getComputedStyle(b).borderRadius,
                padding: window.getComputedStyle(b).padding
            }));

            const statSvg = document.querySelector('.admin-stat-card.accent-kontainer svg');

            return {
                title: document.title,
                url: window.location.href,
                isLogin: !!document.querySelector('.admin-login-layout'),
                portalPengunjungRadius: getR('.admin-header-bar a.btn-admin-outline'),
                statCardRadius: getR('.admin-stat-card'),
                statIconRadius: getR('.admin-stat-icon'),
                insightCardRadius: getR('.admin-insight-card'),
                chartCardRadius: getR('.admin-card'),
                tableCardRadius: getR('.table-responsive-admin'),
                latestBtnRadius: getR('.admin-card a.btn-admin-outline.btn-sm'),
                badgeCount: badges.length,
                badgeSample: badges.slice(0, 5),
                badgeIconsCount: document.querySelectorAll('.table-admin .badge-deteksi i, .table-admin .badge-deteksi svg').length,
                statCardKontainerSvgExists: !!statSvg,
                bodyScrollWidth: document.body.scrollWidth,
                windowInnerWidth: window.innerWidth
            };
        })()""")

        print("=== DASHBOARD CHECK ===")
        print(json.dumps(dashboard_data, indent=2))

        # Screenshot Dashboard Desktop
        artifact_dir = r"C:\Users\Daus\.gemini\antigravity-ide\brain\6c0346c6-d188-4ec6-83cb-749b5fd82b2a"
        req_id += 1
        take_screenshot(sock, req_id, os.path.join(artifact_dir, "admin_dashboard_desktop_p45.png"))

        # Mobile Screenshot Dashboard (390x844)
        req_id += 1
        cdp_call(sock, req_id, "Emulation.setDeviceMetricsOverride", {
            "width": 390,
            "height": 844,
            "deviceScaleFactor": 1,
            "mobile": True
        })
        time.sleep(1)
        req_id += 1
        take_screenshot(sock, req_id, os.path.join(artifact_dir, "admin_dashboard_mobile_p45.png"))

        # 2. TEST HASIL DETEKSI (Desktop 1440x900)
        req_id += 1
        cdp_call(sock, req_id, "Emulation.setDeviceMetricsOverride", {
            "width": 1440,
            "height": 900,
            "deviceScaleFactor": 1,
            "mobile": False
        })

        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/admin/hasil-deteksi"})
        time.sleep(2)

        req_id += 1
        hasil_data = eval_js(sock, req_id, """(() => {
            const getR = (sel) => {
                const el = document.querySelector(sel);
                if (!el) return 'NOT_FOUND';
                const s = window.getComputedStyle(el);
                return s.borderRadius;
            };

            const badges = Array.from(document.querySelectorAll('.table-admin .badge-deteksi')).map(b => ({
                text: b.textContent.trim(),
                className: b.className,
                hasIcons: b.querySelectorAll('i, svg').length,
                borderRadius: window.getComputedStyle(b).borderRadius,
                padding: window.getComputedStyle(b).padding
            }));

            const pageLinks = Array.from(document.querySelectorAll('.admin-page-link')).map(p => ({
                text: p.textContent.trim(),
                radius: window.getComputedStyle(p).borderRadius
            }));

            return {
                title: document.title,
                url: window.location.href,
                filterCardRadius: getR('.admin-card'),
                filterSelectRadius: getR('#filterCctv'),
                terapkanBtnRadius: getR('button[type="submit"].btn-admin-primary'),
                tableResponsiveRadius: getR('.table-responsive-admin'),
                pageLinksSample: pageLinks.slice(0, 5),
                badgeCount: badges.length,
                badgeSample: badges.slice(0, 5),
                badgeIconsCount: document.querySelectorAll('.table-admin .badge-deteksi i, .table-admin .badge-deteksi svg').length,
                hasHorizontalScroll: document.querySelector('.table-responsive-admin') ? (document.querySelector('.table-responsive-admin').scrollWidth > document.querySelector('.table-responsive-admin').clientWidth) : false,
                bodyScrollWidth: document.body.scrollWidth,
                windowInnerWidth: window.innerWidth
            };
        })()""")

        print("\n=== HASIL DETEKSI CHECK ===")
        print(json.dumps(hasil_data, indent=2))

        # Screenshot Hasil Deteksi Desktop
        req_id += 1
        take_screenshot(sock, req_id, os.path.join(artifact_dir, "admin_hasil_deteksi_desktop_p45.png"))

        # Mobile Screenshot Hasil Deteksi (390x844)
        req_id += 1
        cdp_call(sock, req_id, "Emulation.setDeviceMetricsOverride", {
            "width": 390,
            "height": 844,
            "deviceScaleFactor": 1,
            "mobile": True
        })
        time.sleep(1)
        req_id += 1
        take_screenshot(sock, req_id, os.path.join(artifact_dir, "admin_hasil_deteksi_mobile_p45.png"))

        # 3. TEST FILTER & RESET & PAGINATION INTERACTION
        # Back to desktop
        req_id += 1
        cdp_call(sock, req_id, "Emulation.setDeviceMetricsOverride", {
            "width": 1440,
            "height": 900,
            "deviceScaleFactor": 1,
            "mobile": False
        })

        # Apply filter: cctv=all, vehicle=Truk, result=Over Dimension
        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/admin/hasil-deteksi?cctv=all&vehicle=Truk&result=Over+Dimension"})
        time.sleep(1.5)

        req_id += 1
        filter_res = eval_js(sock, req_id, """(() => {
            const resetBtn = document.querySelector('a[title="Bersihkan Filter"]');
            const badges = Array.from(document.querySelectorAll('.table-admin .badge-deteksi')).map(b => b.textContent.trim());
            const activeBadge = document.querySelector('.badge-filter-active');
            return {
                url: window.location.href,
                hasResetBtn: !!resetBtn,
                resetBtnRadius: resetBtn ? window.getComputedStyle(resetBtn).borderRadius : 'N/A',
                hasActiveFilterBadge: !!activeBadge,
                allBadgesOverDim: badges.every(b => b === 'Over Dimension'),
                badgeCount: badges.length,
                badgeIconsCount: document.querySelectorAll('.table-admin .badge-deteksi i, .table-admin .badge-deteksi svg').length
            };
        })()""")

        print("\n=== FILTER INTERACTION CHECK ===")
        print(json.dumps(filter_res, indent=2))

        # Test pagination click with filter preserved
        req_id += 1
        pagination_url = eval_js(sock, req_id, """(() => {
            const p2 = document.querySelector('.admin-page-link:not(.active):not([aria-label])');
            return p2 ? p2.href : null;
        })()""")
        print(f"Pagination link URL: {pagination_url}")

        if pagination_url:
            req_id += 1
            cdp_call(sock, req_id, "Page.navigate", {"url": pagination_url})
            time.sleep(1.5)
            req_id += 1
            page2_res = eval_js(sock, req_id, """(() => {
                const activePage = document.querySelector('.admin-page-link.active');
                return {
                    url: window.location.href,
                    activePageNum: activePage ? activePage.textContent.trim() : null,
                    activePageRadius: activePage ? window.getComputedStyle(activePage).borderRadius : null,
                    badgeCount: document.querySelectorAll('.table-admin .badge-deteksi').length,
                    badgeIconsCount: document.querySelectorAll('.table-admin .badge-deteksi i, .table-admin .badge-deteksi svg').length
                };
            })()""")
            print("\n=== PAGINATION PAGE 2 CHECK ===")
            print(json.dumps(page2_res, indent=2))

        # 4. READ-ONLY CHECK OF ADMIN LOGIN & VISITOR PAGE
        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/admin/login"})
        time.sleep(1.5)
        req_id += 1
        login_check = eval_js(sock, req_id, """(() => {
            const submitBtn = document.querySelector('.btn-submit-login');
            const input = document.querySelector('.admin-login-input');
            const cctvCard = document.querySelector('.admin-login-cctv-card');
            const logoBox = document.querySelector('.admin-login-logo-box');
            return {
                url: window.location.href,
                isLoginPage: !!document.querySelector('.admin-login-layout'),
                submitBtnRadius: submitBtn ? window.getComputedStyle(submitBtn).borderRadius : 'N/A',
                inputRadius: input ? window.getComputedStyle(input).borderRadius : 'N/A',
                cctvCardRadius: cctvCard ? window.getComputedStyle(cctvCard).borderRadius : 'N/A',
                logoBoxRadius: logoBox ? window.getComputedStyle(logoBox).borderRadius : 'N/A'
            };
        })()""")
        print("\n=== LOGIN PAGE CHECK (READ-ONLY) ===")
        print(json.dumps(login_check, indent=2))

        # Visitor Beranda check
        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/"})
        time.sleep(1.5)
        req_id += 1
        visitor_check = eval_js(sock, req_id, """(() => {
            const btnPrimary = document.querySelector('.btn-dishub-primary');
            const statCard = document.querySelector('.stat-card-modern');
            const mapBox = document.querySelector('.hero-map-preview-box');
            return {
                url: window.location.href,
                btnPrimaryRadius: btnPrimary ? window.getComputedStyle(btnPrimary).borderRadius : 'N/A',
                statCardRadius: statCard ? window.getComputedStyle(statCard).borderRadius : 'N/A',
                mapBoxRadius: mapBox ? window.getComputedStyle(mapBox).borderRadius : 'N/A'
            };
        })()""")
        print("\n=== VISITOR BERANDA CHECK ===")
        print(json.dumps(visitor_check, indent=2))

    finally:
        try:
            sock.close()
        except:
            pass
        proc.terminate()

if __name__ == "__main__":
    main()
