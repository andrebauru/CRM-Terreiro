"""
Configuracao central do modulo financeiro.
Le variaveis do .env, descobre arquivos .db na raiz e gerencia paths.
"""

from __future__ import annotations

import os
import glob
from pathlib import Path
from dotenv import load_dotenv

# Raiz do app financeiro
APP_ROOT = Path(__file__).resolve().parent

# Carrega .env se existir
_env_path = APP_ROOT / ".env"
if _env_path.exists():
    load_dotenv(_env_path)


def get_db_dir() -> Path:
    """Retorna o diretorio onde os bancos .db sao procurados/criados."""
    rel = os.getenv("DB_DIR", ".")
    p = (APP_ROOT / rel).resolve()
    p.mkdir(parents=True, exist_ok=True)
    return p


def get_img_dir() -> Path:
    """Retorna o diretorio para comprovantes de imagem."""
    rel = os.getenv("IMG_DIR", "comprovantes")
    p = (APP_ROOT / rel).resolve()
    p.mkdir(parents=True, exist_ok=True)
    return p


def get_pdf_dir() -> Path:
    """Retorna o diretorio para relatorios PDF."""
    rel = os.getenv("PDF_DIR", "relatorios")
    p = (APP_ROOT / rel).resolve()
    p.mkdir(parents=True, exist_ok=True)
    return p


def get_configured_db_name() -> str | None:
    """Retorna o nome do banco configurado no .env, ou None."""
    name = os.getenv("DB_NAME", "").strip()
    if not name:
        return None
    if not name.endswith(".db"):
        name += ".db"
    return name


def discover_db_files() -> list[Path]:
    """Descobre todos os arquivos .db no diretorio configurado."""
    db_dir = get_db_dir()
    return sorted(db_dir.glob("*.db"))


def resolve_db_path(name: str | None = None) -> Path:
    """
    Resolve o caminho completo do banco de dados.
    Prioridade: nome explicito > .env > primeiro .db encontrado > padrao.
    """
    if name:
        n = name if name.endswith(".db") else name + ".db"
        return get_db_dir() / n

    configured = get_configured_db_name()
    if configured:
        return get_db_dir() / configured

    found = discover_db_files()
    if found:
        return found[0]

    return get_db_dir() / "financeiro_crm.db"
