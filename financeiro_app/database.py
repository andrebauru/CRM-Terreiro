"""
Camada de banco de dados SQLite.
- Auto-Schema Injection (CREATE TABLE IF NOT EXISTS)
- Validacao de arquivo .db
- CRUD para contas, entradas, mensalidades, credito_casa e relatorios
"""

from __future__ import annotations

import sqlite3
from pathlib import Path
from typing import Any


# ---------------------------------------------------------------------------
# Schema completo
# ---------------------------------------------------------------------------

_SCHEMA_SQL = """
CREATE TABLE IF NOT EXISTS contas (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao     TEXT    NOT NULL,
    valor         REAL    NOT NULL DEFAULT 0,
    tipo          TEXT    NOT NULL DEFAULT 'saida',
    categoria     TEXT,
    data_vencimento TEXT  NOT NULL,
    data_pagamento  TEXT,
    status        TEXT    NOT NULL DEFAULT 'Pendente',
    comprovante_path TEXT,
    created_at    TEXT    DEFAULT (datetime('now','localtime')),
    updated_at    TEXT    DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS entradas (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    descricao     TEXT    NOT NULL,
    valor         REAL    NOT NULL DEFAULT 0,
    origem        TEXT,
    referencia_id INTEGER,
    data_entrada  TEXT    NOT NULL,
    comprovante_path TEXT,
    created_at    TEXT    DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS mensalidades_filhos (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    filho_nome    TEXT    NOT NULL,
    valor         REAL    NOT NULL DEFAULT 0,
    mes_referencia TEXT   NOT NULL,
    status        TEXT    NOT NULL DEFAULT 'Pendente',
    data_pagamento TEXT,
    credito_casa  REAL    DEFAULT 0,
    comprovante_path TEXT,
    created_at    TEXT    DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS credito_casa (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    entrada_id    INTEGER,
    valor_original REAL   NOT NULL,
    percentual    REAL    NOT NULL DEFAULT 10.0,
    valor_credito REAL    NOT NULL,
    descricao     TEXT,
    data          TEXT    NOT NULL,
    created_at    TEXT    DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS relatorios (
    id            INTEGER PRIMARY KEY AUTOINCREMENT,
    tipo          TEXT    NOT NULL,
    caminho_pdf   TEXT    NOT NULL,
    periodo_inicio TEXT,
    periodo_fim   TEXT,
    created_at    TEXT    DEFAULT (datetime('now','localtime'))
);

CREATE TABLE IF NOT EXISTS configuracoes (
    chave TEXT PRIMARY KEY,
    valor TEXT
);
"""


# ---------------------------------------------------------------------------
# Validacao e conexao
# ---------------------------------------------------------------------------

class DatabaseError(Exception):
    """Erro amigavel de banco de dados."""


def validate_db_file(path: Path) -> bool:
    """Verifica se o arquivo e um banco SQLite valido (magic bytes)."""
    if not path.exists():
        return True  # sera criado
    try:
        with open(path, "rb") as f:
            header = f.read(16)
        return header[:6] == b"SQLite" or len(header) == 0
    except OSError:
        return False


def connect(db_path: Path) -> sqlite3.Connection:
    """
    Abre (ou cria) o banco SQLite, injeta o schema e retorna a conexao.
    Levanta DatabaseError se o arquivo nao for um DB valido.
    """
    if not validate_db_file(db_path):
        raise DatabaseError(
            f"O arquivo '{db_path.name}' nao e um banco de dados SQLite valido.\n"
            "Selecione outro arquivo ou crie um novo."
        )

    conn = sqlite3.connect(str(db_path))
    conn.row_factory = sqlite3.Row
    conn.execute("PRAGMA journal_mode=WAL")
    conn.execute("PRAGMA foreign_keys=ON")

    # Auto-Schema Injection
    conn.executescript(_SCHEMA_SQL)
    conn.commit()
    return conn


# ---------------------------------------------------------------------------
# Helpers genericos
# ---------------------------------------------------------------------------

def _dict_from_row(row: sqlite3.Row | None) -> dict[str, Any] | None:
    if row is None:
        return None
    return dict(row)


def _dicts_from_rows(rows: list[sqlite3.Row]) -> list[dict[str, Any]]:
    return [dict(r) for r in rows]


