"""
Gerador de relatorios PDF com ReportLab.
Nomenclatura: TIPO_DDMMYYYY_HHMM.pdf
Salva o registro (caminho) no banco de dados ativo.
"""

from __future__ import annotations

import sqlite3
from datetime import datetime
from pathlib import Path

from reportlab.lib import colors
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import mm
from reportlab.platypus import (
    SimpleDocTemplate,
    Table,
    TableStyle,
    Paragraph,
    Spacer,
    HRFlowable,
)

from . import config
from . import database as db


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

def _gen_filename(tipo: str) -> str:
    """Gera nome no formato TIPO_DDMMYYYY_HHMM.pdf."""
    now = datetime.now()
    return f"{tipo}_{now.strftime('%d%m%Y_%H%M')}.pdf"


def _currency(value: float) -> str:
    """Formata valor como moeda BRL."""
    return f"R$ {value:,.2f}".replace(",", "X").replace(".", ",").replace("X", ".")


def _build_styles() -> dict:
    """Retorna estilos customizados para os PDFs."""
    ss = getSampleStyleSheet()
    title_style = ParagraphStyle(
        "CustomTitle",
        parent=ss["Title"],
        fontSize=18,
        spaceAfter=6 * mm,
        textColor=colors.HexColor("#1e293b"),
    )
    subtitle_style = ParagraphStyle(
        "CustomSubtitle",
        parent=ss["Normal"],
        fontSize=10,
        textColor=colors.HexColor("#64748b"),
        spaceAfter=4 * mm,
    )
    normal = ss["Normal"]
    return {"title": title_style, "subtitle": subtitle_style, "normal": normal}


def _header_table_style() -> TableStyle:
    return TableStyle([
        ("BACKGROUND", (0, 0), (-1, 0), colors.HexColor("#1e293b")),
        ("TEXTCOLOR", (0, 0), (-1, 0), colors.white),
        ("FONTNAME", (0, 0), (-1, 0), "Helvetica-Bold"),
        ("FONTSIZE", (0, 0), (-1, 0), 9),
        ("BOTTOMPADDING", (0, 0), (-1, 0), 8),
        ("TOPPADDING", (0, 0), (-1, 0), 8),
        ("BACKGROUND", (0, 1), (-1, -1), colors.HexColor("#f8fafc")),
        ("TEXTCOLOR", (0, 1), (-1, -1), colors.HexColor("#334155")),
        ("FONTNAME", (0, 1), (-1, -1), "Helvetica"),
        ("FONTSIZE", (0, 1), (-1, -1), 8),
        ("GRID", (0, 0), (-1, -1), 0.5, colors.HexColor("#e2e8f0")),
        ("ROWBACKGROUNDS", (0, 1), (-1, -1), [colors.white, colors.HexColor("#f1f5f9")]),
        ("ALIGN", (-1, 0), (-1, -1), "RIGHT"),
        ("TOPPADDING", (0, 1), (-1, -1), 5),
        ("BOTTOMPADDING", (0, 1), (-1, -1), 5),
    ])


# ---------------------------------------------------------------------------
# Relatorio: Contas a Pagar
# ---------------------------------------------------------------------------

def gerar_relatorio_contas(
    conn: sqlite3.Connection,
    periodo_inicio: str | None = None,
    periodo_fim: str | None = None,
) -> Path:
    """Gera PDF com todas as contas no periodo."""
    tipo = "CONTAS"
    filename = _gen_filename(tipo)
    filepath = config.get_pdf_dir() / filename
    styles = _build_styles()

    contas = db.list_contas(conn)
    if periodo_inicio and periodo_fim:
        contas = [
            c for c in contas
            if periodo_inicio <= c["data_vencimento"] <= periodo_fim
        ]

    doc = SimpleDocTemplate(str(filepath), pagesize=A4,
                            leftMargin=15 * mm, rightMargin=15 * mm,
                            topMargin=20 * mm, bottomMargin=20 * mm)
    elements = []

    elements.append(Paragraph("Relatorio de Contas a Pagar", styles["title"]))
    sub = f"Gerado em {datetime.now().strftime('%d/%m/%Y %H:%M')}"
    if periodo_inicio and periodo_fim:
        sub += f" | Periodo: {periodo_inicio} a {periodo_fim}"
    elements.append(Paragraph(sub, styles["subtitle"]))
    elements.append(HRFlowable(width="100%", thickness=1, color=colors.HexColor("#e2e8f0")))
    elements.append(Spacer(1, 4 * mm))

    header = ["#", "Descricao", "Categoria", "Vencimento", "Status", "Valor"]
    data = [header]
    total = 0.0
    for c in contas:
        total += c["valor"]
        data.append([
            str(c["id"]),
            c["descricao"][:40],
            c.get("categoria") or "-",
            c["data_vencimento"],
            c["status"],
            _currency(c["valor"]),
        ])

    data.append(["", "", "", "", "TOTAL", _currency(total)])

    col_widths = [25, 150, 80, 70, 60, 75]
    table = Table(data, colWidths=col_widths, repeatRows=1)
    table.setStyle(_header_table_style())
    elements.append(table)

    doc.build(elements)

    # Salva registro no banco
    db.save_relatorio(conn, {
        "tipo": tipo,
        "caminho_pdf": str(filepath),
        "periodo_inicio": periodo_inicio,
        "periodo_fim": periodo_fim,
    })

    return filepath


