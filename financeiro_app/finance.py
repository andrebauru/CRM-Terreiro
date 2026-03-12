"""
Funcoes puras de calculo financeiro.
Nenhuma dependencia de banco de dados — recebem valores e retornam resultados.
"""

from __future__ import annotations


# ---------------------------------------------------------------------------
# Constantes de regra de negocio
# ---------------------------------------------------------------------------

CREDITO_CASA_PERCENTUAL = 10.0   # 10 % de cada entrada vai para Credito Casa
MENSALIDADE_REPASSE = 100.0      # 100 % da mensalidade vai para a casa


# ---------------------------------------------------------------------------
# Credito Casa  (trava de 10 %)
# ---------------------------------------------------------------------------

def calcular_credito_casa(valor_entrada: float) -> float:
    """
    Retorna o valor que deve ser reservado como Credito Casa.
    Regra: 10 % de cada entrada e retido automaticamente.

    >>> calcular_credito_casa(1000.0)
    100.0
    >>> calcular_credito_casa(0)
    0.0
    """
    if valor_entrada <= 0:
        return 0.0
    return round(valor_entrada * (CREDITO_CASA_PERCENTUAL / 100.0), 2)


def calcular_valor_liquido(valor_entrada: float) -> float:
    """
    Retorna o valor liquido apos descontar o Credito Casa.

    >>> calcular_valor_liquido(1000.0)
    900.0
    """
    return round(valor_entrada - calcular_credito_casa(valor_entrada), 2)


# ---------------------------------------------------------------------------
# Mensalidades Filhos  (trava de 100 %)
# ---------------------------------------------------------------------------

def calcular_repasse_mensalidade(valor_mensalidade: float) -> float:
    """
    Retorna o valor repassado a casa.
    Regra: 100 % da mensalidade e revertido integralmente.

    >>> calcular_repasse_mensalidade(250.0)
    250.0
    """
    if valor_mensalidade <= 0:
        return 0.0
    return round(valor_mensalidade * (MENSALIDADE_REPASSE / 100.0), 2)


# ---------------------------------------------------------------------------
# Preparacao de dados para insercao
# ---------------------------------------------------------------------------

def preparar_entrada_com_credito(
    descricao: str,
    valor: float,
    origem: str,
    data_entrada: str,
    referencia_id: int | None = None,
) -> tuple[dict, dict]:
    """
    Retorna (dados_entrada, dados_credito) prontos para insercao no banco.
    A funcao e pura: nao toca no banco, apenas calcula.
    """
    credito = calcular_credito_casa(valor)

    entrada = {
        "descricao": descricao,
        "valor": valor,
        "origem": origem,
        "referencia_id": referencia_id,
        "data_entrada": data_entrada,
    }

    credito_data = {
        "entrada_id": None,  # sera preenchido apos INSERT da entrada
        "valor_original": valor,
        "percentual": CREDITO_CASA_PERCENTUAL,
        "valor_credito": credito,
        "descricao": f"Credito Casa — {descricao}",
        "data": data_entrada,
    }

    return entrada, credito_data


def preparar_mensalidade(
    filho_nome: str,
    valor: float,
    mes_referencia: str,
    status: str = "Pendente",
) -> dict:
    """
    Retorna dict da mensalidade pronto para insercao.
    O campo credito_casa ja vem calculado (100 % do valor).
    """
    return {
        "filho_nome": filho_nome,
        "valor": valor,
        "mes_referencia": mes_referencia,
        "status": status,
        "credito_casa": calcular_repasse_mensalidade(valor),
    }


# ---------------------------------------------------------------------------
# Resumo financeiro (funcao pura sobre listas)
# ---------------------------------------------------------------------------

def resumo_periodo(entradas: list[dict], saidas: list[dict]) -> dict:
    """
    Recebe listas de dicts com campo 'valor' e retorna resumo.

    >>> resumo_periodo([{'valor': 500}, {'valor': 300}], [{'valor': 200}])
    {'total_entradas': 800, 'total_saidas': 200, 'saldo': 600}
    """
    t_ent = sum(e.get("valor", 0) for e in entradas)
    t_sai = sum(s.get("valor", 0) for s in saidas)
    return {
        "total_entradas": t_ent,
        "total_saidas": t_sai,
        "saldo": t_ent - t_sai,
    }