# ---------------------------------------------------------------------------
# CRUD: Contas (despesas / contas a pagar)
# ---------------------------------------------------------------------------

def list_contas(conn: sqlite3.Connection, status: str | None = None) -> list[dict]:
    sql = "SELECT * FROM contas"
    params: list[Any] = []
    if status:
        sql += " WHERE status = ?"
        params.append(status)
    sql += " ORDER BY data_vencimento DESC"
    return _dicts_from_rows(conn.execute(sql, params).fetchall())


def get_conta(conn: sqlite3.Connection, conta_id: int) -> dict | None:
    row = conn.execute("SELECT * FROM contas WHERE id = ?", (conta_id,)).fetchone()
    return _dict_from_row(row)


def create_conta(conn: sqlite3.Connection, data: dict) -> int:
    cur = conn.execute(
        """INSERT INTO contas (descricao, valor, tipo, categoria, data_vencimento, status)
           VALUES (:descricao, :valor, :tipo, :categoria, :data_vencimento, :status)""",
        data,
    )
    conn.commit()
    return cur.lastrowid  # type: ignore[return-value]


def update_conta(conn: sqlite3.Connection, conta_id: int, data: dict) -> None:
    data["id"] = conta_id
    conn.execute(
        """UPDATE contas
           SET descricao = :descricao, valor = :valor, tipo = :tipo,
               categoria = :categoria, data_vencimento = :data_vencimento,
               status = :status, data_pagamento = :data_pagamento,
               comprovante_path = :comprovante_path,
               updated_at = datetime('now','localtime')
           WHERE id = :id""",
        data,
    )
    conn.commit()


def delete_conta(conn: sqlite3.Connection, conta_id: int) -> None:
    conn.execute("DELETE FROM contas WHERE id = ?", (conta_id,))
    conn.commit()


# ---------------------------------------------------------------------------
# CRUD: Entradas (receitas)
# ---------------------------------------------------------------------------

def list_entradas(conn: sqlite3.Connection) -> list[dict]:
    return _dicts_from_rows(
        conn.execute("SELECT * FROM entradas ORDER BY data_entrada DESC").fetchall()
    )


def create_entrada(conn: sqlite3.Connection, data: dict) -> int:
    cur = conn.execute(
        """INSERT INTO entradas (descricao, valor, origem, referencia_id, data_entrada)
           VALUES (:descricao, :valor, :origem, :referencia_id, :data_entrada)""",
        data,
    )
    conn.commit()
    return cur.lastrowid  # type: ignore[return-value]


def update_entrada(conn: sqlite3.Connection, entrada_id: int, data: dict) -> None:
    data["id"] = entrada_id
    conn.execute(
        """UPDATE entradas
           SET descricao = :descricao, valor = :valor, origem = :origem,
               referencia_id = :referencia_id, data_entrada = :data_entrada,
               comprovante_path = :comprovante_path
           WHERE id = :id""",
        data,
    )
    conn.commit()


def delete_entrada(conn: sqlite3.Connection, entrada_id: int) -> None:
    conn.execute("DELETE FROM entradas WHERE id = ?", (entrada_id,))
    conn.commit()


# ---------------------------------------------------------------------------
# CRUD: Mensalidades Filhos
# ---------------------------------------------------------------------------

def list_mensalidades(conn: sqlite3.Connection, mes: str | None = None) -> list[dict]:
    sql = "SELECT * FROM mensalidades_filhos"
    params: list[Any] = []
    if mes:
        sql += " WHERE mes_referencia = ?"
        params.append(mes)
    sql += " ORDER BY mes_referencia DESC, filho_nome ASC"
    return _dicts_from_rows(conn.execute(sql, params).fetchall())


def create_mensalidade(conn: sqlite3.Connection, data: dict) -> int:
    cur = conn.execute(
        """INSERT INTO mensalidades_filhos
              (filho_nome, valor, mes_referencia, status, credito_casa)
           VALUES (:filho_nome, :valor, :mes_referencia, :status, :credito_casa)""",
        data,
    )
    conn.commit()
    return cur.lastrowid  # type: ignore[return-value]


