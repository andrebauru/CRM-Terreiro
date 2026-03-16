"""
Interface grafica do Modulo Financeiro — CustomTkinter.
Dashboard moderno, CRUD de Contas/Entradas/Mensalidades,
seletor de banco de dados e gerador de relatorios.
"""

from __future__ import annotations

import os
import sqlite3
import subprocess
import sys
import tkinter as tk
from datetime import datetime, date
from pathlib import Path
from tkinter import filedialog, messagebox

import customtkinter as ctk

from . import config
from . import database as db
from . import finance
from . import images
from . import reports

# ---------------------------------------------------------------------------
# Aparencia
# ---------------------------------------------------------------------------
ctk.set_appearance_mode("light")
ctk.set_default_color_theme("blue")

COLOR_BG = "#f8fafc"
COLOR_SIDEBAR = "#1e293b"
COLOR_SIDEBAR_HOVER = "#334155"
COLOR_ACCENT = "#6366f1"
COLOR_RED = "#ef4444"
COLOR_GREEN = "#22c55e"
COLOR_AMBER = "#f59e0b"
COLOR_TEXT = "#0f172a"
COLOR_MUTED = "#64748b"


# ═══════════════════════════════════════════════════════════════════════════
# MAIN APP
# ═══════════════════════════════════════════════════════════════════════════

