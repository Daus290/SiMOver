"""
HLS Stream Proxy for ATCS Banjarmasin
Runs a multi-threaded HTTP server to proxy ATCS fMP4 HLS streams with clean CORS headers.
Prevents single-threaded PHP (localhost:8080) from locking on video chunk downloads.
"""

import sys
import os
import re
import time
import urllib.request
import urllib.error
import ssl
from pathlib import Path
from http.server import ThreadingHTTPServer, BaseHTTPRequestHandler
import threading

BASE_DIR = Path(__file__).resolve().parent
UPSTREAM_HOST = "https://atcs.banjarmasinkota.go.id"
DEFAULT_PORT = 8088

# In-memory segment cache for hot chunks (LRU / TTL)
_cache_lock = threading.Lock()
_segment_cache = {}  # key -> (data, content_type, timestamp)
MAX_CACHE_ENTRIES = 30
CACHE_TTL_SEC = 90

# SSL context that skips verification for upstream government server
ssl_ctx = ssl.create_default_context()
ssl_ctx.check_hostname = False
ssl_ctx.verify_mode = ssl.CERT_NONE


_latest_overlay = {
    "available": False,
    "camera_code": "ATCS-TEST",
    "boxes": [],
    "updated_at": 0,
}


def set_latest_overlay(boxes, frame_w=1280, frame_h=720):
    global _latest_overlay
    _latest_overlay = {
        "available": True,
        "origin": "rizkiya_core",
        "camera_code": "ATCS-TEST",
        "frame_width": frame_w,
        "frame_height": frame_h,
        "boxes": boxes[:25] if boxes else [],
        "updated_at": time.time(),
    }