# ---------------------------------------------------------------------------
# Relatorio: Entradas
# ---------------------------------------------------------------------------

def gerar_relatorio_entradas(
    conn: sqlite3.Connection,
    periodo_inicio: str | None = None,
    periodo_fim: str | None = None,
) -> Path:
    """Gera PDF com todas as entradas no periodo."""
    tipo = "ENTRADAS"
    filename = _gen_filename(tipo)
    filepath = config.get_pdf_dir() / filename
    styles = _build_styles()

    entradas = db.list_entradas(conn)
    if periodo_inicio and periodo_fim:
        entradas = [
            e for e in entradas
            if periodo_inicio <= e["data_entrada"] <= periodo_fim
        ]

    doc = SimpleDocTemplate(str(filepath), pagesize=A4,
                            leftMargin=15 * mm, rightMargin=15 * mm,
                            topMargin=20 * mm, bottomMargin=20 * mm)
    elements = []

    elements.append(Paragraph("Relatorio de Entradas", styles["title"]))
    sub = f"Gerado em {datetime.now().strftime('%d/%m/%Y %H:%M')}"
    if periodo_inicio and periodo_fim:
        sub += f" | Periodo: {periodo_inicio} a {periodo_fim}"
    elements.append(Paragraph(sub, styles["subtitle"]))
    elements.append(HRFlowable(width="100%", thickness=1, color=colors.HexColor("#e2e8f0")))
    elements.append(Spacer(1, 4 * mm))

    header = ["#", "Descricao", "Origem", "Data", "Valor"]
    data = [header]
    total = 0.0
    for e in entradas:
        total += e["valor"]
        data.append([
            str(e["id"]),
            e["descricao"][:50],
            e.get("origem") or "-",
            e["data_entrada"],
            _currency(e["valor"]),
        ])

    data.append(["", "", "", "TOTAL", _currency(total)])

    col_widths = [25, 180, 80, 80, 80]
    table = Table(data, colWidths=col_widths, repeatRows=1)
    table.setStyle(_header_table_style())
    elements.append(table)

    doc.build(elements)

    db.save_relatorio(conn, {
        "tipo": tipo,
        "caminho_pdf": str(filepath),
        "periodo_inicio": periodo_inicio,
        "periodo_fim": periodo_fim,
    })

    return filepath


# ---------------------------------------------------------------------------
# Relatorio: Resumo Financeiro
# ---------------------------------------------------------------------------

def gerar_relatorio_resumo(
    conn: sqlite3.Connection,
    mes: str | None = None,
) -> Path:
    """Gera PDF com resumo financeiro (dashboard em papel)."""
    tipo = "RESUMO"
    filename = _gen_filename(tipo)
    filepath = config.get_pdf_dir() / filename
    styles = _build_styles()

    totals = db.dashboard_totals(conn, mes)

    doc = SimpleDocTemplate(str(filepath), pagesize=A4,
                            leftMargin=15 * mm, rightMargin=15 * mm,
                            topMargin=20 * mm, bottomMargin=20 * mm)
    elements = []

    titulo = "Resumo Financeiro"
    if mes:
        titulo += f" — {mes}"
    elements.append(Paragraph(titulo, styles["title"]))
    elements.append(Paragraph(
        f"Gerado em {datetime.now().strftime('%d/%m/%Y %H:%M')}",
        styles["subtitle"],
    ))
    elements.append(HRFlowable(width="100%", thickness=1, color=colors.HexColor("#e2e8f0")))
    elements.append(Spacer(1, 6 * mm))

    summary_data = [
        ["Indicador", "Valor"],
        ["Total de Entradas", _currency(totals["total_entradas"])],
        ["Total de Saidas", _currency(totals["total_saidas"])],
        ["Saldo", _currency(totals["saldo"])],
        ["Mensalidades (total)", _currency(totals["total_mensalidades"])],
        ["Mensalidades Pagas", _currency(totals["mensalidades_pagas"])],
        ["Mensalidades Pendentes", _currency(totals["mensalidades_pendentes"])],
        ["Credito Casa Acumulado", _currency(totals["total_credito_casa"])],
        ["Contas Pendentes (qtd)", str(totals["contas_pendentes"])],
    ]

    table = Table(summary_data, colWidths=[250, 200], repeatRows=1)
    table.setStyle(_header_table_style())
    elements.append(table)

    doc.build(elements)

    db.save_relatorio(conn, {
        "tipo": tipo,
        "caminho_pdf": str(filepath),
        "periodo_inicio": mes,
        "periodo_fim": mes,
    })

    return filepath
