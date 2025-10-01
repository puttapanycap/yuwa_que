"""Simple gTTS-based HTTP API compatible with the queue audio system."""
from __future__ import annotations

import io
import os
from typing import Any, Dict

from flask import Flask, Response, jsonify, request, send_file
from gtts import gTTS


DEFAULT_LANG = os.getenv("TTS_DEFAULT_LANG", "th")
DEFAULT_TLD = os.getenv("TTS_DEFAULT_TLD", "com")
DEFAULT_FILENAME = os.getenv("TTS_DEFAULT_FILENAME", "speech.mp3")
MAX_TEXT_LENGTH = int(os.getenv("TTS_MAX_TEXT_LENGTH", "800"))

app = Flask(__name__)


def _error(message: str, status: int = 400) -> Response:
    payload: Dict[str, Any] = {"success": False, "message": message}
    response = jsonify(payload)
    response.status_code = status
    return response


@app.post("/tts")
def synthesize() -> Response:
    data = request.get_json(silent=True)
    if not isinstance(data, dict):
        return _error("กรุณาส่งข้อมูลในรูปแบบ JSON")

    text = str(data.get("text", "")).strip()
    if not text:
        return _error("กรุณาระบุข้อความสำหรับแปลงเป็นเสียง")

    if len(text) > MAX_TEXT_LENGTH:
        return _error(f"ข้อความยาวเกินกำหนด ({MAX_TEXT_LENGTH} อักขระ)")

    lang = str(data.get("lang", DEFAULT_LANG)).strip() or DEFAULT_LANG
    slow_raw = data.get("slow", False)
    if isinstance(slow_raw, str):
        slow = slow_raw.lower() in {"1", "true", "yes"}
    else:
        slow = bool(slow_raw)
    tld = str(data.get("tld", DEFAULT_TLD)).strip() or DEFAULT_TLD
    filename = str(data.get("filename", DEFAULT_FILENAME)).strip() or DEFAULT_FILENAME

    # gTTS expects language code without region, e.g. "th" from "th-TH"
    lang_short = lang.split("-")[0]

    try:
        tts = gTTS(text=text, lang=lang_short, slow=slow, tld=tld)
        audio_io = io.BytesIO()
        tts.write_to_fp(audio_io)
        audio_io.seek(0)
    except ValueError as exc:
        return _error(f"ไม่รองรับภาษาหรือการตั้งค่าที่ระบุ: {exc}")
    except Exception as exc:  # pragma: no cover - unexpected errors
        return _error(f"ไม่สามารถสร้างเสียงได้: {exc}", status=500)

    response = send_file(
        audio_io,
        mimetype="audio/mpeg",
        as_attachment=False,
        download_name=filename if filename.lower().endswith(".mp3") else f"{filename}.mp3",
    )
    response.headers["X-TTS-Engine"] = "gTTS"
    response.headers["Cache-Control"] = "no-store"
    return response


@app.get("/health")
def health() -> Response:
    return jsonify({"success": True, "engine": "gTTS"})


if __name__ == "__main__":
    host = os.getenv("TTS_API_HOST", "0.0.0.0")
    port = int(os.getenv("TTS_API_PORT", "5000"))
    debug = os.getenv("TTS_API_DEBUG", "false").lower() in {"1", "true", "yes"}
    app.run(host=host, port=port, debug=debug)
