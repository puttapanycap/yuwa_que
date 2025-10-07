#!/usr/bin/env python3
"""
TTS API Server for Queue System
================================

A unified Text-to-Speech API server using FastAPI and gTTS.
This server provides endpoints for converting text to speech audio files
and is designed to work with the queue system's audio calling features.

Features:
- FastAPI-based RESTful API
- gTTS (Google Text-to-Speech) engine
- Support for multiple languages
- CORS enabled for cross-origin requests
- Health check endpoint
- JSON and binary response formats

Running the server:
------------------
Basic usage:
    python server.py

With custom configuration:
    TTS_API_HOST=0.0.0.0 TTS_API_PORT=5000 python server.py

Or using uvicorn directly:
    uvicorn server:app --host 0.0.0.0 --port 5000 --reload

API Endpoints:
-------------
POST /tts - Convert text to speech (JSON body)
GET /tts - Convert text to speech (query parameters)
GET /health - Health check endpoint

Example Usage:
-------------
# Using curl (POST):
curl -X POST http://localhost:5000/tts \
  -H "Content-Type: application/json" \
  -d '{"text": "สวัสดีค่ะ ยินดีต้อนรับ", "lang": "th"}' \
  --output speech.mp3

# Using curl (GET):
curl -G http://localhost:5000/tts \
  --data-urlencode "text=สวัสดีค่ะ" \
  --data-urlencode "lang=th" \
  --output speech.mp3

# From PHP (as configured in the queue system):
curl -X POST http://localhost:5000/tts \
  -H "Content-Type: application/json" \
  -d '{"text": "{{_TEXT_TO_SPECH_}}", "lang": "th"}' \
  --silent --show-error
"""

from __future__ import annotations

import io
import os
from typing import Optional

from fastapi import FastAPI, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from fastapi.responses import Response, StreamingResponse
from pydantic import BaseModel, Field

try:
    from gtts import gTTS
except ImportError as exc:
    raise ImportError(
        "gTTS is required. Install it with: pip install gTTS>=2.5.0"
    ) from exc


# Configuration from environment variables
DEFAULT_LANG = os.getenv("TTS_DEFAULT_LANG", "th")
DEFAULT_TLD = os.getenv("TTS_DEFAULT_TLD", "com")
MAX_TEXT_LENGTH = int(os.getenv("TTS_MAX_TEXT_LENGTH", "800"))
API_HOST = os.getenv("TTS_API_HOST", "0.0.0.0")
API_PORT = int(os.getenv("TTS_API_PORT", "5000"))
API_DEBUG = os.getenv("TTS_API_DEBUG", "false").lower() in {"1", "true", "yes"}


class TTSRequest(BaseModel):
    """Schema for POST requests to /tts endpoint."""
    
    text: str = Field(..., description="Text to convert to speech", min_length=1, max_length=MAX_TEXT_LENGTH)
    lang: Optional[str] = Field(DEFAULT_LANG, description="Language code (e.g., 'th', 'en', 'ja')")
    slow: Optional[bool] = Field(False, description="Speak slowly")
    tld: Optional[str] = Field(DEFAULT_TLD, description="Top-level domain for accent")


class HealthResponse(BaseModel):
    """Schema for health check response."""
    
    success: bool
    engine: str
    version: str
    status: str


app = FastAPI(
    title="TTS API Server",
    description="Text-to-Speech API Server for Queue System using gTTS",
    version="1.0.0",
    docs_url="/docs",
    redoc_url="/redoc",
)

# Enable CORS for all origins (adjust as needed for production)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)


def synthesize_speech(
    text: str,
    lang: str = DEFAULT_LANG,
    slow: bool = False,
    tld: str = DEFAULT_TLD,
) -> bytes:
    """
    Generate speech audio from text using gTTS.
    
    Args:
        text: The text to convert to speech
        lang: Language code (will be shortened to base language if includes region)
        slow: Whether to speak slowly
        tld: Top-level domain for accent selection
        
    Returns:
        bytes: MP3 audio data
        
    Raises:
        HTTPException: If synthesis fails
    """
    if not text or not text.strip():
        raise HTTPException(
            status_code=400,
            detail="กรุณาระบุข้อความสำหรับแปลงเป็นเสียง (Text is required)"
        )
    
    if len(text) > MAX_TEXT_LENGTH:
        raise HTTPException(
            status_code=400,
            detail=f"ข้อความยาวเกินกำหนด {MAX_TEXT_LENGTH} อักขระ (Text too long)"
        )
    
    # Extract base language code (e.g., "th" from "th-TH")
    lang_code = lang.split("-")[0] if lang else DEFAULT_LANG
    
    try:
        # Generate speech using gTTS
        tts = gTTS(text=text, lang=lang_code, slow=slow, tld=tld)
        
        # Write to BytesIO buffer
        audio_buffer = io.BytesIO()
        tts.write_to_fp(audio_buffer)
        audio_buffer.seek(0)
        
        return audio_buffer.read()
        
    except ValueError as exc:
        raise HTTPException(
            status_code=400,
            detail=f"ไม่รองรับภาษาหรือการตั้งค่าที่ระบุ (Invalid language or settings): {exc}"
        ) from exc
    except Exception as exc:
        raise HTTPException(
            status_code=500,
            detail=f"ไม่สามารถสร้างเสียงได้ (TTS generation failed): {exc}"
        ) from exc