class HlsProxyHandler(BaseHTTPRequestHandler):
    protocol_version = "HTTP/1.0"

    def log_message(self, format, *args):
        # Keep console output minimal
        pass

    def do_OPTIONS(self):
        self.send_response(204)
        self.send_header("Access-Control-Allow-Origin", "*")
        self.send_header("Access-Control-Allow-Methods", "GET, HEAD, OPTIONS")
        self.send_header("Access-Control-Allow-Headers", "*")
        self.send_header("Access-Control-Max-Age", "86400")
        self.end_headers()

    def do_HEAD(self):
        self._handle_request(is_head=True)

    def do_GET(self):
        self._handle_request(is_head=False)

    def _handle_request(self, is_head=False):
        path = self.path.split("?")[0].lstrip("/")
        parts = path.split("/")

        # Endpoint overlay kotak deteksi real-time
        if path == "overlay" or (len(parts) >= 2 and parts[0] == "api" and parts[1] == "overlay"):
            import json
            age = round(time.time() - _latest_overlay.get("updated_at", 0), 2)
            data = dict(_latest_overlay)
            data["age_seconds"] = age
            if age > 4.0:
                data["available"] = False
                data["boxes"] = []
            body = json.dumps(data).encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "application/json")
            self.send_header("Access-Control-Allow-Origin", "*")
            self.send_header("Cache-Control", "no-cache, no-store")
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            if not is_head:
                self.wfile.write(body)
            return

        # Static image serving for uploads/detections/<filename>
        if len(parts) >= 3 and parts[0] == "uploads" and parts[1] == "detections":
            fname = parts[2]
            img_file = BASE_DIR.parent / "public" / "uploads" / "detections" / fname
            if img_file.is_file():
                try:
                    with open(img_file, "rb") as f:
                        img_bytes = f.read()
                    self.send_response(200)
                    self.send_header("Content-Type", "image/jpeg")
                    self.send_header("Access-Control-Allow-Origin", "*")
                    self.send_header("Cache-Control", "public, max-age=86400")
                    self.send_header("Content-Length", str(len(img_bytes)))
                    self.end_headers()
                    if not is_head:
                        self.wfile.write(img_bytes)
                    return
                except Exception:
                    pass

        # Format expected: stream/<source_code>/<filename>
        if len(parts) >= 3 and parts[0] == "stream":
            source_code = parts[1]
            filename = "/".join(parts[2:])
            self._proxy_stream(source_code, filename, is_head)
            return

        # Healthcheck endpoint
        if path == "health" or path == "":
            self.send_response(200)
            self.send_header("Content-Type", "text/plain")
            self.send_header("Access-Control-Allow-Origin", "*")
            body = b"HLS Stream Proxy OK"
            self.send_header("Content-Length", str(len(body)))
            self.end_headers()
            if not is_head:
                self.wfile.write(body)
            return

        self.send_response(404)
        self.end_headers()

    def _proxy_stream(self, source_code, filename, is_head):
        upstream_url = f"{UPSTREAM_HOST}/stream/{source_code}/{filename}"
        is_m3u8 = filename.endswith(".m3u8")

        # Check in-memory cache for media segments
        if not is_m3u8:
            cache_key = f"{source_code}/{filename}"
            with _cache_lock:
                entry = _segment_cache.get(cache_key)
                if entry:
                    data, ctype, ts = entry
                    if time.time() - ts < CACHE_TTL_SEC:
                        self.send_response(200)
                        self.send_header("Content-Type", ctype)
                        self.send_header("Content-Length", str(len(data)))
                        self.send_header("Access-Control-Allow-Origin", "*")
                        self.send_header("Cache-Control", "public, max-age=60")
                        self.end_headers()
                        if not is_head:
                            self.wfile.write(data)
                        return

        req = urllib.request.Request(upstream_url, headers={
            "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) HlsProxy/1.0",
            "Accept": "*/*"
        })

        try:
            with urllib.request.urlopen(req, context=ssl_ctx, timeout=10) as resp:
                code = resp.status
                content = resp.read()
                content_type = resp.headers.get("Content-Type", "application/octet-stream")

                if is_m3u8:
                    # Rewrite manifest URLs so segments and playlists route through this proxy
                    try:
                        text = content.decode("utf-8", errors="replace")
                        # Child playlists
                        text = re.sub(
                            r"^([a-zA-Z0-9_.-]+\.m3u8)$",
                            r"/stream/" + source_code + r"/\1",
                            text,
                            flags=re.MULTILINE
                        )
                        # fMP4 Map Init segment
                        text = re.sub(
                            r'#EXT-X-MAP:URI="([^"]+)"',
                            r'#EXT-X-MAP:URI="/stream/' + source_code + r'/\1"',
                            text
                        )
                        # Media Segments (.mp4, .ts, .m4s)
                        text = re.sub(
                            r"^([a-zA-Z0-9_.-]+\.(?:mp4|ts|m4s))$",
                            r"/stream/" + source_code + r"/\1",
                            text,
                            flags=re.MULTILINE
                        )
                        content = text.encode("utf-8")
                        content_type = "application/vnd.apple.mpegurl"
                    except Exception as e:
                        pass
                else:
                    # Store media segment in cache
                    with _cache_lock:
                        if len(_segment_cache) >= MAX_CACHE_ENTRIES:
                            # Evict oldest entry
                            oldest_k = min(_segment_cache.keys(), key=lambda k: _segment_cache[k][2])
                            _segment_cache.pop(oldest_k, None)
                        _segment_cache[f"{source_code}/{filename}"] = (content, content_type, time.time())

                self.send_response(code)
                self.send_header("Content-Type", content_type)
                self.send_header("Content-Length", str(len(content)))
                self.send_header("Access-Control-Allow-Origin", "*")
                self.send_header("Cache-Control", "no-cache" if is_m3u8 else "public, max-age=60")
                self.end_headers()
                if not is_head:
                    self.wfile.write(content)

        except urllib.error.HTTPError as e:
            self.send_response(e.code)
            self.send_header("Access-Control-Allow-Origin", "*")
            self.end_headers()
        except Exception as e:
            self.send_response(502)
            self.send_header("Access-Control-Allow-Origin", "*")
            self.send_header("Content-Type", "text/plain")
            self.end_headers()
            self.wfile.write(f"Proxy error: {e}".encode("utf-8"))


def start_proxy_thread(port=DEFAULT_PORT):
    server = ThreadingHTTPServer(("0.0.0.0", port), HlsProxyHandler)
    t = threading.Thread(target=server.serve_forever, daemon=True, name="HlsProxyServer")
    t.start()
    return server


if __name__ == "__main__":
    port = int(sys.argv[1]) if len(sys.argv) > 1 else DEFAULT_PORT
    server = ThreadingHTTPServer(("0.0.0.0", port), HlsProxyHandler)
    print(f"HLS Stream Proxy running on http://localhost:{port}/stream/...")
    try:
        server.serve_forever()
    except KeyboardInterrupt:
        pass
