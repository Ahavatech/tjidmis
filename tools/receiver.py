#!/usr/bin/env python3
"""Tiny local receiver used while crawling: accepts {"path": "...", "body": "..."} or {"path": "...", "b64": "..."} on 127.0.0.1:8765
and writes the file under the project root. Run: python tools/receiver.py"""
import http.server, json, os, base64
ROOT = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
class H(http.server.BaseHTTPRequestHandler):
    def do_OPTIONS(s):
        s.send_response(204); s.send_header("Access-Control-Allow-Origin", "*"); s.send_header("Access-Control-Allow-Headers", "*"); s.end_headers()
    def do_POST(s):
        d = json.loads(s.rfile.read(int(s.headers['Content-Length'])))
        p = os.path.normpath(os.path.join(ROOT, d['path']))
        assert p.startswith(ROOT)
        os.makedirs(os.path.dirname(p), exist_ok=True)
        if 'b64' in d: open(p, 'wb').write(base64.b64decode(d['b64']))
        else: open(p, 'w', encoding='utf-8').write(d['body'])
        s.send_response(200); s.send_header("Access-Control-Allow-Origin", "*"); s.end_headers(); s.wfile.write(b"ok")
    def log_message(*a): pass
http.server.HTTPServer(("127.0.0.1", 8765), H).serve_forever()
