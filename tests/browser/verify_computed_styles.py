import subprocess, time, json, urllib.request, socket, base64, os, struct

def ws_handshake(sock, host, port, path):
    key = base64.b64encode(os.urandom(16)).decode('utf-8')
    req = (
        f"GET {path} HTTP/1.1\r\n"
        f"Host: {host}:{port}\r\n"
        "Upgrade: websocket\r\n"
        "Connection: Upgrade\r\n"
        f"Sec-WebSocket-Key: {key}\r\n"
        "Sec-WebSocket-Version: 13\r\n\r\n"
    )
    sock.sendall(req.encode('utf-8'))
    resp = sock.recv(4096)
    if b"101 " not in resp:
        raise Exception("WS handshake failed: " + resp.decode('utf-8', errors='ignore'))

def ws_send(sock, payload_str):
    data = payload_str.encode('utf-8')
    length = len(data)
    frame = bytearray([0x81]) # text frame, FIN
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
    masked = bytearray(data[i] ^ mask[i % 4] for i in range(length))
    frame.extend(masked)
    sock.sendall(frame)

def ws_recv(sock):
    buf = sock.recv(2)
    if not buf:
        return None
    b1, b2 = buf[0], buf[1]
    length = b2 & 0x7F
    if length == 126:
        ext = sock.recv(2)
        length = struct.unpack('>H', ext)[0]
    elif length == 127:
        ext = sock.recv(8)
        length = struct.unpack('>Q', ext)[0]
    
    payload = bytearray()
    while len(payload) < length:
        chunk = sock.recv(min(4096, length - len(payload)))
        if not chunk:
            break
        payload.extend(chunk)
    return payload.decode('utf-8', errors='ignore')

def eval_js(sock, req_id, expr):
    msg = json.dumps({
        "id": req_id,
        "method": "Runtime.evaluate",
        "params": {
            "expression": expr,
            "returnByValue": True,
            "awaitPromise": True
        }
    })
    ws_send(sock, msg)
    while True:
        resp_str = ws_recv(sock)
        if not resp_str:
            return None
        resp = json.loads(resp_str)
        if resp.get("id") == req_id:
            res = resp.get("result", {}).get("result", {})
            return res.get("value")

def run_tests():
    chrome = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
    port = 9225
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
        targets_resp = urllib.request.urlopen(f'http://localhost:{port}/json')
        targets = json.loads(targets_resp.read())
        target = [t for t in targets if t.get('type') == 'page'][0]
        ws_url = target['webSocketDebuggerUrl']
        # ws://localhost:9225/devtools/page/...
        path = ws_url.split(f':{port}')[1]

        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.connect(('localhost', port))
        ws_handshake(sock, 'localhost', port, path)

        req_id = 1
        
        pages_to_test = [
            ("Beranda", "http://localhost:8080/"),
            ("Live CCTV", "http://localhost:8080/live-cctv"),
            ("Live CCTV Player", "http://localhost:8080/live-cctv?cctv=BJB-BRIMOB-F1"),
            ("Dashboard", "http://localhost:8080/dashboard"),
            ("Peta Interaktif", "http://localhost:8080/peta-cctv"),
            ("Tentang Dishub", "http://localhost:8080/tentang-dishub"),
            ("Tentang ODOL", "http://localhost:8080/tentang-over-dimension"),
            ("Admin Login (read-only)", "http://localhost:8080/admin/login"),
        ]

        results = {}

        for name, url in pages_to_test:
            req_id += 1
            ws_send(sock, json.dumps({"id": req_id, "method": "Page.navigate", "params": {"url": url}}))
            time.sleep(1.5) # allow load

            req_id += 1
            js = """(() => {
                const getRadius = (selector) => {
                    const el = document.querySelector(selector);
                    if (!el) return 'NOT_FOUND';
                    const s = window.getComputedStyle(el);
                    return {
                        borderRadius: s.borderRadius,
                        borderTopLeftRadius: s.borderTopLeftRadius,
                        borderTopRightRadius: s.borderTopRightRadius,
                        borderBottomLeftRadius: s.borderBottomLeftRadius,
                        borderBottomRightRadius: s.borderBottomRightRadius,
                    };
                };

                const data = {};
                // Elements to check
                const selectors = [
                    '.btn-dishub-primary',
                    '.btn-dishub-outline',
                    '.btn-dishub-light',
                    '.stat-card-modern',
                    '.cctv-card-simple',
                    '.cctv-thumb-wrap',
                    '.cctv-thumb-placeholder',
                    '.cctv-status-badge',
                    '.cctv-fav-toggle',
                    '.filter-bar',
                    '.filter-btn',
                    '.search-field-dishub',
                    '.player-wrap',
                    '.card-plain',
                    '.leaflet-map-wrapper',
                    '.btn-map-control',
                    '.btn-detail-rounded',
                    '.modal-content',
                    '.tentang-peran-card',
                    '.dampak-card',
                    '.dampak-img-wrap',
                    '.odol-workflow-card',
                    '.odol-vehicle-card',
                    '.odol-photo-unit',
                    'button[type="submit"]' // admin login button
                ];

                for (const sel of selectors) {
                    const res = getRadius(sel);
                    if (res !== 'NOT_FOUND') {
                        data[sel] = res;
                    }
                }
                return data;
            })()"""
            eval_res = eval_js(sock, req_id, js)
            results[name] = eval_res
            print(f"=== {name} ({url}) ===")
            for sel, r in (eval_res or {}).items():
                print(f"  {sel}: borderRadius={r.get('borderRadius')}")

        with open("scratch/computed_style_results.json", "w") as f:
            json.dump(results, f, indent=2)

    finally:
        proc.terminate()

if __name__ == '__main__':
    run_tests()
