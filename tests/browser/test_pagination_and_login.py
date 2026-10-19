import subprocess, time, json, urllib.request, socket, base64, os, struct

def ws_handshake(sock, host, port, path):
    key = base64.b64encode(os.urandom(16)).decode('utf-8')
    req = f"GET {path} HTTP/1.1\r\nHost: {host}:{port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n"
    sock.sendall(req.encode('utf-8'))
    resp = sock.recv(4096)
    if b"101 " not in resp: raise Exception("WS handshake failed")

def ws_send(sock, payload_str):
    data = payload_str.encode('utf-8')
    length = len(data)
    frame = bytearray([0x81])
    mask = os.urandom(4)
    if length <= 125: frame.append(0x80 | length)
    elif length <= 65535: frame.append(0x80 | 126); frame.extend(struct.pack('>H', length))
    else: frame.append(0x80 | 127); frame.extend(struct.pack('>Q', length))
    frame.extend(mask)
    frame.extend(bytearray(data[i] ^ mask[i % 4] for i in range(length)))
    sock.sendall(frame)

def ws_recv(sock):
    buf = sock.recv(2)
    if not buf: return None
    b2 = buf[1]
    length = b2 & 0x7F
    if length == 126: length = struct.unpack('>H', sock.recv(2))[0]
    elif length == 127: length = struct.unpack('>Q', sock.recv(8))[0]
    payload = bytearray()
    while len(payload) < length:
        chunk = sock.recv(min(4096, length - len(payload)))
        if not chunk: break
        payload.extend(chunk)
    return payload.decode('utf-8', errors='ignore')

def cdp_call(sock, req_id, method, params=None):
    msg = {"id": req_id, "method": method}
    if params: msg["params"] = params
    ws_send(sock, json.dumps(msg))
    while True:
        resp_str = ws_recv(sock)
        if not resp_str: return None
        resp = json.loads(resp_str)
        if resp.get("id") == req_id: return resp.get("result", {})

def eval_js(sock, req_id, expr):
    res = cdp_call(sock, req_id, "Runtime.evaluate", {"expression": expr, "returnByValue": True, "awaitPromise": True})
    return res.get("result", {}).get("value")

def take_screenshot(sock, req_id, filepath):
    res = cdp_call(sock, req_id, "Page.captureScreenshot", {"format": "png"})
    data = res.get("data")
    if data:
        with open(filepath, "wb") as f: f.write(base64.b64decode(data))

def main():
    chrome = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
    port = 9230
    proc = subprocess.Popen([chrome, '--headless=new', '--disable-gpu', f'--remote-debugging-port={port}', 'about:blank'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(1.5)

    try:
        targets = json.loads(urllib.request.urlopen(f'http://localhost:{port}/json').read())
        target = [t for t in targets if t.get('type') == 'page'][0]
        path = target['webSocketDebuggerUrl'].split(f':{port}')[1]
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.connect(('localhost', port))
        ws_handshake(sock, 'localhost', port, path)

        req_id = 1
        cdp_call(sock, req_id, "Network.enable")

        # 1. TEST LOGIN PAGE WITHOUT COOKIE
        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/admin/login"})
        time.sleep(1.5)

        req_id += 1
        login_res = eval_js(sock, req_id, """(() => {
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
        print("=== LOGIN PAGE CHECK (WITHOUT SESSION) ===")
        print(json.dumps(login_res, indent=2))

        # 2. TEST PAGINATION WITH SESSION
        session_id = "d69c78e0eea8b7ea0ec6469f658804c2"
        req_id += 1
        cdp_call(sock, req_id, "Network.setCookie", {
            "name": "ci_session",
            "value": session_id,
            "domain": "localhost",
            "path": "/"
        })

        req_id += 1
        cdp_call(sock, req_id, "Page.navigate", {"url": "http://localhost:8080/admin/hasil-deteksi?page=2"})
        time.sleep(1.5)

        req_id += 1
        page2_res = eval_js(sock, req_id, """(() => {
            const activePage = document.querySelector('.admin-page-link.active');
            const badges = Array.from(document.querySelectorAll('.table-admin .badge-deteksi')).map(b => ({
                text: b.textContent.trim(),
                hasIcons: b.querySelectorAll('i, svg').length
            }));
            return {
                url: window.location.href,
                activePage: activePage ? activePage.textContent.trim() : null,
                activePageRadius: activePage ? window.getComputedStyle(activePage).borderRadius : null,
                totalBadgesOnPage2: badges.length,
                totalBadgeIcons: document.querySelectorAll('.table-admin .badge-deteksi i, .table-admin .badge-deteksi svg').length,
                firstThreeBadges: badges.slice(0, 3)
            };
        })()""")
        print("\n=== HASIL DETEKSI PAGE 2 CHECK ===")
        print(json.dumps(page2_res, indent=2))

        # Also test modal account in admin
        req_id += 1
        modal_res = eval_js(sock, req_id, """(() => {
            const modalContent = document.querySelector('#adminAccountModal .modal-content');
            const modalSubmit = document.querySelector('#btnSubmitPassword');
            const modalCancel = document.querySelector('#adminAccountModal .modal-footer button.btn-admin-outline');
            return {
                modalContentRadius: modalContent ? window.getComputedStyle(modalContent).borderRadius : 'N/A',
                modalSubmitRadius: modalSubmit ? window.getComputedStyle(modalSubmit).borderRadius : 'N/A',
                modalCancelRadius: modalCancel ? window.getComputedStyle(modalCancel).borderRadius : 'N/A'
            };
        })()""")
        print("\n=== MODAL ACCOUNT CHECK ===")
        print(json.dumps(modal_res, indent=2))

    finally:
        try: sock.close()
        except: pass
        proc.terminate()

if __name__ == "__main__":
    main()
