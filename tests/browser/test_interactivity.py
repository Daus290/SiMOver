import subprocess, time, json, urllib.request, socket, base64, os, struct

def ws_handshake(sock, host, port, path):
    key = base64.b64encode(os.urandom(16)).decode('utf-8')
    req = f'GET {path} HTTP/1.1\r\nHost: {host}:{port}\r\nUpgrade: websocket\r\nConnection: Upgrade\r\nSec-WebSocket-Key: {key}\r\nSec-WebSocket-Version: 13\r\n\r\n'
    sock.sendall(req.encode('utf-8'))
    resp = sock.recv(4096)
    if b'101 ' not in resp: raise Exception('WS handshake failed')

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

def eval_js(sock, req_id, expr):
    msg = json.dumps({'id': req_id, 'method': 'Runtime.evaluate', 'params': {'expression': expr, 'returnByValue': True, 'awaitPromise': True}})
    ws_send(sock, msg)
    while True:
        resp_str = ws_recv(sock)
        if not resp_str: return None
        resp = json.loads(resp_str)
        if resp.get('id') == req_id: return resp.get('result', {}).get('result', {}).get('value')

def test():
    chrome = r'C:\Program Files\Google\Chrome\Application\chrome.exe'
    port = 9228
    proc = subprocess.Popen([chrome, '--headless=new', '--disable-gpu', f'--remote-debugging-port={port}', 'http://localhost:8080/live-cctv'], stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
    time.sleep(3)
    try:
        targets = json.loads(urllib.request.urlopen(f'http://localhost:{port}/json').read())
        target = [t for t in targets if t.get('type') == 'page'][0]
        path = target['webSocketDebuggerUrl'].split(f':{port}')[1]
        sock = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        sock.connect(('localhost', port))
        ws_handshake(sock, 'localhost', port, path)

        # 1. Test Filter Simpang
        js_filter = '''(() => {
            const btn = document.querySelector('button[data-filter="s3-brimob"]');
            if (!btn) return { error: 'Filter btn not found' };
            btn.click();
            const visibleCards = Array.from(document.querySelectorAll('.cctv-item-card')).filter(c => c.style.display !== 'none');
            return {
                filterActive: btn.classList.contains('active'),
                visibleCount: visibleCards.length
            };
        })()'''
        res_filter = eval_js(sock, 1, js_filter)
        print('1. Filter Simpang Brimob result:', res_filter)

        # 2. Test Search Input
        js_search = '''(() => {
            const input = document.querySelector('#cctvSearchInput');
            if (!input) return { error: 'Search input not found' };
            input.value = 'PTZ';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            const visibleCards = Array.from(document.querySelectorAll('.cctv-item-card')).filter(c => c.style.display !== 'none');
            return {
                searchVal: input.value,
                visibleCount: visibleCards.length
            };
        })()'''
        res_search = eval_js(sock, 2, js_search)
        print('2. Search PTZ result:', res_search)

        # 3. Test Reset Filter Button
        js_reset = '''(() => {
            const resetBtn = document.querySelector('#resetFilterBtn');
            const input = document.querySelector('#cctvSearchInput');
            input.value = 'TIDAK_ADA_KAMERA_XYZ';
            input.dispatchEvent(new Event('input', { bubbles: true }));
            const emptyStateVisible = !document.querySelector('#cctvEmptyState').classList.contains('d-none');
            resetBtn.click();
            const inputAfter = document.querySelector('#cctvSearchInput').value;
            const visibleCards = Array.from(document.querySelectorAll('.cctv-item-card')).filter(c => c.style.display !== 'none');
            return {
                emptyStateShown: emptyStateVisible,
                inputAfterReset: inputAfter,
                visibleAfterReset: visibleCards.length
            };
        })()'''
        res_reset = eval_js(sock, 3, js_reset)
        print('3. Reset Filter result:', res_reset)

        # 4. Test Favorite Toggle
        js_fav = '''(() => {
            const favBtn = document.querySelector('.cctv-fav-btn');
            if (!favBtn) return { error: 'Fav btn not found' };
            const before = favBtn.classList.contains('is-favorite');
            favBtn.click();
            const after = favBtn.classList.contains('is-favorite');
            favBtn.click(); // toggle back
            const reverted = favBtn.classList.contains('is-favorite');
            return { before, after, reverted };
        })()'''
        res_fav = eval_js(sock, 4, js_fav)
        print('4. Favorite toggle result:', res_fav)

        # 5. Test Peta Modal & zoom
        ws_send(sock, json.dumps({'id': 10, 'method': 'Page.navigate', 'params': {'url': 'http://localhost:8080/peta-cctv'}}))
        time.sleep(3)

        js_modal = '''(async () => {
            const modalBtn = document.querySelector('.js-btn-open-modal');
            if (!modalBtn) return { error: 'Modal btn not found' };
            modalBtn.click();
            await new Promise(r => setTimeout(r, 600));
            const modal = document.querySelector('#intersectionModal');
            const hasShowClass = modal ? modal.classList.contains('show') : false;
            return {
                modalFound: !!modal,
                modalOpened: hasShowClass,
                modalRadius: modal ? window.getComputedStyle(modal.querySelector('.modal-content')).borderRadius : null
            };
        })()'''
        res_modal = eval_js(sock, 11, js_modal)
        print('5. Peta Modal result:', res_modal)

    finally:
        proc.terminate()

if __name__ == '__main__':
    test()