def update_mensalidade(conn: sqlite3.Connection, mid: int, data: dict) -> None:
    data["id"] = mid
    conn.execute(
        """UPDATE mensalidades_filhos
           SET filho_nome = :filho_nome, valor = :valor,
               mes_referencia = :mes_referencia, status = :status,
               data_pagamento = :data_pagamento,
               credito_casa = :credito_casa,
               comprovante_path = :comprovante_path
           WHERE id = :id""",
        data,
    )
    conn.commit()


# ---------------------------------------------------------------------------
# CRUD: Credito Casa
# ---------------------------------------------------------------------------

def list_credito_casa(conn: sqlite3.Connection) -> list[dict]:
    return _dicts_from_rows(
        conn.execute("SELECT * FROM credito_casa ORDER BY data DESC").fetchall()
    )


def create_credito_casa(conn: sqlite3.Connection, data: dict) -> int:
    cur = conn.execute(
        """INSERT INTO credito_casa
              (entrada_id, valor_original, percentual, valor_credito, descricao, data)
           VALUES (:entrada_id, :valor_original, :percentual, :valor_credito, :descricao, :data)""",
        data,
    )
    conn.commit()
    return cur.lastrowid  # type: ignore[return-value]


# ---------------------------------------------------------------------------
# CRUD: Relatorios
# ---------------------------------------------------------------------------

def save_relatorio(conn: sqlite3.Connection, data: dict) -> int:
    cur = conn.execute(
        """INSERT INTO relatorios (tipo, caminho_pdf, periodo_inicio, periodo_fim)
           VALUES (:tipo, :caminho_pdf, :periodo_inicio, :periodo_fim)""",
        data,
    )
    conn.commit()
    return cur.lastrowid  # type: ignore[return-value]


def list_relatorios(conn: sqlite3.Connection) -> list[dict]:
    return _dicts_from_rows(
        conn.execute("SELECT * FROM relatorios ORDER BY created_at DESC").fetchall()
    )


# ---------------------------------------------------------------------------
# Dashboard / Aggregations
# ---------------------------------------------------------------------------

def dashboard_totals(conn: sqlite3.Connection, mes: str | None = None) -> dict:
    """Retorna totais para o dashboard."""
    where_mes = ""
    params_ent: list[Any] = []
    params_conta: list[Any] = []
    params_mens: list[Any] = []

    if mes:
        where_ent = " WHERE substr(data_entrada, 1, 7) = ?"
        params_ent.append(mes)
        where_conta = " WHERE substr(data_vencimento, 1, 7) = ?"
        params_conta.append(mes)
        where_mens = " WHERE mes_referencia = ?"
        params_mens.append(mes)
    else:
        where_ent = where_conta = where_mens = ""

    total_entradas = conn.execute(
        f"SELECT COALESCE(SUM(valor), 0) FROM entradas{where_ent}", params_ent
    ).fetchone()[0]

    total_saidas = conn.execute(
        f"SELECT COALESCE(SUM(valor), 0) FROM contas WHERE tipo = 'saida'{' AND substr(data_vencimento, 1, 7) = ?' if mes else ''}",
        params_conta,
    ).fetchone()[0]

    total_mensalidades = conn.execute(
        f"SELECT COALESCE(SUM(valor), 0) FROM mensalidades_filhos{where_mens}", params_mens
    ).fetchone()[0]

    mensalidades_pagas = conn.execute(
        f"SELECT COALESCE(SUM(valor), 0) FROM mensalidades_filhos WHERE status = 'Pago'{' AND mes_referencia = ?' if mes else ''}",
        params_mens,
    ).fetchone()[0]

    total_credito = conn.execute(
        "SELECT COALESCE(SUM(valor_credito), 0) FROM credito_casa"
    ).fetchone()[0]

    contas_pendentes = conn.execute(
        f"SELECT COUNT(*) FROM contas WHERE status = 'Pendente'{' AND substr(data_vencimento, 1, 7) = ?' if mes else ''}",
        params_conta,
    ).fetchone()[0]

    return {
        "total_entradas": total_entradas,
        "total_saidas": total_saidas,
        "saldo": total_entradas - total_saidas,
        "total_mensalidades": total_mensalidades,
        "mensalidades_pagas": mensalidades_pagas,
        "mensalidades_pendentes": total_mensalidades - mensalidades_pagas,
        "total_credito_casa": total_credito,
        "contas_pendentes": contas_pendentes,
    }