class FinanceiroApp(ctk.CTk):
    """Janela principal do modulo financeiro."""

    def __init__(self) -> None:
        super().__init__()
        self.title("CRM Terreiro — Modulo Financeiro")
        self.geometry("1200x750")
        self.minsize(960, 600)
        self.configure(fg_color=COLOR_BG)

        self.conn: sqlite3.Connection | None = None
        self.db_path: Path | None = None

        # Layout principal: sidebar + conteudo + footer
        self.grid_columnconfigure(1, weight=1)
        self.grid_rowconfigure(0, weight=1)

        self._build_sidebar()
        self._build_footer()

        # Container de conteudo (troca de frames)
        self.content = ctk.CTkFrame(self, fg_color=COLOR_BG, corner_radius=0)
        self.content.grid(row=0, column=1, sticky="nsew")
        self.content.grid_columnconfigure(0, weight=1)
        self.content.grid_rowconfigure(0, weight=1)

        self.frames: dict[str, ctk.CTkFrame] = {}
        self._current_frame: str = ""

        # Tenta conectar automaticamente
        self._auto_connect()

    # --- Sidebar -----------------------------------------------------------

    def _build_sidebar(self) -> None:
        sidebar = ctk.CTkFrame(self, width=220, fg_color=COLOR_SIDEBAR, corner_radius=0)
        sidebar.grid(row=0, column=0, rowspan=2, sticky="ns")
        sidebar.grid_propagate(False)

        # Logo
        logo_frame = ctk.CTkFrame(sidebar, fg_color="transparent")
        logo_frame.pack(fill="x", padx=16, pady=(20, 8))
        ctk.CTkLabel(
            logo_frame, text="CRM Terreiro",
            font=ctk.CTkFont(size=18, weight="bold"),
            text_color="#818cf8",
        ).pack(anchor="w")
        ctk.CTkLabel(
            logo_frame, text="Modulo Financeiro",
            font=ctk.CTkFont(size=11),
            text_color="#94a3b8",
        ).pack(anchor="w")

        sep = ctk.CTkFrame(sidebar, height=1, fg_color="#334155")
        sep.pack(fill="x", padx=16, pady=12)

        # Botoes de navegacao
        nav_items = [
            ("Dashboard", "dashboard"),
            ("Contas a Pagar", "contas"),
            ("Entradas", "entradas"),
            ("Mensalidades", "mensalidades"),
            ("Relatorios", "relatorios"),
        ]

        self._nav_buttons: dict[str, ctk.CTkButton] = {}
        for label, key in nav_items:
            btn = ctk.CTkButton(
                sidebar,
                text=f"  {label}",
                anchor="w",
                font=ctk.CTkFont(size=13),
                fg_color="transparent",
                hover_color=COLOR_SIDEBAR_HOVER,
                text_color="#cbd5e1",
                height=38,
                corner_radius=8,
                command=lambda k=key: self._show_frame(k),
            )
            btn.pack(fill="x", padx=10, pady=2)
            self._nav_buttons[key] = btn

        # Espacador
        ctk.CTkFrame(sidebar, fg_color="transparent").pack(fill="both", expand=True)

        # Botao selecionar/criar DB
        db_btn = ctk.CTkButton(
            sidebar,
            text="Selecionar / Criar DB",
            font=ctk.CTkFont(size=12),
            fg_color="#4f46e5",
            hover_color="#4338ca",
            height=36,
            corner_radius=8,
            command=self._select_database,
        )
        db_btn.pack(fill="x", padx=12, pady=(4, 16))

    # --- Footer ------------------------------------------------------------

    def _build_footer(self) -> None:
        self.footer = ctk.CTkFrame(self, height=32, fg_color="#e2e8f0", corner_radius=0)
        self.footer.grid(row=1, column=1, sticky="ew")
        self.footer.grid_propagate(False)

        self.footer_label = ctk.CTkLabel(
            self.footer,
            text="Nenhum banco conectado",
            font=ctk.CTkFont(size=11),
            text_color=COLOR_MUTED,
        )
        self.footer_label.pack(side="left", padx=12)

        self.footer_version = ctk.CTkLabel(
            self.footer,
            text="v1.0.0",
            font=ctk.CTkFont(size=10),
            text_color="#94a3b8",
        )
        self.footer_version.pack(side="right", padx=12)

    def _update_footer(self) -> None:
        if self.db_path:
            self.footer_label.configure(
                text=f"Banco: {self.db_path.name}",
                text_color=COLOR_GREEN,
            )
        else:
            self.footer_label.configure(
                text="Nenhum banco conectado",
                text_color=COLOR_MUTED,
            )

    # --- Navegacao ----------------------------------------------------------

    def _show_frame(self, name: str) -> None:
        if not self.conn:
            messagebox.showwarning("Banco de Dados", "Conecte-se a um banco antes.")
            self._select_database()
            return

        # Atualiza destaque do botao
        for key, btn in self._nav_buttons.items():
            if key == name:
                btn.configure(fg_color=COLOR_ACCENT, text_color="white")
            else:
                btn.configure(fg_color="transparent", text_color="#cbd5e1")

        # Destroi frame anterior
        for widget in self.content.winfo_children():
            widget.destroy()

        self._current_frame = name

        builders = {
            "dashboard": self._build_dashboard,
            "contas": self._build_contas,
            "entradas": self._build_entradas,
            "mensalidades": self._build_mensalidades,
            "relatorios": self._build_relatorios,
        }
        builder = builders.get(name)
        if builder:
            builder()

    # --- Conexao ao banco --------------------------------------------------

    def _auto_connect(self) -> None:
        """Tenta conectar automaticamente ao banco configurado ou descoberto."""
        try:
            path = config.resolve_db_path()
            self._connect_to(path)
            self._show_frame("dashboard")
        except db.DatabaseError:
            self._select_database()

    def _connect_to(self, path: Path) -> None:
        if self.conn:
            self.conn.close()
        self.conn = db.connect(path)
        self.db_path = path
        self._update_footer()

    def _select_database(self) -> None:
        """Abre dialog para selecionar ou criar um banco .db."""
        dialog = ctk.CTkToplevel(self)
        dialog.title("Selecionar / Criar Banco de Dados")
        dialog.geometry("480x320")
        dialog.transient(self)
        dialog.grab_set()

        ctk.CTkLabel(
            dialog, text="Banco de Dados",
            font=ctk.CTkFont(size=16, weight="bold"),
        ).pack(padx=20, pady=(20, 4))
        ctk.CTkLabel(
            dialog,
            text="Selecione um banco existente ou crie um novo.",
            font=ctk.CTkFont(size=12),
            text_color=COLOR_MUTED,
        ).pack(padx=20, pady=(0, 12))

        # Lista bancos encontrados
        found = config.discover_db_files()
        listbox_frame = ctk.CTkFrame(dialog, fg_color="transparent")
        listbox_frame.pack(fill="both", expand=True, padx=20)

        lb = tk.Listbox(listbox_frame, font=("Inter", 11), selectmode="single",
                        bd=1, relief="solid", highlightthickness=0)
        lb.pack(fill="both", expand=True)
        for f in found:
            lb.insert("end", f.name)

        btn_frame = ctk.CTkFrame(dialog, fg_color="transparent")
        btn_frame.pack(fill="x", padx=20, pady=12)

        def _open_selected():
            sel = lb.curselection()
            if not sel:
                messagebox.showinfo("Selecione", "Escolha um banco da lista.")
                return
            name = lb.get(sel[0])
            path = config.get_db_dir() / name
            try:
                self._connect_to(path)
                dialog.destroy()
                self._show_frame("dashboard")
            except db.DatabaseError as e:
                messagebox.showerror("Erro", str(e))

        def _create_new():
            path = filedialog.asksaveasfilename(
                title="Criar novo banco de dados",
                initialdir=str(config.get_db_dir()),
                defaultextension=".db",
                filetypes=[("SQLite Database", "*.db")],
            )
            if not path:
                return
            try:
                self._connect_to(Path(path))
                dialog.destroy()
                self._show_frame("dashboard")
            except db.DatabaseError as e:
                messagebox.showerror("Erro", str(e))

        def _browse():
            path = filedialog.askopenfilename(
                title="Abrir banco de dados",
                initialdir=str(config.get_db_dir()),
                filetypes=[("SQLite Database", "*.db"), ("Todos", "*.*")],
            )
            if not path:
                return
            try:
                self._connect_to(Path(path))
                dialog.destroy()
                self._show_frame("dashboard")
            except db.DatabaseError as e:
                messagebox.showerror("Erro", str(e))

        ctk.CTkButton(btn_frame, text="Abrir Selecionado", fg_color=COLOR_ACCENT,
                       command=_open_selected, width=140).pack(side="left", padx=(0, 6))
        ctk.CTkButton(btn_frame, text="Procurar...",
                       fg_color="#475569", command=_browse, width=100).pack(side="left", padx=(0, 6))
        ctk.CTkButton(btn_frame, text="Criar Novo", fg_color=COLOR_GREEN,
                       command=_create_new, width=100).pack(side="left")

    # ═══════════════════════════════════════════════════════════════════════
    # DASHBOARD
    # ═══════════════════════════════════════════════════════════════════════

    def _build_dashboard(self) -> None:
        assert self.conn
        frame = ctk.CTkScrollableFrame(self.content, fg_color=COLOR_BG)
        frame.grid(row=0, column=0, sticky="nsew", padx=0, pady=0)
        frame.grid_columnconfigure((0, 1, 2, 3), weight=1)

        # Titulo
        ctk.CTkLabel(
            frame, text="Dashboard",
            font=ctk.CTkFont(size=22, weight="bold"),
            text_color=COLOR_TEXT,
        ).grid(row=0, column=0, columnspan=2, sticky="w", padx=20, pady=(20, 4))

        mes_atual = date.today().strftime("%Y-%m")
        ctk.CTkLabel(
            frame,
            text=f"Visao geral — {date.today().strftime('%B %Y').title()}",
            font=ctk.CTkFont(size=12), text_color=COLOR_MUTED,
        ).grid(row=1, column=0, columnspan=2, sticky="w", padx=20, pady=(0, 16))

        totals = db.dashboard_totals(self.conn, mes_atual)

        cards = [
            ("Entradas", totals["total_entradas"], COLOR_GREEN, 0),
            ("Saidas", totals["total_saidas"], COLOR_RED, 1),
            ("Saldo", totals["saldo"], COLOR_ACCENT, 2),
            ("Credito Casa", totals["total_credito_casa"], COLOR_AMBER, 3),
        ]

        for label, value, color, col in cards:
            card = ctk.CTkFrame(frame, fg_color="white", corner_radius=16,
                                border_width=1, border_color="#e2e8f0")
            card.grid(row=2, column=col, padx=10, pady=8, sticky="ew")
            card.grid_columnconfigure(0, weight=1)

            ctk.CTkLabel(card, text=label, font=ctk.CTkFont(size=11),
                         text_color=COLOR_MUTED).pack(padx=16, pady=(14, 2), anchor="w")
            val_text = f"¥{int(value):,}"
            ctk.CTkLabel(card, text=val_text, font=ctk.CTkFont(size=22, weight="bold"),
                         text_color=color).pack(padx=16, pady=(0, 14), anchor="w")

        # Linha 2: Mensalidades + Contas pendentes
        cards2 = [
            ("Mensalidades Pagas", totals["mensalidades_pagas"], COLOR_GREEN, 0),
            ("Mensalidades Pendentes", totals["mensalidades_pendentes"], COLOR_AMBER, 1),
            ("Contas Pendentes", totals["contas_pendentes"], COLOR_RED, 2),
        ]
        for label, value, color, col in cards2:
            card = ctk.CTkFrame(frame, fg_color="white", corner_radius=16,
                                border_width=1, border_color="#e2e8f0")
            card.grid(row=3, column=col, padx=10, pady=8, sticky="ew")
            card.grid_columnconfigure(0, weight=1)

            ctk.CTkLabel(card, text=label, font=ctk.CTkFont(size=11),
                         text_color=COLOR_MUTED).pack(padx=16, pady=(14, 2), anchor="w")
            if isinstance(value, float):
                val_text = f"¥{int(value):,}"
            else:
                val_text = str(value)
            ctk.CTkLabel(card, text=val_text, font=ctk.CTkFont(size=20, weight="bold"),
                         text_color=color).pack(padx=16, pady=(0, 14), anchor="w")

        # Ultimas contas
        ctk.CTkLabel(
            frame, text="Ultimas Contas a Pagar",
            font=ctk.CTkFont(size=14, weight="bold"), text_color=COLOR_TEXT,
        ).grid(row=4, column=0, columnspan=4, sticky="w", padx=20, pady=(20, 6))

        contas = db.list_contas(self.conn)[:5]
        if not contas:
            ctk.CTkLabel(frame, text="Nenhuma conta cadastrada.",
                         text_color=COLOR_MUTED).grid(row=5, column=0, columnspan=4, padx=20)
        else:
            for i, c in enumerate(contas):
                row_frame = ctk.CTkFrame(frame, fg_color="white", corner_radius=10,
                                         border_width=1, border_color="#e2e8f0")
                row_frame.grid(row=5 + i, column=0, columnspan=4,
                               sticky="ew", padx=20, pady=3)
                row_frame.grid_columnconfigure(1, weight=1)

                status_color = COLOR_GREEN if c["status"] == "Pago" else COLOR_RED
                ctk.CTkLabel(row_frame, text="●", text_color=status_color,
                             font=ctk.CTkFont(size=10)).grid(row=0, column=0, padx=(12, 4))
                ctk.CTkLabel(row_frame, text=c["descricao"][:40],
                             font=ctk.CTkFont(size=12)).grid(row=0, column=1, sticky="w")
                val = f"¥{int(c['valor']):,}"
                ctk.CTkLabel(row_frame, text=val,
                             font=ctk.CTkFont(size=12, weight="bold"),
                             text_color=COLOR_TEXT).grid(row=0, column=2, padx=12, pady=8)

    # ═══════════════════════════════════════════════════════════════════════
    # CONTAS A PAGAR
    # ═══════════════════════════════════════════════════════════════════════

    def _build_contas(self) -> None:
        assert self.conn
        frame = ctk.CTkFrame(self.content, fg_color=COLOR_BG, corner_radius=0)
        frame.grid(row=0, column=0, sticky="nsew")
        frame.grid_columnconfigure(0, weight=1)
        frame.grid_rowconfigure(1, weight=1)

        # Header
        header = ctk.CTkFrame(frame, fg_color=COLOR_BG)
        header.grid(row=0, column=0, sticky="ew", padx=20, pady=(20, 8))
        header.grid_columnconfigure(0, weight=1)

        ctk.CTkLabel(header, text="Contas a Pagar",
                      font=ctk.CTkFont(size=20, weight="bold"),
                      text_color=COLOR_TEXT).grid(row=0, column=0, sticky="w")
        ctk.CTkButton(header, text="+ Nova Conta", fg_color=COLOR_ACCENT,
                       width=130, height=34, corner_radius=8,
                       command=lambda: self._conta_dialog()).grid(row=0, column=1)

        # Tabela
        table_frame = ctk.CTkScrollableFrame(frame, fg_color="white", corner_radius=16,
                                              border_width=1, border_color="#e2e8f0")
        table_frame.grid(row=1, column=0, sticky="nsew", padx=20, pady=(0, 20))
        table_frame.grid_columnconfigure((0, 1, 2, 3, 4, 5), weight=1)

        # Cabecalho
        headers = ["#", "Descricao", "Vencimento", "Categoria", "Valor", "Status"]
        for i, h in enumerate(headers):
            ctk.CTkLabel(table_frame, text=h, font=ctk.CTkFont(size=11, weight="bold"),
                         text_color=COLOR_MUTED).grid(row=0, column=i, padx=8, pady=8, sticky="w")

        contas = db.list_contas(self.conn)
        for row_idx, c in enumerate(contas, start=1):
            vals = [
                str(c["id"]),
                c["descricao"][:35],
                c["data_vencimento"],
                c.get("categoria") or "-",
                f"¥{int(c['valor']):,}",
                c["status"],
            ]
            for col_idx, v in enumerate(vals):
                color = COLOR_GREEN if col_idx == 5 and v == "Pago" else (
                    COLOR_RED if col_idx == 5 else COLOR_TEXT)
                ctk.CTkLabel(table_frame, text=v, font=ctk.CTkFont(size=11),
                             text_color=color).grid(
                    row=row_idx, column=col_idx, padx=8, pady=4, sticky="w")

            # Botoes de acao
            act = ctk.CTkFrame(table_frame, fg_color="transparent")
            act.grid(row=row_idx, column=6, padx=4, pady=2)
            ctk.CTkButton(act, text="Editar", width=50, height=24, font=ctk.CTkFont(size=10),
                           fg_color="#475569", corner_radius=6,
                           command=lambda cid=c["id"]: self._conta_dialog(cid)).pack(side="left", padx=2)
            ctk.CTkButton(act, text="Excluir", width=50, height=24, font=ctk.CTkFont(size=10),
                           fg_color=COLOR_RED, corner_radius=6,
                           command=lambda cid=c["id"]: self._delete_conta(cid)).pack(side="left", padx=2)

    def _conta_dialog(self, conta_id: int | None = None) -> None:
        """Dialog para criar/editar conta."""
        assert self.conn
        is_edit = conta_id is not None
        data_atual: dict = {}
        if is_edit:
            data_atual = db.get_conta(self.conn, conta_id) or {}

        dlg = ctk.CTkToplevel(self)
        dlg.title("Editar Conta" if is_edit else "Nova Conta")
        dlg.geometry("420x440")
        dlg.transient(self)
        dlg.grab_set()

        fields: dict[str, ctk.CTkEntry | ctk.CTkOptionMenu] = {}

        r = 0
        for label, key, default in [
            ("Descricao", "descricao", ""),
            ("Valor (¥)", "valor", "0"),
            ("Vencimento (YYYY-MM-DD)", "data_vencimento", date.today().isoformat()),
            ("Categoria", "categoria", ""),
        ]:
            ctk.CTkLabel(dlg, text=label, font=ctk.CTkFont(size=12)).pack(padx=20, pady=(10 if r == 0 else 4, 0), anchor="w")
            entry = ctk.CTkEntry(dlg, height=34)
            entry.pack(fill="x", padx=20)
            entry.insert(0, str(data_atual.get(key, default)))
            fields[key] = entry
            r += 1

        ctk.CTkLabel(dlg, text="Tipo", font=ctk.CTkFont(size=12)).pack(padx=20, pady=(4, 0), anchor="w")
        tipo_menu = ctk.CTkOptionMenu(dlg, values=["saida", "entrada"], height=34)
        tipo_menu.set(data_atual.get("tipo", "saida"))
        tipo_menu.pack(fill="x", padx=20)
        fields["tipo"] = tipo_menu

        ctk.CTkLabel(dlg, text="Status", font=ctk.CTkFont(size=12)).pack(padx=20, pady=(4, 0), anchor="w")
        status_menu = ctk.CTkOptionMenu(dlg, values=["Pendente", "Pago"], height=34)
        status_menu.set(data_atual.get("status", "Pendente"))
        status_menu.pack(fill="x", padx=20)
        fields["status"] = status_menu

        def _save():
            assert self.conn
            try:
                payload = {
                    "descricao": fields["descricao"].get(),
                    "valor": float(fields["valor"].get().replace(",", ".")),
                    "data_vencimento": fields["data_vencimento"].get(),
                    "categoria": fields["categoria"].get() or None,
                    "tipo": fields["tipo"].get(),
                    "status": fields["status"].get(),
                    "data_pagamento": date.today().isoformat() if fields["status"].get() == "Pago" else None,
                    "comprovante_path": data_atual.get("comprovante_path"),
                }
                if is_edit:
                    assert conta_id is not None
                    db.update_conta(self.conn, conta_id, payload)
                else:
                    db.create_conta(self.conn, payload)
                dlg.destroy()
                self._show_frame("contas")
            except Exception as e:
                messagebox.showerror("Erro", str(e))

        ctk.CTkButton(dlg, text="Salvar", fg_color=COLOR_ACCENT, height=36,
                       command=_save).pack(fill="x", padx=20, pady=16)

    def _delete_conta(self, conta_id: int) -> None:
        assert self.conn
        if messagebox.askyesno("Confirmar", "Excluir esta conta?"):
            db.delete_conta(self.conn, conta_id)
            self._show_frame("contas")

    # ═══════════════════════════════════════════════════════════════════════
    # ENTRADAS
    # ═══════════════════════════════════════════════════════════════════════

    def _build_entradas(self) -> None:
        assert self.conn
        frame = ctk.CTkFrame(self.content, fg_color=COLOR_BG, corner_radius=0)
        frame.grid(row=0, column=0, sticky="nsew")
        frame.grid_columnconfigure(0, weight=1)
        frame.grid_rowconfigure(1, weight=1)

        header = ctk.CTkFrame(frame, fg_color=COLOR_BG)
        header.grid(row=0, column=0, sticky="ew", padx=20, pady=(20, 8))
        header.grid_columnconfigure(0, weight=1)

        ctk.CTkLabel(header, text="Entradas",
                      font=ctk.CTkFont(size=20, weight="bold"),
                      text_color=COLOR_TEXT).grid(row=0, column=0, sticky="w")
        ctk.CTkButton(header, text="+ Nova Entrada", fg_color=COLOR_GREEN,
                       width=140, height=34, corner_radius=8,
                       command=lambda: self._entrada_dialog()).grid(row=0, column=1)

        table_frame = ctk.CTkScrollableFrame(frame, fg_color="white", corner_radius=16,
                                              border_width=1, border_color="#e2e8f0")
        table_frame.grid(row=1, column=0, sticky="nsew", padx=20, pady=(0, 20))
        table_frame.grid_columnconfigure((0, 1, 2, 3, 4), weight=1)

        for i, h in enumerate(["#", "Descricao", "Origem", "Data", "Valor"]):
            ctk.CTkLabel(table_frame, text=h, font=ctk.CTkFont(size=11, weight="bold"),
                         text_color=COLOR_MUTED).grid(row=0, column=i, padx=8, pady=8, sticky="w")

        entradas = db.list_entradas(self.conn)
        for row_idx, e in enumerate(entradas, start=1):
            vals = [
                str(e["id"]),
                e["descricao"][:40],
                e.get("origem") or "-",
                e["data_entrada"],
                f"¥{int(e['valor']):,}",
            ]
            for col_idx, v in enumerate(vals):
                ctk.CTkLabel(table_frame, text=v, font=ctk.CTkFont(size=11),
                             text_color=COLOR_TEXT).grid(
                    row=row_idx, column=col_idx, padx=8, pady=4, sticky="w")

            act = ctk.CTkFrame(table_frame, fg_color="transparent")
            act.grid(row=row_idx, column=5, padx=4, pady=2)
            ctk.CTkButton(act, text="Editar", width=50, height=24, font=ctk.CTkFont(size=10),
                           fg_color="#475569", corner_radius=6,
                           command=lambda eid=e["id"]: self._entrada_dialog(eid)).pack(side="left", padx=2)
            ctk.CTkButton(act, text="Excluir", width=50, height=24, font=ctk.CTkFont(size=10),
                           fg_color=COLOR_RED, corner_radius=6,
                           command=lambda eid=e["id"]: self._delete_entrada(eid)).pack(side="left", padx=2)

    def _entrada_dialog(self, entrada_id: int | None = None) -> None:
        assert self.conn
        is_edit = entrada_id is not None
        data_atual: dict = {}
        if is_edit:
            rows = self.conn.execute("SELECT * FROM entradas WHERE id = ?", (entrada_id,)).fetchone()
            data_atual = dict(rows) if rows else {}

        dlg = ctk.CTkToplevel(self)
        dlg.title("Editar Entrada" if is_edit else "Nova Entrada")
        dlg.geometry("420x400")
        dlg.transient(self)
        dlg.grab_set()

        fields: dict[str, ctk.CTkEntry | ctk.CTkOptionMenu] = {}
        for label, key, default in [
            ("Descricao", "descricao", ""),
            ("Valor (¥)", "valor", "0"),
            ("Data (YYYY-MM-DD)", "data_entrada", date.today().isoformat()),
        ]:
            ctk.CTkLabel(dlg, text=label, font=ctk.CTkFont(size=12)).pack(padx=20, pady=(8, 0), anchor="w")
            entry = ctk.CTkEntry(dlg, height=34)
            entry.pack(fill="x", padx=20)
            entry.insert(0, str(data_atual.get(key, default)))
            fields[key] = entry

        ctk.CTkLabel(dlg, text="Origem", font=ctk.CTkFont(size=12)).pack(padx=20, pady=(8, 0), anchor="w")
        origem_menu = ctk.CTkOptionMenu(dlg, values=["mensalidade", "trabalho", "doacao", "outro"], height=34)
        origem_menu.set(data_atual.get("origem", "outro"))
        origem_menu.pack(fill="x", padx=20)
        fields["origem"] = origem_menu

        # Comprovante
        comprovante_var = tk.StringVar(value="")
        def _select_img():
            path = filedialog.askopenfilename(filetypes=[("Imagens", "*.jpg *.jpeg *.png *.webp")])
            if path:
                comprovante_var.set(path)
        ctk.CTkButton(dlg, text="Anexar Comprovante", fg_color="#475569", height=30,
                       command=_select_img).pack(padx=20, pady=(8, 0), anchor="w")

        def _save():
            assert self.conn
            try:
                valor = float(fields["valor"].get().replace(",", "."))
                entrada_data, credito_data = finance.preparar_entrada_com_credito(
                    descricao=fields["descricao"].get(),
                    valor=valor,
                    origem=fields["origem"].get(),
                    data_entrada=fields["data_entrada"].get(),
                )

                if is_edit:
                    entrada_data["comprovante_path"] = data_atual.get("comprovante_path")
                    assert entrada_id is not None
                    db.update_entrada(self.conn, entrada_id, entrada_data)
                else:
                    new_id = db.create_entrada(self.conn, entrada_data)
                    # Registra credito casa
                    credito_data["entrada_id"] = new_id
                    db.create_credito_casa(self.conn, credito_data)
                    # Processa comprovante se houver
                    img_path = comprovante_var.get()
                    if img_path:
                        dest = images.process_comprovante(img_path, new_id)
                        self.conn.execute(
                            "UPDATE entradas SET comprovante_path = ? WHERE id = ?",
                            (str(dest), new_id))
                        self.conn.commit()

                dlg.destroy()
                self._show_frame("entradas")
            except Exception as e:
                messagebox.showerror("Erro", str(e))

        ctk.CTkButton(dlg, text="Salvar", fg_color=COLOR_ACCENT, height=36,
                       command=_save).pack(fill="x", padx=20, pady=16)

    def _delete_entrada(self, entrada_id: int) -> None:
        assert self.conn
        if messagebox.askyesno("Confirmar", "Excluir esta entrada?"):
            db.delete_entrada(self.conn, entrada_id)
            self._show_frame("entradas")

    # ═══════════════════════════════════════════════════════════════════════
    # MENSALIDADES
    # ═══════════════════════════════════════════════════════════════════════

    def _build_mensalidades(self) -> None:
        assert self.conn
        frame = ctk.CTkFrame(self.content, fg_color=COLOR_BG, corner_radius=0)
        frame.grid(row=0, column=0, sticky="nsew")
        frame.grid_columnconfigure(0, weight=1)
        frame.grid_rowconfigure(1, weight=1)

        header = ctk.CTkFrame(frame, fg_color=COLOR_BG)
        header.grid(row=0, column=0, sticky="ew", padx=20, pady=(20, 8))
        header.grid_columnconfigure(0, weight=1)

        ctk.CTkLabel(header, text="Mensalidades dos Filhos",
                      font=ctk.CTkFont(size=20, weight="bold"),
                      text_color=COLOR_TEXT).grid(row=0, column=0, sticky="w")
        ctk.CTkButton(header, text="+ Nova Mensalidade", fg_color=COLOR_ACCENT,
                       width=160, height=34, corner_radius=8,
                       command=lambda: self._mensalidade_dialog()).grid(row=0, column=1)

        table_frame = ctk.CTkScrollableFrame(frame, fg_color="white", corner_radius=16,
                                              border_width=1, border_color="#e2e8f0")
        table_frame.grid(row=1, column=0, sticky="nsew", padx=20, pady=(0, 20))
        table_frame.grid_columnconfigure((0, 1, 2, 3, 4, 5), weight=1)

        for i, h in enumerate(["#", "Filho", "Mes Ref.", "Valor", "Credito Casa", "Status"]):
            ctk.CTkLabel(table_frame, text=h, font=ctk.CTkFont(size=11, weight="bold"),
                         text_color=COLOR_MUTED).grid(row=0, column=i, padx=8, pady=8, sticky="w")

        mensalidades = db.list_mensalidades(self.conn)
        for row_idx, m in enumerate(mensalidades, start=1):
            vals = [
                str(m["id"]),
                m["filho_nome"],
                m["mes_referencia"],
                f"¥{int(m['valor']):,}",
                f"¥{int(m['credito_casa']):,}",
                m["status"],
            ]
            for col_idx, v in enumerate(vals):
                color = COLOR_GREEN if col_idx == 5 and v == "Pago" else (
                    COLOR_AMBER if col_idx == 5 else COLOR_TEXT)
                ctk.CTkLabel(table_frame, text=v, font=ctk.CTkFont(size=11),
                             text_color=color).grid(
                    row=row_idx, column=col_idx, padx=8, pady=4, sticky="w")

            act = ctk.CTkFrame(table_frame, fg_color="transparent")
            act.grid(row=row_idx, column=6, padx=4, pady=2)
            if m["status"] != "Pago":
                ctk.CTkButton(act, text="Pagar", width=50, height=24, font=ctk.CTkFont(size=10),
                               fg_color=COLOR_GREEN, corner_radius=6,
                               command=lambda mid=m["id"]: self._pagar_mensalidade(mid)).pack(side="left", padx=2)

    def _mensalidade_dialog(self) -> None:
        assert self.conn
        dlg = ctk.CTkToplevel(self)
        dlg.title("Nova Mensalidade")
        dlg.geometry("420x340")
        dlg.transient(self)
        dlg.grab_set()

        fields: dict[str, ctk.CTkEntry] = {}
        for label, key, default in [
            ("Nome do Filho", "filho_nome", ""),
            ("Valor (¥)", "valor", "0"),
            ("Mes Referencia (YYYY-MM)", "mes_referencia", date.today().strftime("%Y-%m")),
        ]:
            ctk.CTkLabel(dlg, text=label, font=ctk.CTkFont(size=12)).pack(padx=20, pady=(8, 0), anchor="w")
            entry = ctk.CTkEntry(dlg, height=34)
            entry.pack(fill="x", padx=20)
            entry.insert(0, default)
            fields[key] = entry

        def _save():
            assert self.conn
            try:
                valor = float(fields["valor"].get().replace(",", "."))
                payload = finance.preparar_mensalidade(
                    filho_nome=fields["filho_nome"].get(),
                    valor=valor,
                    mes_referencia=fields["mes_referencia"].get(),
                )
                db.create_mensalidade(self.conn, payload)
                dlg.destroy()
                self._show_frame("mensalidades")
            except Exception as e:
                messagebox.showerror("Erro", str(e))

        ctk.CTkButton(dlg, text="Salvar", fg_color=COLOR_ACCENT, height=36,
                       command=_save).pack(fill="x", padx=20, pady=16)

    def _pagar_mensalidade(self, mid: int) -> None:
        assert self.conn
        row = self.conn.execute("SELECT * FROM mensalidades_filhos WHERE id = ?", (mid,)).fetchone()
        if not row:
            return
        data = dict(row)
        data["status"] = "Pago"
        data["data_pagamento"] = date.today().isoformat()
        data["comprovante_path"] = data.get("comprovante_path")
        db.update_mensalidade(self.conn, mid, data)

        # Registra como entrada + credito casa
        entrada_data, credito_data = finance.preparar_entrada_com_credito(
            descricao=f"Mensalidade — {data['filho_nome']} ({data['mes_referencia']})",
            valor=data["valor"],
            origem="mensalidade",
            data_entrada=date.today().isoformat(),
            referencia_id=mid,
        )
        new_id = db.create_entrada(self.conn, entrada_data)
        credito_data["entrada_id"] = new_id
        db.create_credito_casa(self.conn, credito_data)

        self._show_frame("mensalidades")

    # ═══════════════════════════════════════════════════════════════════════
    # RELATORIOS
    # ═══════════════════════════════════════════════════════════════════════

    def _build_relatorios(self) -> None:
        assert self.conn
        frame = ctk.CTkScrollableFrame(self.content, fg_color=COLOR_BG)
        frame.grid(row=0, column=0, sticky="nsew")
        frame.grid_columnconfigure(0, weight=1)

        ctk.CTkLabel(
            frame, text="Relatorios",
            font=ctk.CTkFont(size=20, weight="bold"), text_color=COLOR_TEXT,
        ).pack(padx=20, pady=(20, 4), anchor="w")
        ctk.CTkLabel(
            frame, text="Gere PDFs profissionais a partir dos dados do banco ativo.",
            font=ctk.CTkFont(size=12), text_color=COLOR_MUTED,
        ).pack(padx=20, pady=(0, 16), anchor="w")

        # Filtros de periodo
        filter_frame = ctk.CTkFrame(frame, fg_color="white", corner_radius=12,
                                     border_width=1, border_color="#e2e8f0")
        filter_frame.pack(fill="x", padx=20, pady=(0, 12))

        ctk.CTkLabel(filter_frame, text="Periodo Inicio (YYYY-MM-DD):",
                      font=ctk.CTkFont(size=11)).grid(row=0, column=0, padx=12, pady=8)
        inicio_entry = ctk.CTkEntry(filter_frame, height=30, width=140)
        inicio_entry.grid(row=0, column=1, padx=4, pady=8)
        inicio_entry.insert(0, date.today().replace(day=1).isoformat())

        ctk.CTkLabel(filter_frame, text="Fim:",
                      font=ctk.CTkFont(size=11)).grid(row=0, column=2, padx=12, pady=8)
        fim_entry = ctk.CTkEntry(filter_frame, height=30, width=140)
        fim_entry.grid(row=0, column=3, padx=4, pady=8)
        fim_entry.insert(0, date.today().isoformat())

        # Botoes de geracao
        buttons_frame = ctk.CTkFrame(frame, fg_color="transparent")
        buttons_frame.pack(fill="x", padx=20, pady=(0, 16))

        def _gen(fn, *args):
            try:
                path = fn(self.conn, inicio_entry.get(), fim_entry.get())
                messagebox.showinfo("PDF Gerado", f"Salvo em:\n{path}")
                # Abre o PDF no viewer do sistema
                if sys.platform == "win32":
                    os.startfile(str(path))
            except Exception as e:
                messagebox.showerror("Erro", str(e))

        ctk.CTkButton(buttons_frame, text="Relatorio de Contas",
                       fg_color=COLOR_RED, height=36, corner_radius=8,
                       command=lambda: _gen(reports.gerar_relatorio_contas)).pack(
            side="left", padx=(0, 8))
        ctk.CTkButton(buttons_frame, text="Relatorio de Entradas",
                       fg_color=COLOR_GREEN, height=36, corner_radius=8,
                       command=lambda: _gen(reports.gerar_relatorio_entradas)).pack(
            side="left", padx=(0, 8))

        def _gen_resumo():
            assert self.conn
            try:
                mes = date.today().strftime("%Y-%m")
                path = reports.gerar_relatorio_resumo(self.conn, mes)
                messagebox.showinfo("PDF Gerado", f"Salvo em:\n{path}")
                if sys.platform == "win32":
                    os.startfile(str(path))
            except Exception as e:
                messagebox.showerror("Erro", str(e))

        ctk.CTkButton(buttons_frame, text="Resumo Financeiro",
                       fg_color=COLOR_ACCENT, height=36, corner_radius=8,
                       command=_gen_resumo).pack(side="left")

        # Historico de relatorios gerados
        ctk.CTkLabel(
            frame, text="Historico de Relatorios",
            font=ctk.CTkFont(size=14, weight="bold"), text_color=COLOR_TEXT,
        ).pack(padx=20, pady=(8, 6), anchor="w")

        hist = db.list_relatorios(self.conn)
        if not hist:
            ctk.CTkLabel(frame, text="Nenhum relatorio gerado ainda.",
                         text_color=COLOR_MUTED).pack(padx=20, anchor="w")
        else:
            for r in hist[:10]:
                row = ctk.CTkFrame(frame, fg_color="white", corner_radius=10,
                                   border_width=1, border_color="#e2e8f0")
                row.pack(fill="x", padx=20, pady=3)

                ctk.CTkLabel(row, text=f"{r['tipo']}",
                             font=ctk.CTkFont(size=12, weight="bold"),
                             text_color=COLOR_ACCENT).pack(side="left", padx=12, pady=8)
                ctk.CTkLabel(row, text=r["created_at"][:19],
                             font=ctk.CTkFont(size=10),
                             text_color=COLOR_MUTED).pack(side="left", padx=4)

                pdf_name = Path(r["caminho_pdf"]).name
                ctk.CTkLabel(row, text=pdf_name,
                             font=ctk.CTkFont(size=10),
                             text_color="#475569").pack(side="right", padx=12)
