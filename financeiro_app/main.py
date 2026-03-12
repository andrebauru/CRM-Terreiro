"""
Ponto de entrada do Modulo Financeiro.
Execute: python -m financeiro_app
ou:      python financeiro_app/main.py
"""

from __future__ import annotations

import sys
from pathlib import Path

# Garante que o pacote esta no path quando executado diretamente
_root = Path(__file__).resolve().parent.parent
if str(_root) not in sys.path:
    sys.path.insert(0, str(_root))

from financeiro_app.app import FinanceiroApp


def main() -> None:
    app = FinanceiroApp()
    app.mainloop()


if __name__ == "__main__":
    main()
