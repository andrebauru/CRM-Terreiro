<div class="form-errors"></div>
<form action="<?= ROUTE_BASE ?>/clients" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

    <!-- Informações Básicas -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-person me-2"></i>Informações Básicas
        </h4>

        <div class="row">
            <div class="col-md-8">
                <div class="mb-3">
                    <label class="form-label required">Nome do Cliente</label>
                    <input type="text" name="name" class="form-control" placeholder="Nome completo" required>
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">CPF/CNPJ</label>
                    <input type="text" name="document" class="form-control" placeholder="000.000.000-00">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Data de Nascimento</label>
                    <input type="date" name="birth_date" class="form-control">
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Origem do Cliente</label>
                    <select name="source" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="indicacao">Indicação</option>
                        <option value="site">Site</option>
                        <option value="instagram">Instagram</option>
                        <option value="facebook">Facebook</option>
                        <option value="whatsapp">WhatsApp</option>
                        <option value="presencial">Presencial</option>
                        <option value="outro">Outro</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Contato -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-telephone me-2"></i>Contato
        </h4>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-envelope"></i></span>
                        <input type="email" name="email" class="form-control" placeholder="email@exemplo.com">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Telefone Principal</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone" class="form-control" placeholder="(11) 99999-9999">
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">Telefone Secundário</label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-telephone"></i></span>
                        <input type="text" name="phone_secondary" class="form-control" placeholder="(11) 99999-9999">
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="mb-3">
                    <label class="form-label">
                        <i class="bi bi-whatsapp text-success me-1"></i>WhatsApp
                    </label>
                    <div class="input-icon">
                        <span class="input-icon-addon"><i class="bi bi-whatsapp text-success"></i></span>
                        <input type="text" name="whatsapp" class="form-control" placeholder="5511999999999">
                    </div>
                    <small class="form-text">Digite com código do país (55) + DDD + número. O link será gerado automaticamente.</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Endereço -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-geo-alt me-2"></i>Endereço
        </h4>

        <div class="mb-3">
            <label class="form-label">Endereço</label>
            <input type="text" name="address" class="form-control" placeholder="Rua, número, complemento">
        </div>

        <div class="row">
            <div class="col-md-5">
                <div class="mb-3">
                    <label class="form-label">Cidade</label>
                    <input type="text" name="city" class="form-control" placeholder="Cidade">
                </div>
            </div>
            <div class="col-md-4">
                <div class="mb-3">
                    <label class="form-label">Estado</label>
                    <select name="state" class="form-select">
                        <option value="">Selecione...</option>
                        <option value="AC">Acre</option>
                        <option value="AL">Alagoas</option>
                        <option value="AP">Amapá</option>
                        <option value="AM">Amazonas</option>
                        <option value="BA">Bahia</option>
                        <option value="CE">Ceará</option>
                        <option value="DF">Distrito Federal</option>
                        <option value="ES">Espírito Santo</option>
                        <option value="GO">Goiás</option>
                        <option value="MA">Maranhão</option>
                        <option value="MT">Mato Grosso</option>
                        <option value="MS">Mato Grosso do Sul</option>
                        <option value="MG">Minas Gerais</option>
                        <option value="PA">Pará</option>
                        <option value="PB">Paraíba</option>
                        <option value="PR">Paraná</option>
                        <option value="PE">Pernambuco</option>
                        <option value="PI">Piauí</option>
                        <option value="RJ">Rio de Janeiro</option>
                        <option value="RN">Rio Grande do Norte</option>
                        <option value="RS">Rio Grande do Sul</option>
                        <option value="RO">Rondônia</option>
                        <option value="RR">Roraima</option>
                        <option value="SC">Santa Catarina</option>
                        <option value="SP">São Paulo</option>
                        <option value="SE">Sergipe</option>
                        <option value="TO">Tocantins</option>
                    </select>
                </div>
            </div>
            <div class="col-md-3">
                <div class="mb-3">
                    <label class="form-label">CEP</label>
                    <input type="text" name="zip_code" class="form-control" placeholder="00000-000">
                </div>
            </div>
        </div>
    </div>

    <!-- Observações -->
    <div class="mb-4">
        <h4 class="text-muted mb-3">
            <i class="bi bi-journal-text me-2"></i>Observações
        </h4>

        <div class="mb-3">
            <label class="form-label">Anotações sobre o Cliente</label>
            <textarea name="notes" class="form-control" rows="4" placeholder="Informações importantes, preferências, histórico de atendimento, trabalhos a serem realizados, etc."></textarea>
            <small class="form-text">Todas as alterações neste campo serão registradas no histórico.</small>
        </div>
    </div>

    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>
            Salvar Cliente
        </button>
    </div>
</form>
