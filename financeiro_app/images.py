"""
Processamento de imagens de comprovantes.
- Redimensionamento automatico para <= 500 KB via Pillow
- Nomenclatura padrao: ID_XXXX.jpg
"""

from __future__ import annotations

import io
from pathlib import Path

from PIL import Image

from . import config

MAX_SIZE_KB = 500
JPEG_QUALITY_START = 92
JPEG_QUALITY_MIN = 30
MAX_DIMENSION = 1920  # px


def _resize_if_needed(img: Image.Image) -> Image.Image:
    """Redimensiona mantendo aspect-ratio se exceder MAX_DIMENSION."""
    w, h = img.size
    if max(w, h) <= MAX_DIMENSION:
        return img
    ratio = MAX_DIMENSION / max(w, h)
    new_size = (int(w * ratio), int(h * ratio))
    return img.resize(new_size, Image.LANCZOS)


def _compress_to_max_kb(img: Image.Image, max_kb: int = MAX_SIZE_KB) -> bytes:
    """Comprime progressivamente ate ficar <= max_kb."""
    quality = JPEG_QUALITY_START
    while quality >= JPEG_QUALITY_MIN:
        buf = io.BytesIO()
        img.save(buf, format="JPEG", quality=quality, optimize=True)
        data = buf.getvalue()
        if len(data) <= max_kb * 1024:
            return data
        quality -= 5

    # Se ainda for grande, reduz dimensao e tenta de novo
    w, h = img.size
    img = img.resize((w // 2, h // 2), Image.LANCZOS)
    buf = io.BytesIO()
    img.save(buf, format="JPEG", quality=JPEG_QUALITY_MIN, optimize=True)
    return buf.getvalue()


def process_comprovante(
    source_path: str | Path,
    transaction_id: int,
) -> Path:
    """
    Processa uma imagem de comprovante:
    1. Abre a imagem original
    2. Redimensiona se necessario
    3. Comprime para <= 500 KB
    4. Salva como ID_XXXX.jpg na pasta de comprovantes

    Retorna o Path do arquivo salvo.
    """
    source = Path(source_path)
    if not source.exists():
        raise FileNotFoundError(f"Arquivo nao encontrado: {source}")

    img = Image.open(source)
    # Converte para RGB (remove alpha / paleta)
    if img.mode not in ("RGB", "L"):
        img = img.convert("RGB")

    img = _resize_if_needed(img)
    compressed = _compress_to_max_kb(img)

    # Nomenclatura: ID_XXXX.jpg
    filename = f"ID_{transaction_id:04d}.jpg"
    dest = config.get_img_dir() / filename

    dest.write_bytes(compressed)
    return dest


def get_comprovante_path(transaction_id: int) -> Path | None:
    """Retorna o path do comprovante se existir, senao None."""
    filename = f"ID_{transaction_id:04d}.jpg"
    path = config.get_img_dir() / filename
    return path if path.exists() else None
