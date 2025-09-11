#!/usr/bin/env python3
"""
Google Cloud Text-to-Speech helper script

Usage examples:
  python voice.py --text "ขอเชิญหมายเลข A 0 0 1 ที่ ห้องตรวจ 1" --lang th-TH \
                  --voice th-TH-Standard-A --rate 1.0 --pitch 0.0 \
                  --audio-format mp3 --out storage/tts/test.mp3

Environment:
  Set GOOGLE_APPLICATION_CREDENTIALS to your service account JSON path.

Exit codes:
  0 on success, non-zero on failure. Always prints a JSON object to stdout.
"""
from __future__ import annotations

import argparse
import json
import os
import sys


def _fail(msg: str, code: int = 1):
    print(json.dumps({"success": False, "message": msg}, ensure_ascii=False))
    sys.exit(code)


def main():
    parser = argparse.ArgumentParser(description="Google TTS CLI")
    parser.add_argument("--text", help="Text to synthesize")
    parser.add_argument("--text-file", help="Path to a UTF-8 text file", default=None)
    parser.add_argument("--lang", default="th-TH", help="Language code, e.g., th-TH")
    parser.add_argument("--voice", default="th-TH-Standard-A", help="Voice name")
    parser.add_argument("--rate", type=float, default=1.0, help="Speaking rate 0.25–4.0")
    parser.add_argument("--pitch", type=float, default=0.0, help="Pitch -20.0–20.0")
    parser.add_argument("--audio-format", default="mp3", choices=["mp3", "ogg", "wav"], help="Output format")
    parser.add_argument("--credentials", help="Path to GCP service account JSON (optional)")
    parser.add_argument("--engine", default="google", choices=["google", "gtts"], help="TTS engine: 'google' (Cloud) or 'gtts' (no credentials)")
    parser.add_argument("--out", required=True, help="Output file path")
    args = parser.parse_args()

    # Load text
    text = args.text
    if not text and args.text_file:
        try:
            with open(args.text_file, "r", encoding="utf-8") as f:
                text = f.read()
        except Exception as e:
            _fail(f"Failed to read text file: {e}")
    if not text:
        # Read from stdin as fallback
        if not sys.stdin.isatty():
            text = sys.stdin.read().strip()
    if not text:
        _fail("No text provided")

    # Check dependency
    # Lazy imports based on engine
    texttospeech = None
    DefaultCredentialsError = None
    service_account = None
    gTTS = None
    if args.engine == "google":
        try:
            from google.cloud import texttospeech  # type: ignore
            from google.auth.exceptions import DefaultCredentialsError  # type: ignore
            from google.oauth2 import service_account  # type: ignore
        except Exception:
            _fail("Missing dependency google-cloud-texttospeech. Install with: pip install google-cloud-texttospeech")
    else:  # gtts
        try:
            from gtts import gTTS  # type: ignore
        except Exception:
            _fail("Missing dependency gTTS. Install with: pip install gTTS")

    # Ensure output directory exists
    out_path = args.out
    out_dir = os.path.dirname(out_path) or "."
    os.makedirs(out_dir, exist_ok=True)

    # Build request
    if args.engine == "gtts":
        # gTTS only supports mp3 output
        out_path = args.out
        if not out_path.lower().endswith(".mp3"):
            out_path += ".mp3"
        try:
            tts = gTTS(text=text, lang=args.lang.split("-")[0])
            tts.save(out_path)
        except Exception as e:
            _fail(f"gTTS synthesis failed: {e}")
        print(json.dumps({
            "success": True,
            "path": out_path,
            "bytes": os.path.getsize(out_path) if os.path.exists(out_path) else 0,
            "lang": args.lang,
            "voice": "gtts-default",
            "rate": args.rate,
            "pitch": args.pitch,
            "format": "mp3",
            "engine": "gtts",
        }, ensure_ascii=False))
        return
    else:
        # Create Google client with optional explicit credentials
        try:
            if args.credentials:
                if not os.path.exists(args.credentials):
                    _fail(f"Credentials file not found: {args.credentials}")
                creds = service_account.Credentials.from_service_account_file(args.credentials)
                client = texttospeech.TextToSpeechClient(credentials=creds)
            else:
                client = texttospeech.TextToSpeechClient()
        except DefaultCredentialsError:
            _fail("Google TTS credentials not found. Set GOOGLE_APPLICATION_CREDENTIALS env var or use --credentials to provide a service account JSON file.")

    synthesis_input = texttospeech.SynthesisInput(text=text)
    voice_params = texttospeech.VoiceSelectionParams(
        language_code=args.lang,
        name=args.voice,
    )

    if args.audio_format == "mp3":
        audio_config = texttospeech.AudioConfig(
            audio_encoding=texttospeech.AudioEncoding.MP3,
            speaking_rate=args.rate,
            pitch=args.pitch,
        )
    elif args.audio_format == "ogg":
        audio_config = texttospeech.AudioConfig(
            audio_encoding=texttospeech.AudioEncoding.OGG_OPUS,
            speaking_rate=args.rate,
            pitch=args.pitch,
        )
    else:  # wav
        audio_config = texttospeech.AudioConfig(
            audio_encoding=texttospeech.AudioEncoding.LINEAR16,
            speaking_rate=args.rate,
            pitch=args.pitch,
        )

    try:
        response = client.synthesize_speech(
            input=synthesis_input, voice=voice_params, audio_config=audio_config
        )
    except Exception as e:
        _fail(f"Google TTS request failed: {e}")

    try:
        with open(out_path, "wb") as out_f:
            out_f.write(response.audio_content)
    except Exception as e:
        _fail(f"Failed to write audio: {e}")

    print(json.dumps({
        "success": True,
        "path": out_path,
        "bytes": len(response.audio_content),
        "lang": args.lang,
        "voice": args.voice,
        "rate": args.rate,
        "pitch": args.pitch,
        "format": args.audio_format,
        "engine": "google",
    }, ensure_ascii=False))


if __name__ == "__main__":
    main()