@app.post("/tts", response_class=Response)
async def tts_post(request: TTSRequest) -> Response:
    """
    Convert text to speech via POST request.
    
    Expects a JSON body with text, lang (optional), slow (optional), and tld (optional).
    Returns MP3 audio data.
    
    Example:
        {
            "text": "สวัสดีค่ะ ยินดีต้อนรับ",
            "lang": "th",
            "slow": false,
            "tld": "com"
        }
    """
    audio_data = synthesize_speech(
        text=request.text,
        lang=request.lang or DEFAULT_LANG,
        slow=request.slow or False,
        tld=request.tld or DEFAULT_TLD,
    )
    
    return Response(
        content=audio_data,
        media_type="audio/mpeg",
        headers={
            "Content-Disposition": 'inline; filename="speech.mp3"',
            "X-TTS-Engine": "gTTS",
            "Cache-Control": "no-store, no-cache, must-revalidate",
        }
    )


@app.get("/tts", response_class=Response)
async def tts_get(
    text: str = Query(..., description="Text to convert to speech", min_length=1),
    lang: str = Query(DEFAULT_LANG, description="Language code (e.g., 'th', 'en', 'ja')"),
    slow: bool = Query(False, description="Speak slowly"),
    tld: str = Query(DEFAULT_TLD, description="Top-level domain for accent"),
) -> Response:
    """
    Convert text to speech via GET request.
    
    Use query parameters to provide input.
    Returns MP3 audio data.
    
    Example:
        GET /tts?text=สวัสดีค่ะ&lang=th
    """
    audio_data = synthesize_speech(
        text=text,
        lang=lang,
        slow=slow,
        tld=tld,
    )
    
    return Response(
        content=audio_data,
        media_type="audio/mpeg",
        headers={
            "Content-Disposition": 'inline; filename="speech.mp3"',
            "X-TTS-Engine": "gTTS",
            "Cache-Control": "no-store, no-cache, must-revalidate",
        }
    )


@app.get("/health", response_model=HealthResponse)
async def health_check() -> HealthResponse:
    """
    Health check endpoint.
    
    Returns the status of the TTS API server.
    """
    try:
        # Try to import gTTS to verify it's available
        import gtts
        gtts_version = getattr(gtts, "__version__", "unknown")
    except Exception:
        gtts_version = "unknown"
    
    return HealthResponse(
        success=True,
        engine="gTTS",
        version=gtts_version,
        status="operational"
    )


@app.get("/")
async def root():
    """Root endpoint with API information."""
    return {
        "service": "TTS API Server",
        "version": "1.0.0",
        "engine": "gTTS",
        "endpoints": {
            "POST /tts": "Convert text to speech (JSON body)",
            "GET /tts": "Convert text to speech (query parameters)",
            "GET /health": "Health check",
            "GET /docs": "API documentation (Swagger UI)",
            "GET /redoc": "API documentation (ReDoc)"
        },
        "example": {
            "curl_post": 'curl -X POST http://localhost:5000/tts -H "Content-Type: application/json" -d \'{"text": "สวัสดีค่ะ", "lang": "th"}\' --output speech.mp3',
            "curl_get": 'curl -G http://localhost:5000/tts --data-urlencode "text=สวัสดีค่ะ" --data-urlencode "lang=th" --output speech.mp3'
        }
    }


if __name__ == "__main__":
    import uvicorn
    
    print(f"""
╔══════════════════════════════════════════════════════════════╗
║          TTS API Server for Queue System                    ║
╚══════════════════════════════════════════════════════════════╝

Starting server...
  • Host: {API_HOST}
  • Port: {API_PORT}
  • Debug: {API_DEBUG}
  • Default Language: {DEFAULT_LANG}
  • Max Text Length: {MAX_TEXT_LENGTH}

Available at:
  • API: http://{API_HOST if API_HOST != '0.0.0.0' else 'localhost'}:{API_PORT}
  • Docs: http://{API_HOST if API_HOST != '0.0.0.0' else 'localhost'}:{API_PORT}/docs
  • Health: http://{API_HOST if API_HOST != '0.0.0.0' else 'localhost'}:{API_PORT}/health

Press Ctrl+C to stop
    """)
    
    uvicorn.run(
        app,
        host=API_HOST,
        port=API_PORT,
        log_level="debug" if API_DEBUG else "info",
    )
