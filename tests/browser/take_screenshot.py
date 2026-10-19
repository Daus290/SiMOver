import subprocess
import os
import sys

def take_screenshot(url, out_path, width=1440, height=900):
    chrome_path = r"C:\Program Files\Google\Chrome\Application\chrome.exe"
    abs_out = os.path.abspath(out_path)
    cmd = [
        chrome_path,
        "--headless=new",
        "--disable-gpu",
        f"--window-size={width},{height}",
        f"--screenshot={abs_out}",
        url
    ]
    print(f"Capturing {url} to {abs_out} ({width}x{height})...")
    res = subprocess.run(cmd, capture_output=True, text=True)
    if os.path.exists(abs_out):
        print(f"Success! File size: {os.path.getsize(abs_out)} bytes")
        return True
    else:
        print("Failed! Stdout:", res.stdout, "Stderr:", res.stderr)
        return False

if __name__ == '__main__':
    url = sys.argv[1] if len(sys.argv) > 1 else "http://localhost:8080/admin/login"
    out = sys.argv[2] if len(sys.argv) > 2 else "scratch/login_after_desktop_1440.png"
    w = int(sys.argv[3]) if len(sys.argv) > 3 else 1440
    h = int(sys.argv[4]) if len(sys.argv) > 4 else 900
    take_screenshot(url, out, w, h)
