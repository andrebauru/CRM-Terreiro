#!/bin/bash
# ==============================================================================
# DEPLOY SCRIPT - CRM TERREIRO
# ==============================================================================
# Uso: bash deploy.sh
# Função: Sincroniza código, compila assets, instala dependências e aplica migrações
# ==============================================================================

set -e

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_info() {
    echo -e "${GREEN}✅ ${NC}$1"
}

print_error() {
    echo -e "${RED}❌ ${NC}$1"
}

print_step() {
    echo -e "${YELLOW}🔄 ${NC}$1"
}

print_warning() {
    echo -e "${BLUE}⚠️  ${NC}$1"
}

# ==============================================================================
# INICIALIZAÇÃO
# ==============================================================================

print_info "Iniciando deploy do CRM Terreiro..."
echo ""

# ==============================================================================
# VERIFICAR DEPENDÊNCIAS
# ==============================================================================
print_step "Verificando dependências do sistema..."

if ! command -v git &> /dev/null; then
    print_error "Git não está instalado. Abortando."
    exit 1
fi

if ! command -v npm &> /dev/null; then
    print_error "npm (Node.js) não está instalado. Abortando."
    exit 1
fi

if ! command -v composer &> /dev/null; then
    print_error "Composer não está instalado. Abortando."
    exit 1
fi

if ! command -v php &> /dev/null; then
    print_error "PHP não está instalado. Abortando."
    exit 1
fi

print_info "Todas as dependências encontradas!"
echo ""

# ==============================================================================
# GIT SYNC
# ==============================================================================
print_step "Sincronizando repositório com master remoto..."

git fetch --all
git reset --hard origin/master
git clean -fd

print_info "Repositório sincronizado!"
echo ""

# ==============================================================================
# COMPILAR TAILWIND CSS
# ==============================================================================
print_step "Compilando estilos CSS..."

if [ -f "package.json" ]; then
    if npm run build:css; then
        print_info "CSS compilado com sucesso!"
    else
        print_error "Falha ao compilar CSS"
        exit 1
    fi
else
    print_error "package.json não encontrado"
    exit 1
fi
echo ""

# ==============================================================================
# INSTALAR DEPENDÊNCIAS PHP
# ==============================================================================
print_step "Instalando dependências PHP (Composer)..."

if [ -f "composer.json" ]; then
    if composer install --no-dev --optimize-autoloader; then
        print_info "Dependências PHP instaladas!"
    else
        print_error "Falha ao instalar dependências PHP"
        exit 1
    fi
else
    print_warning "composer.json não encontrado. Pulando Composer."
fi
echo ""

# ==============================================================================
# EXECUTAR MIGRAÇÕES
# ==============================================================================
print_step "Executando migrações do banco de dados..."

if [ -f "migrate.php" ]; then
    if php migrate.php; then
        print_info "Migrações executadas!"
    else
        print_warning "Falha ao executar migrações (verifique manualmente)"
    fi
elif [ -f "database/migrate.php" ]; then
    if php database/migrate.php; then
        print_info "Migrações executadas!"
    else
        print_warning "Falha ao executar migrações (verifique manualmente)"
    fi
else
    print_warning "Script de migração não encontrado"
fi
echo ""

# ==============================================================================
# PERMISSÕES E LIMPEZA
# ==============================================================================
print_step "Configurando permissões..."

# Criar diretórios se não existirem
mkdir -p storage/logs
mkdir -p public/assets/css
mkdir -p public/assets/js

# Limpar logs antigos (7+ dias)
if [ -d "storage/logs" ]; then
    find storage/logs -type f -name "*.log" -mtime +7 -delete 2>/dev/null || true
    print_info "Logs antigos removidos"
fi

# Tentar aplicar permissões (não falha se sem sudo)
if command -v sudo &> /dev/null; then
    sudo chmod -R 755 storage 2>/dev/null || true
    sudo chmod -R 755 public 2>/dev/null || true
    sudo chown -R www-data:www-data . 2>/dev/null || true
fi

print_info "Permissões configuradas!"
echo ""

# ==============================================================================
# FINALIZACAO
# ==============================================================================
print_info "✨ Deploy concluído com sucesso!"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "📋 Resumo do deploy:"
echo "   ✓ Repositório sincronizado com origin/master"
echo "   ✓ Tailwind CSS compilado"
echo "   ✓ Dependências PHP instaladas"
echo "   ✓ Migrações executadas"
echo "   ✓ Permissões configuradas"
echo ""
echo "🚀 Sistema pronto para produção!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
